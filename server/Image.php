<?php
declare(strict_types=1);
namespace Threadroom;

final class Image
{
    public const MAX_BYTES = 10000000;

    /** Strip all EXIF, comments, profiles, text, trailing bytes, and nonessential metadata without GD. */
    public static function sanitize(string $bytes, bool $jpegOnly = false): array
    {
        if (strlen($bytes) < 32 || strlen($bytes) >= self::MAX_BYTES) {
            throw new ApiError(422, 'invalid_image', 'Use a JPEG or PNG smaller than 10 MB.');
        }
        $info = @getimagesizefromstring($bytes);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (!$info || !in_array($mime, ['image/jpeg', 'image/png'], true) || ($info['mime'] ?? '') !== $mime) {
            throw new ApiError(422, 'invalid_image', 'The file is not a supported image.');
        }
        if ($info[0] < 384 || $info[1] < 384 || max($info[0], $info[1]) < 512 || max($info[0], $info[1]) > 4096) {
            throw new ApiError(422, 'invalid_dimensions', 'Use an image at least 512 by 384 pixels with no side above 4096.');
        }
        if ($jpegOnly && $mime !== 'image/jpeg') {
            throw new ApiError(502, 'unsupported_result', 'The provider returned an unsupported image format.');
        }
        $clean = $mime === 'image/jpeg' ? self::jpeg($bytes) : self::png($bytes);
        $after = @getimagesizefromstring($clean);
        if (!$after || $after[0] !== $info[0] || $after[1] !== $info[1]) {
            throw new ApiError(422, 'invalid_image', 'The image structure could not be validated.');
        }
        return ['bytes' => $clean, 'mime' => $mime, 'width' => $info[0], 'height' => $info[1]];
    }

    private static function invalid(): never
    {
        throw new ApiError(422, 'invalid_image', 'The image structure could not be validated.');
    }

    private static function jpeg(string $s): string
    {
        if (substr($s, 0, 2) !== "\xFF\xD8") self::invalid();
        $out = "\xFF\xD8";
        $i = 2; $n = strlen($s); $sawScan = false;
        while ($i < $n) {
            $start = $i;
            if (ord($s[$i]) !== 255) self::invalid();
            while ($i < $n && ord($s[$i]) === 255) $i++;
            if ($i >= $n) self::invalid();
            $marker = ord($s[$i++]);
            if ($marker === 217) {
                if (!$sawScan) self::invalid();
                return $out . "\xFF\xD9"; // Deliberately discard anything after EOI.
            }
            if ($marker === 0 || $marker === 216 || ($marker >= 208 && $marker <= 215) || $i + 2 > $n) self::invalid();
            $length = unpack('n', substr($s, $i, 2))[1];
            if ($length < 2 || $i + $length > $n) self::invalid();
            $segment = "\xFF" . chr($marker) . substr($s, $i, $length);
            $i += $length;
            if (!(($marker >= 224 && $marker <= 239) || $marker === 254)) $out .= $segment;
            if ($marker !== 218) continue;
            $sawScan = true;
            $scanStart = $i;
            while ($i < $n) {
                if (ord($s[$i]) !== 255) { $i++; continue; }
                $m = $i;
                while ($m < $n && ord($s[$m]) === 255) $m++;
                if ($m >= $n) self::invalid();
                $next = ord($s[$m]);
                if ($next === 0 || ($next >= 208 && $next <= 215)) { $i = $m + 1; continue; }
                break;
            }
            $out .= substr($s, $scanStart, $i - $scanStart);
        }
        self::invalid();
    }

    private static function png(string $s): string
    {
        $signature = "\x89PNG\r\n\x1A\n";
        if (substr($s, 0, 8) !== $signature) self::invalid();
        $out = $signature; $i = 8; $n = strlen($s); $hasHeader = false; $hasData = false;
        while ($i + 12 <= $n) {
            $length = unpack('N', substr($s, $i, 4))[1];
            if ($length > self::MAX_BYTES || $i + $length + 12 > $n) self::invalid();
            $type = substr($s, $i + 4, 4);
            $data = substr($s, $i + 8, $length);
            $crc = substr($s, $i + 8 + $length, 4);
            if (!preg_match('/^[A-Za-z]{4}$/D', $type) || !hash_equals(hash('crc32b', $type . $data, true), $crc)) self::invalid();
            if (!$hasHeader && $type !== 'IHDR') self::invalid();
            if ($type === 'IHDR') { if ($hasHeader || $length !== 13) self::invalid(); $hasHeader = true; }
            if ($type === 'IDAT') $hasData = true;
            if (in_array($type, ['acTL', 'fcTL', 'fdAT'], true)) self::invalid(); // No animated PNG.
            $keep = in_array($type, ['IHDR', 'PLTE', 'IDAT', 'IEND', 'tRNS'], true);
            if (!$keep && ctype_upper($type[0])) self::invalid(); // Unknown critical chunk.
            if ($keep) $out .= substr($s, $i, $length + 12);
            $i += $length + 12;
            if ($type === 'IEND') {
                if ($length !== 0 || !$hasData) self::invalid();
                return $out;
            }
        }
        self::invalid();
    }
}
