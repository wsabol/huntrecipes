<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class ProfilePhotoService {
    /** @return array{string, string} Small and large public-disk paths. */
    public function store(UploadedFile $file): array {
        $source = imagecreatefromstring($file->get());
        if ($source === false) {
            throw new RuntimeException('Unable to decode profile photo.');
        }
        $base = 'users/' . Str::uuid();
        $paths = [$base . '.jpg', $base . '-lg.jpg'];
        $disk = Storage::disk('public');
        try {
            $width = imagesx($source);
            $height = imagesy($source);
            $side = min($width, $height);
            foreach ([96, 608] as $index => $size) {
                $image = imagecreatetruecolor($size, $size);
                imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
                imagecopyresampled($image, $source, 0, 0, (int) (($width - $side) / 2), (int) (($height - $side) / 2), $size, $size, $side, $side);
                ob_start();
                imagejpeg($image, null, 90);
                $bytes = ob_get_clean();
                imagedestroy($image);
                if (!$disk->put($paths[$index], $bytes)) {
                    throw new RuntimeException('Unable to store profile photo.');
                }
            }
        } catch (Throwable $exception) {
            $disk->delete($paths);
            throw $exception;
        } finally {
            imagedestroy($source);
        }

        return $paths;
    }
}
