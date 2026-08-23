<?php

namespace App\Support;

class SimpleReportPdf
{
    private array $pages = [];
    private array $lines = [];
    private float $y = 800;

    public function __construct(
        private readonly string $title,
        private readonly string $subtitle
    ) {
        $this->newPage();
    }

    public function heading(string $text): void
    {
        $this->space(12);
        $this->line($text, 15, true);
        $this->rule();
    }

    public function row(array $columns, array $widths, int $fontSize = 9): void
    {
        $x = 40;
        $maxLines = 1;
        $wrapped = [];

        foreach ($columns as $index => $value) {
            $parts = $this->wrap((string) $value, max(8, (int) floor(($widths[$index] ?? 90) / 5.1)));
            $wrapped[] = $parts;
            $maxLines = max($maxLines, count($parts));
        }

        if ($this->y - ($maxLines * 12) < 40) {
            $this->newPage();
        }

        foreach ($wrapped as $index => $parts) {
            $lineY = $this->y;
            foreach ($parts as $part) {
                $this->text($x, $lineY, $part, $fontSize);
                $lineY -= 11;
            }
            $x += $widths[$index] ?? 90;
        }

        $this->y -= max(15, $maxLines * 12);
    }

    public function kpis(array $items): void
    {
        foreach (array_chunk($items, 3) as $chunk) {
            $this->row(array_map(fn ($item) => $item[0].': '.$item[1], $chunk), [170, 170, 170], 10);
        }
    }

    public function output(): string
    {
        $this->finishPage();

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [] /Count 0 >>';
        $kids = [];
        $contentObject = 3 + count($this->pages);

        foreach ($this->pages as $index => $content) {
            $pageObject = 3 + $index;
            $kids[] = "{$pageObject} 0 R";
            $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$contentObject} 0 R >> >> /Contents ".($contentObject + 1 + $index).' 0 R >>';
        }

        $objects[1] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($this->pages).' >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        foreach ($this->pages as $content) {
            $objects[] = '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $number => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($number + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function newPage(): void
    {
        if ($this->lines !== []) {
            $this->finishPage();
        }

        $this->lines = [];
        $this->y = 800;
        $this->line($this->title, 18, true);
        $this->line($this->subtitle, 10);
        $this->space(8);
    }

    private function finishPage(): void
    {
        if ($this->lines === []) {
            return;
        }

        $this->pages[] = "BT\n".implode("\n", $this->lines)."\nET";
        $this->lines = [];
    }

    private function line(string $text, int $size = 10, bool $bold = false): void
    {
        $this->text(40, $this->y, $bold ? mb_strtoupper($text) : $text, $size);
        $this->y -= $size + 6;
    }

    private function text(float $x, float $y, string $text, int $size): void
    {
        $this->lines[] = sprintf('/F1 %d Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj', $size, $x, $y, $this->escape($text));
    }

    private function rule(): void
    {
        $this->line(str_repeat('-', 110), 8);
    }

    private function space(int $height): void
    {
        $this->y -= $height;
    }

    private function wrap(string $text, int $limit): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [''];

        foreach ($words as $word) {
            $current = end($lines);
            if (mb_strlen(trim($current.' '.$word)) > $limit) {
                $lines[] = $word;
            } else {
                $lines[array_key_last($lines)] = trim($current.' '.$word);
            }
        }

        return array_values(array_filter($lines, fn ($line) => $line !== '')) ?: [''];
    }

    private function escape(string $text): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
    }
}
