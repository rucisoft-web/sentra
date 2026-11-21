<?php

namespace App\Services;

/** Generator PDF teks sederhana (tanpa dependensi) — dipakai untuk file demo & sertifikat. */
class SimplePdf
{
    public static function make(string $title, array $lines, string $footer = ''): string
    {
        $pages = array_chunk(self::wrap($lines), 46);
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $kids = [];
        $n = 5;
        foreach ($pages as $i => $pageLines) {
            $s = "0.12 0.23 0.37 rg 0 800 595 42 re f\n";
            $s .= "BT /F2 11 Tf 1 1 1 rg 40 815 Td (".self::esc('SENTRA - Enterprise Document & Governance System').") Tj ET\n";
            $s .= "BT /F2 15 Tf 0 0 0 rg 40 765 Td (".self::esc($title).") Tj ET\n";
            $s .= "0.7 0.6 0.3 RG 40 755 m 555 755 l S\n";
            $y = 732;
            foreach ($pageLines as $l) {
                $bold = str_starts_with($l, '## ');
                $txt = $bold ? substr($l, 3) : $l;
                $s .= 'BT /'.($bold ? 'F2 11' : 'F1 10')." Tf 0.1 0.1 0.1 rg 40 {$y} Td (".self::esc($txt).") Tj ET\n";
                $y -= $bold ? 18 : 14.5;
            }
            $s .= "BT /F1 8 Tf 0.4 0.4 0.4 rg 40 30 Td (".self::esc($footer.'  |  Halaman '.($i + 1).' dari '.count($pages)).") Tj ET\n";
            $contentId = $n++; $pageId = $n++;
            $objects[$contentId] = "<< /Length ".strlen($s)." >>\nstream\n{$s}endstream";
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentId} 0 R >>";
            $kids[] = "{$pageId} 0 R";
        }
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($kids).' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $id => $obj) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$obj}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $o) $pdf .= sprintf("%010d 00000 n \n", $o);
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    private static function wrap(array $lines): array
    {
        $out = [];
        foreach ($lines as $l) {
            if (str_starts_with($l, '## ')) { $out[] = ''; $out[] = $l; continue; }
            foreach (explode("\n", wordwrap($l, 100, "\n", true)) as $w) $out[] = $w;
        }
        return $out;
    }

    private static function esc(string $s): string
    {
        $s = iconv('UTF-8', 'Windows-1252//TRANSLIT', $s) ?: $s;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }
}
