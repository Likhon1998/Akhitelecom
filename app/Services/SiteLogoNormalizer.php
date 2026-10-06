<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SiteLogoNormalizer
{
    private const MAX_SOURCE_PIXELS = 40_000_000;

    /** GD alpha runs 0 (opaque) to 127 (fully transparent). */
    private const TRANSPARENT_ALPHA = 120;

    /**
     * Store an uploaded logo/favicon as a cropped transparent PNG.
     *
     * @param  'logo'|'favicon'  $kind
     */
    public function storeProcessed(UploadedFile $file, string $kind = 'logo'): string
    {
        $folder = $kind === 'favicon' ? 'cms/favicon' : 'cms/logo';
        $tmp = $file->getRealPath();
        if (! $tmp || ! is_file($tmp)) {
            throw new RuntimeException('Uploaded image could not be read.');
        }

        $filename = Str::uuid()->toString().'.png';
        $relative = $folder.'/'.$filename;
        Storage::disk('public')->makeDirectory($folder);
        $absolute = Storage::disk('public')->path($relative);

        $this->normalizeToPng(
            $tmp,
            $absolute,
            square: $kind === 'favicon',
            padding: $kind === 'favicon' ? 6 : 12,
            threshold: 42,
            maxSize: $kind === 'favicon' ? 512 : 1000,
        );

        return $relative;
    }

    public function normalizeToPng(
        string $sourcePath,
        string $destPath,
        bool $square = false,
        int $padding = 12,
        int $threshold = 42,
        int $maxSize = 512,
    ): void {
        if (! function_exists('imagecreatetruecolor')) {
            if (! @copy($sourcePath, $destPath)) {
                throw new RuntimeException('GD is not available and logo copy failed.');
            }

            return;
        }

        $data = @file_get_contents($sourcePath);
        if ($data === false) {
            throw new RuntimeException('Unable to read uploaded logo.');
        }

        $size = @getimagesizefromstring($data);
        if ($size && ($size[0] * $size[1]) > self::MAX_SOURCE_PIXELS) {
            throw new RuntimeException('Logo image dimensions are too large.');
        }

        $this->raiseMemoryLimit();

        $im = @imagecreatefromstring($data);
        unset($data);
        if (! $im instanceof \GdImage) {
            throw new RuntimeException('Unsupported logo image format.');
        }

        if (! imageistruecolor($im)) {
            imagepalettetotruecolor($im);
        }
        imagealphablending($im, false);
        imagesavealpha($im, true);

        // Scanning is per-pixel PHP, so bound the work before cropping.
        $im = $this->fitWithin($im, $maxSize * 2);

        try {
            $w = imagesx($im);
            $h = imagesy($im);
            $hasTransparency = false;
            $opaque = [$w, $h, -1, -1];
            $colored = [$w, $h, -1, -1];

            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    $rgba = imagecolorat($im, $x, $y);
                    if ((($rgba >> 24) & 0x7F) >= self::TRANSPARENT_ALPHA) {
                        $hasTransparency = true;
                        continue;
                    }
                    $this->growBounds($opaque, $x, $y);

                    if (! $this->isCanvasPixel(($rgba >> 16) & 0xFF, ($rgba >> 8) & 0xFF, $rgba & 0xFF, $threshold)) {
                        $this->growBounds($colored, $x, $y);
                    }
                }
            }

            // Transparent PNG: the transparency is the background, keep every visible pixel (incl. black logos).
            // Opaque image: treat near-black / near-white as empty canvas.
            $colorKey = ! $hasTransparency && $colored[2] >= 0;
            [$minX, $minY, $maxX, $maxY] = $hasTransparency ? $opaque : $colored;

            if ($maxX < 0) {
                [$minX, $minY, $maxX, $maxY] = [0, 0, $w - 1, $h - 1];
            }

            $minX = max(0, $minX - $padding);
            $minY = max(0, $minY - $padding);
            $maxX = min($w - 1, $maxX + $padding);
            $maxY = min($h - 1, $maxY + $padding);
            $cw = $maxX - $minX + 1;
            $ch = $maxY - $minY + 1;

            $outW = $square ? max($cw, $ch) : $cw;
            $outH = $square ? $outW : $ch;
            $ox = (int) (($outW - $cw) / 2);
            $oy = (int) (($outH - $ch) / 2);

            $out = $this->transparentCanvas($outW, $outH);

            if ($colorKey) {
                $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
                for ($y = 0; $y < $ch; $y++) {
                    for ($x = 0; $x < $cw; $x++) {
                        $rgb = imagecolorat($im, $minX + $x, $minY + $y);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = $rgb & 0xFF;

                        if ($this->isCanvasPixel($r, $g, $b, $threshold)) {
                            imagesetpixel($out, $ox + $x, $oy + $y, $transparent);
                            continue;
                        }

                        imagesetpixel($out, $ox + $x, $oy + $y, imagecolorallocatealpha($out, $r, $g, $b, 0));
                    }
                }
            } else {
                imagecopy($out, $im, $ox, $oy, $minX, $minY, $cw, $ch);
            }

            $out = $this->fitWithin($out, $maxSize);

            if (! imagepng($out, $destPath, 6)) {
                throw new RuntimeException('Failed to save processed logo.');
            }
            imagedestroy($out);
        } catch (Throwable $e) {
            imagedestroy($im);
            throw $e;
        }

        imagedestroy($im);
    }

    /** @param  array{0:int,1:int,2:int,3:int}  $bounds  minX, minY, maxX, maxY */
    private function growBounds(array &$bounds, int $x, int $y): void
    {
        $bounds[0] = min($bounds[0], $x);
        $bounds[1] = min($bounds[1], $y);
        $bounds[2] = max($bounds[2], $x);
        $bounds[3] = max($bounds[3], $y);
    }

    private function transparentCanvas(int $w, int $h): \GdImage
    {
        $canvas = imagecreatetruecolor($w, $h);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefilledrectangle($canvas, 0, 0, $w - 1, $h - 1, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        return $canvas;
    }

    /** Downscale (never upscale) so neither side exceeds $limit. */
    private function fitWithin(\GdImage $im, int $limit): \GdImage
    {
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w <= $limit && $h <= $limit) {
            return $im;
        }

        $scale = min($limit / $w, $limit / $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $scaled = $this->transparentCanvas($nw, $nh);
        imagecopyresampled($scaled, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($im);

        return $scaled;
    }

    /** Phone photos decode to 50MB+ in GD; shared hosts often default to 128M. */
    private function raiseMemoryLimit(): void
    {
        $limit = trim((string) ini_get('memory_limit'));
        if ($limit === '' || $limit === '-1') {
            return;
        }

        $bytes = (int) $limit;
        $unit = strtolower(substr($limit, -1));
        $bytes *= match ($unit) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        if ($bytes < 512 * 1024 ** 2) {
            @ini_set('memory_limit', '512M');
        }
    }

    private function isCanvasPixel(int $r, int $g, int $b, int $threshold): bool
    {
        // Solid black / near-black backgrounds from AI exports.
        if ($r <= $threshold && $g <= $threshold && $b <= $threshold) {
            return true;
        }

        // Near-white empty padding some exports include.
        if ($r >= 250 && $g >= 250 && $b >= 250) {
            return true;
        }

        return false;
    }
}
