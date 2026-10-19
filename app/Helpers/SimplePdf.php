<?php
/**
 * SimplePdf - dependency-free A4 PDF writer (text, rectangles, JPEG/PNG images).
 * Coordinates are in points measured from the TOP-LEFT corner of the page.
 * Uses the built-in Helvetica fonts with WinAnsi encoding.
 */
class SimplePdf {
    const W = 595.28;
    const H = 841.89;

    private $pages = [];
    private $images = [];
    private $cur = -1;

    public function addPage() {
        $this->pages[] = ['ops' => '', 'imgs' => []];
        $this->cur = count($this->pages) - 1;
    }

    private function op($s) {
        if ($this->cur < 0) $this->addPage();
        $this->pages[$this->cur]['ops'] .= $s . "\n";
    }

    private static function esc($txt) {
        $txt = (string)$txt;
        $conv = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $txt);
        if ($conv === false) $conv = preg_replace('/[^\x20-\x7E]/', '?', $txt);
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $conv);
    }

    private static function rgb($c) {
        return sprintf('%.3F %.3F %.3F', $c[0] / 255, $c[1] / 255, $c[2] / 255);
    }

    public function text($x, $y, $txt, $size = 10, $bold = false, $color = [25, 32, 46]) {
        $this->op(sprintf('BT /%s %.2F Tf %s rg %.2F %.2F Td (%s) Tj ET',
            $bold ? 'F2' : 'F1', $size, self::rgb($color), $x, self::H - $y, self::esc($txt)));
    }

    /** Rough Helvetica width estimate, good enough for wrapping. */
    public static function textWidth($txt, $size) {
        return strlen((string)$txt) * $size * 0.5;
    }

    /** Writes wrapped text, returns the new Y below the last line. */
    public function wrapText($x, $y, $maxW, $txt, $size = 9, $bold = false, $color = [25, 32, 46], $lineH = null) {
        $lineH = $lineH ?: $size * 1.3;
        $words = preg_split('/\s+/', trim((string)$txt));
        $line = '';
        foreach ($words as $w) {
            $try = $line === '' ? $w : "$line $w";
            if (self::textWidth($try, $size) > $maxW && $line !== '') {
                $this->text($x, $y, $line, $size, $bold, $color);
                $y += $lineH;
                $line = $w;
            } else {
                $line = $try;
            }
        }
        if ($line !== '') {
            $this->text($x, $y, $line, $size, $bold, $color);
            $y += $lineH;
        }
        return $y;
    }

    public function rect($x, $y, $w, $h, $fill = null, $stroke = null, $lineW = 0.6) {
        $py = self::H - $y - $h;
        if ($fill) $this->op(self::rgb($fill) . ' rg');
        if ($stroke) $this->op(self::rgb($stroke) . ' RG ' . sprintf('%.2F w', $lineW));
        $mode = $fill && $stroke ? 'B' : ($fill ? 'f' : 'S');
        $this->op(sprintf('%.2F %.2F %.2F %.2F re %s', $x, $py, $w, $h, $mode));
    }

    public function line($x1, $y1, $x2, $y2, $color = [226, 220, 212], $w = 0.6) {
        $this->op(self::rgb($color) . ' RG ' . sprintf('%.2F w %.2F %.2F m %.2F %.2F l S',
            $w, $x1, self::H - $y1, $x2, self::H - $y2));
    }

    /**
     * Draws an image fitted (aspect preserved) inside the box. Returns false if unreadable.
     */
    public function image($path, $x, $y, $boxW, $boxH) {
        if (!$path || !is_file($path)) return false;
        $info = @getimagesize($path);
        if (!$info) return false;

        $data = null;
        $channels = $info['channels'] ?? 3;
        if ($info[2] === IMAGETYPE_JPEG && $channels !== 4) {
            $data = file_get_contents($path);
        } elseif (function_exists('imagecreatefromstring')) {
            $im = @imagecreatefromstring(file_get_contents($path));
            if (!$im) return false;
            // Flatten transparency onto white
            $bg = imagecreatetruecolor(imagesx($im), imagesy($im));
            imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
            imagecopy($bg, $im, 0, 0, 0, 0, imagesx($im), imagesy($im));
            ob_start();
            imagejpeg($bg, null, 85);
            $data = ob_get_clean();
            imagedestroy($im);
            imagedestroy($bg);
            $channels = 3;
        }
        if (!$data) return false;

        $name = 'Im' . (count($this->images) + 1);
        $this->images[$name] = [
            'data' => $data, 'w' => $info[0], 'h' => $info[1],
            'cs' => $channels === 1 ? '/DeviceGray' : '/DeviceRGB'
        ];
        if ($this->cur < 0) $this->addPage();
        $this->pages[$this->cur]['imgs'][] = $name;

        $scale = min($boxW / $info[0], $boxH / $info[1]);
        $w = $info[0] * $scale;
        $h = $info[1] * $scale;
        $dx = $x + ($boxW - $w) / 2;
        $dy = $y + ($boxH - $h) / 2;
        $this->op(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q', $w, $h, $dx, self::H - $dy - $h, $name));
        return true;
    }

    public function output() {
        if (empty($this->pages)) $this->addPage();
        $objs = [];
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objs[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $next = 5;

        $imgIds = [];
        foreach ($this->images as $name => $img) {
            $imgIds[$name] = $next;
            $objs[$next++] = sprintf("<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace %s /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n%s\nendstream",
                $img['w'], $img['h'], $img['cs'], strlen($img['data']), $img['data']);
        }

        $kids = [];
        foreach ($this->pages as $p) {
            $cid = $next++;
            $objs[$cid] = sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($p['ops']), $p['ops']);
            $xo = '';
            foreach (array_unique($p['imgs']) as $n) $xo .= "/$n {$imgIds[$n]} 0 R ";
            $pid = $next++;
            $objs[$pid] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> /XObject << %s>> >> /Contents %d 0 R >>',
                self::W, self::H, $xo, $cid);
            $kids[] = "$pid 0 R";
        }
        $objs[2] = sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' ', $kids), count($kids));

        ksort($objs);
        $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objs as $id => $body) {
            $offsets[$id] = strlen($out);
            $out .= "$id 0 obj\n$body\nendobj\n";
        }
        $xref = strlen($out);
        $max = max(array_keys($objs));
        $out .= "xref\n0 " . ($max + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $out .= isset($offsets[$i]) ? sprintf("%010d 00000 n \n", $offsets[$i]) : "0000000000 65535 f \n";
        }
        $out .= "trailer\n<< /Size " . ($max + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        return $out;
    }
}
