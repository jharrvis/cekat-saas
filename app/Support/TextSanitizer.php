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

        // 2. Images -> alt text; links -> "label (url)" (URL is kept).
        $t = preg_replace('/!\[([^\]]*)\]\([^)]*\)/u', '$1', $t);
        $t = preg_replace('/\[([^\]]+)\]\(([^)\s]+)\)/u', '$1 ($2)', $t);

        // 3. Line-level constructs: headings, blockquotes, horizontal rules.
        $t = preg_replace('/^[ \t]*#{1,6}[ \t]+/mu', '', $t);
        $t = preg_replace('/^[ \t]*>[ \t]?/mu', '', $t);
        $t = preg_replace('/^[ \t]*([-*_][ \t]*){3,}$/mu', '', $t);

        // 4. Emphasis - word-boundary guarded so snake_case, 2*3*4 and
        //    file_name.txt survive.
        $t = preg_replace('/\*\*(?=\S)([\s\S]*?\S)\*\*/u', '$1', $t);
        $t = preg_replace('/__(?=\S)([\s\S]*?\S)__/u', '$1', $t);
        $t = preg_replace('/(?<![\p{L}\p{N}])\*([^\s*][^*]*?)\*(?![\p{L}\p{N}])/u', '$1', $t);
        $t = preg_replace('/(?<![\p{L}\p{N}_])_([^_\n]+)_(?![\p{L}\p{N}_])/u', '$1', $t);
        $t = preg_replace('/~~(?=\S)([^~]*?\S)~~/u', '$1', $t);
        $t = preg_replace('/`([^`\n]+)`/u', '$1', $t);

        // 5. Leftover raw HTML tags (replies sometimes embed <b>/<br>).
        $t = preg_replace('/<\/?[a-zA-Z][^>]*>/u', '', $t);

        // 6. Restore fenced code content, then drop stray fence markers
        //    (the action-JSON stripper used to leave ```json fences behind).
        $t = preg_replace_callback('/\x1A(\d+)\x1A/', fn ($m) => $fences[(int) $m[1]] ?? '', $t) ?? $t;
        $t = preg_replace('/^[ \t]*```[a-zA-Z0-9_+-]*[ \t]*$/mu', '', $t);

        // 7. Tidy whitespace: collapse runs of spaces left by removed tags
        //    and the blank lines left by removed blocks.
        $t = preg_replace('/[ \t]{2,}/', ' ', $t);
        $t = preg_replace("/\n{3,}/", "\n\n", $t);

        return trim($t);
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
