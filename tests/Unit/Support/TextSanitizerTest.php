<?php

namespace Tests\Unit\Support;

use App\Support\TextSanitizer;
use PHPUnit\Framework\TestCase;

class TextSanitizerTest extends TestCase
{
    public function test_headings_bold_italic_and_inline_code_are_flattened(): void
    {
        $in = "## Judul Utama\n\nIni **penting** dan *sangat* penting, `code` juga.";
        $out = TextSanitizer::markdownToPlain($in);

        $this->assertStringNotContainsString('#', $out);
        $this->assertStringNotContainsString('**', $out);
        $this->assertStringNotContainsString('`', $out);
        $this->assertStringContainsString('Judul Utama', $out);
        $this->assertStringContainsString('Ini penting dan sangat penting, code juga.', $out);
    }

    public function test_plain_urls_emoji_and_snake_case_are_untouched(): void
    {
        $in = 'Silakan order di https://toko.test/order kak 👋 file config_app.yaml dan diskon_50%';
        $this->assertSame($in, TextSanitizer::markdownToPlain($in));
    }

    public function test_links_keep_the_url_but_drop_the_markdown_wrapper(): void
    {
        $out = TextSanitizer::markdownToPlain('Lihat [Paket Pro](https://toko.test/pro) ya');

        $this->assertStringContainsString('Paket Pro (https://toko.test/pro)', $out);
        $this->assertStringNotContainsString('](', $out);
    }

    public function test_fenced_code_content_survives_untouched(): void
    {
        $in = "Contoh:\n```php\n# komentar\n\$x = **not bold**;\necho \$x;\n```\nSelesai.";
        $out = TextSanitizer::markdownToPlain($in);

        $this->assertStringContainsString('# komentar', $out);
        $this->assertStringContainsString('$x = **not bold**;', $out);
        $this->assertStringNotContainsString('```', $out);
        $this->assertStringContainsString('Selesai.', $out);
    }

    public function test_blockquotes_hr_and_raw_html_are_removed(): void
    {
        $out = TextSanitizer::markdownToPlain("> Kutipan\n\n---\n\nTeks <b>tebal</b> <br> selesai.");

        $this->assertSame("Kutipan\n\nTeks tebal selesai.", $out);
    }

    public function test_mojibake_sequences_are_repaired(): void
    {
        $this->assertSame("Halo, don\u{2019}t worry!", TextSanitizer::markdownToPlain("Halo, donâ€™t worry!"));
        $this->assertSame('kopi énak', TextSanitizer::markdownToPlain('kopi Ã©nak'));
    }

    public function test_sanitize_is_idempotent(): void
    {
        $in = "### Harga\n\n**Rp100rb** - hubungi [CS](https://wa.test/1) atau 081234567890";

        $once = TextSanitizer::markdownToPlain($in);
        $this->assertSame($once, TextSanitizer::markdownToPlain($once));
    }

    public function test_markdown_table_is_rendered_as_aligned_plain_text(): void
    {
        $in = implode("\n", [
            '| Ukuran | Estimasi Harga |',
            '|--------|-----------------|',
            '| Pintu lebar 80 cm × tinggi 40 cm | Rp 584.000 |',
            '| Pintu lebar 100 cm × tinggi 60 cm | Rp 760.000 |',
            '| Pintu lebar 120 cm × tinggi 80 cm | Rp 1.232.000 |',
        ]);

        $out = TextSanitizer::markdownToPlain($in);

        $this->assertStringNotContainsString('|---', $out);

        $lines = explode("\n", $out);
        $this->assertCount(5, $lines);

        // Column separator aligns with the pipes of every row.
        foreach ($lines as $line) {
            $this->assertStringContainsString(' | ', $line);
            $this->assertSame(mb_strpos($lines[1], '|'), mb_strpos($line, '|'));
        }

        // Header, rule and data rows are padded to identical column width.
        $col0 = array_map(fn ($line) => explode('|', $line)[0], $lines);
        $this->assertSame(
            array_unique(array_map(fn ($cell) => mb_strwidth($cell), $col0)),
            [mb_strwidth($col0[0])],
        );

        $this->assertStringContainsString('Rp 1.232.000', $out);
    }

    public function test_table_cells_get_inline_markdown_cleaned(): void
    {
        $in = "| Produk | Harga |\n| --- | --- |\n| **Paket Pro** | [Rp100rb](https://toko.test) |";

        $out = TextSanitizer::markdownToPlain($in);

        $this->assertStringContainsString('Paket Pro', $out);
        $this->assertStringContainsString('Rp100rb (https://toko.test)', $out);
        $this->assertStringNotContainsString('**', $out);
        $this->assertStringNotContainsString('](', $out);
    }

    public function test_table_survives_round_two_unchanged(): void
    {
        $in = "Berikut daftarnya:\n\n| A | B |\n|---|---|\n| 1 | 2 |";

        $once = TextSanitizer::markdownToPlain($in);
        $this->assertSame($once, TextSanitizer::markdownToPlain($once));
    }

    public function test_pipe_line_without_separator_is_not_tabled(): void
    {
        $in = "Harga | berlaku mulai hari ini\n---";

        $out = TextSanitizer::markdownToPlain($in);

        $this->assertStringContainsString('Harga | berlaku mulai hari ini', $out);
        $this->assertStringNotContainsString('---', $out);
    }
}
