<?php

namespace App\Support;

/**
 * Plain-text cleanup for LLM replies and summaries.
 *
 * The widget's client parser (parseMarkdown in public/widget/widget.js)
 * only understands a subset of markdown - headings (** ## **) and other
 * constructs rendered literally in the widget and RAW in chat history,
 * lead emails and the admin inbox (all of which print plain escaped text).
 * Sanitizing once, server-side, before persist + return covers every
 * surface; the client parser stays as the XSS escaper/linkifier.
 */
class TextSanitizer
{
    /**
     * Convert markdown-ish text to readable plain text.
     *
     * Invariants: bare URLs stay byte-identical, emoji are preserved,
     * snake_case/file names are untouched, fenced code content is kept
     * as-is (minus the fence lines), and the function is idempotent.
     */
    public static function markdownToPlain(string $text): string
    {
        $t = self::fixEncoding($text);

        // 1. Pull fenced code blocks out so none of the rules below can
        //    mangle code content (placeholders survive the transforms).
        $fences = [];
        $t = preg_replace_callback('/```[a-zA-Z0-9_+-]*\r?\n?([\s\S]*?)```/', function ($m) use (&$fences) {
            $fences[] = trim($m[1]);

            return "\n\x1A" . (count($fences) - 1) . "\x1A\n";
        }, $t) ?? $t;

        // 1b. Pull markdown table blocks out too: the space collapse in
        //     step 7 would otherwise destroy the column padding added
        //     when the tables are rendered below (after that collapse).
        $tables = [];
        $lines = preg_split('/\r?\n/', $t);
        if (is_array($lines)) {
            $kept = [];
            $count = count($lines);
            for ($i = 0; $i < $count; $i++) {
                if (self::tableBlockStartsAt($lines, $i)) {
                    $end = $i + 2;
                    while ($end < $count && str_contains($lines[$end], '|')) {
                        $end++;
                    }
                    $tables[] = array_slice($lines, $i, $end - $i);
                    $kept[] = "\x1B" . (count($tables) - 1) . "\x1B";
                    $i = $end - 1;

                    continue;
                }
                $kept[] = $lines[$i];
            }
            $t = implode("\n", $kept);
        }

        // 2. Images -> alt text; links -> "label (url)" (URL is kept).
        $t = self::linksToPlain($t);

        // 3. Line-level constructs: headings, blockquotes, horizontal rules.
        $t = preg_replace('/^[ \t]*#{1,6}[ \t]+/mu', '', $t);
        $t = preg_replace('/^[ \t]*>[ \t]?/mu', '', $t);
        $t = preg_replace('/^[ \t]*([-*_][ \t]*){3,}$/mu', '', $t);

        // 4. Emphasis - word-boundary guarded so snake_case, 2*3*4 and
        //    file_name.txt survive.
        $t = self::emphasisToPlain($t);

        // 5. Leftover raw HTML tags (replies sometimes embed <b>/<br>).
        $t = self::tagsToPlain($t);

        // 6. Restore fenced code content, then drop stray fence markers
        //    (the action-JSON stripper used to leave ```json fences behind).
        $t = preg_replace_callback('/\x1A(\d+)\x1A/', fn ($m) => $fences[(int) $m[1]] ?? '', $t) ?? $t;
        $t = preg_replace('/^[ \t]*```[a-zA-Z0-9_+-]*[ \t]*$/mu', '', $t);

        // 7. Tidy whitespace: collapse runs of spaces left by removed tags
        //    and the blank lines left by removed blocks.
        $t = preg_replace('/[ \t]{2,}/', ' ', $t);
        $t = preg_replace("/\n{3,}/", "\n\n", $t);

        // 8. Render pulled tables as aligned plain text - column padding
        //    survives because it is added after the collapse above, and
        //    re-running the sanitizer yields byte-identical output (the
        //    rendered rows are picked up as tables again and re-padded
        //    to the same widths).
        if ($tables !== []) {
            $t = preg_replace_callback('/\x1B(\d+)\x1B/', function ($m) use ($tables) {
                $block = $tables[(int) $m[1]] ?? null;

                return is_array($block) ? self::renderPlainTable($block) : '';
            }, $t) ?? $t;
        }

        return trim($t);
    }

    /**
     * A table starts when a pipe line is followed by a GFM separator row
     * ("|---|---|", "---|---", or a plain dashes rule directly under a
     * header that starts/ends with a pipe).
     */
    protected static function tableBlockStartsAt(array $lines, int $i): bool
    {
        $header = trim($lines[$i] ?? '');
        $sep = trim($lines[$i + 1] ?? '');

        if (! str_contains($header, '|')) {
            return false;
        }

        if (! preg_match('/^[\s:|-]+$/', $sep) || ! str_contains($sep, '-')) {
            return false;
        }

        return str_contains($sep, '|')
            || str_starts_with($header, '|')
            || str_ends_with($header, '|');
    }

    /**
     * Render a markdown table block (header, separator, body rows) as
     * column-aligned plain text. Cells get the same inline cleanup as
     * the rest of the reply (links, emphasis, raw tags).
     */
    protected static function renderPlainTable(array $lines): string
    {
        $header = array_map(
            fn ($cell) => trim(self::inlineToPlain($cell)),
            self::splitTableRow($lines[0]),
        );

        $count = count($header);
        if ($count === 0) {
            return '';
        }

        $sep = self::splitTableRow($lines[1] ?? '');
        $aligns = [];
        for ($k = 0; $k < $count; $k++) {
            $aligns[$k] = str_ends_with($sep[$k] ?? '', ':') ? 'right' : 'left';
        }

        $width = fn (string $s): int => function_exists('mb_strwidth')
            ? mb_strwidth($s, 'UTF-8')
            : strlen($s);

        $widths = array_fill(0, $count, 0);
        foreach ($header as $k => $cell) {
            $widths[$k] = max($widths[$k], $width($cell));
        }

        $rows = [];
        foreach (array_slice($lines, 2) as $line) {
            $cells = array_map(
                fn ($cell) => trim(self::inlineToPlain($cell)),
                self::splitTableRow($line),
            );
            if (count($cells) < $count) {
                $cells = array_merge($cells, array_fill(0, $count - count($cells), ''));
            } else {
                $cells = array_slice($cells, 0, $count);
            }
            foreach ($cells as $k => $cell) {
                $widths[$k] = max($widths[$k], $width($cell));
            }
            $rows[] = $cells;
        }

        $pad = function (string $cell, int $target, string $align) use ($width): string {
            $gap = max(0, $target - $width($cell));

            return match ($align) {
                'right' => str_repeat(' ', $gap) . $cell,
                'center' => str_repeat(' ', intdiv($gap, 2)) . $cell . str_repeat(' ', $gap - intdiv($gap, 2)),
                default => $cell . str_repeat(' ', $gap),
            };
        };

        $line = fn (array $cells): string => implode(' | ', array_map(
            fn ($k) => $pad($cells[$k], $widths[$k], $aligns[$k]),
            array_keys($cells),
        ));

        $out = [$line($header), implode(' | ', array_map(fn ($k) => str_repeat('-', $widths[$k]), array_keys($header)))];
        foreach ($rows as $row) {
            $out[] = $line($row);
        }

        return implode("\n", $out);
    }

    /**
     * @return list<string>
     */
    protected static function splitTableRow(string $line): array
    {
        $line = trim($line);
        if (str_starts_with($line, '|')) {
            $line = substr($line, 1);
        }
        if (str_ends_with($line, '|')) {
            $line = substr($line, 0, -1);
        }

        return array_map('trim', explode('|', $line));
    }

    /**
     * Inline-level cleanup shared by plain text and table cells.
     */
    protected static function inlineToPlain(string $t): string
    {
        return self::tagsToPlain(self::emphasisToPlain(self::linksToPlain($t)));
    }

    protected static function linksToPlain(string $t): string
    {
        $t = preg_replace('/!\[([^\]]*)\]\([^)]*\)/u', '$1', $t);
        $t = preg_replace('/\[([^\]]+)\]\(([^)\s]+)\)/u', '$1 ($2)', $t);

        return $t;
    }

    protected static function emphasisToPlain(string $t): string
    {
        $t = preg_replace('/\*\*(?=\S)([\s\S]*?\S)\*\*/u', '$1', $t);
        $t = preg_replace('/__(?=\S)([\s\S]*?\S)__/u', '$1', $t);
        $t = preg_replace('/(?<![\p{L}\p{N}])\*([^\s*][^*]*?)\*(?![\p{L}\p{N}])/u', '$1', $t);
        $t = preg_replace('/(?<![\p{L}\p{N}_])_([^_\n]+)_(?![\p{L}\p{N}_])/u', '$1', $t);
        $t = preg_replace('/~~(?=\S)([^~]*?\S)~~/u', '$1', $t);
        $t = preg_replace('/`([^`\n]+)`/u', '$1', $t);

        return $t;
    }

    protected static function tagsToPlain(string $t): string
    {
        return preg_replace('/<\/?[a-zA-Z][^>]*>/u', '', $t);
    }

    /**
     * Best-effort UTF-8 repair + known CP1252-as-UTF8 double-encoding map.
     * Only the common visible sequences are mapped - never blanket
     * re-decoding, which would corrupt legitimate UTF-8 (emoji, accents).
     */
    protected static function fixEncoding(string $t): string
    {
        if (! mb_check_encoding($t, 'UTF-8')) {
            $fixed = @iconv('UTF-8', 'UTF-8//IGNORE', $t);
            $t = $fixed !== false ? $fixed : '';
        }

        return strtr($t, [
            'â€™' => "\u{2019}", 'â€˜' => "\u{2018}",
            'â€œ' => "\u{201C}", 'â€\u{009D}' => "\u{201D}",
            'â€”' => "\u{2014}", 'â€“' => "\u{2013}", 'â‚¬' => "\u{20AC}",
            'â€¦' => "\u{2026}",
            'Ã©' => 'é', 'Ã¨' => 'è', 'Ãª' => 'ê', 'Ã¡' => 'á',
            'Ã±' => 'ñ', 'Ã¼' => 'ü', 'Ã¶' => 'ö', 'Ã¤' => 'ä',
            'Ã§' => 'ç', 'Ã´' => 'ô',
            'ï¿½' => '', 'Â¿' => '?', 'Â¡' => '!', 'Â ' => ' ',
        ]);
    }
}
