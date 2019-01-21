<?php

namespace RonasIT\Support\Traits;

/**
 * @deprecated
 */
trait ImagesTrait
{
    public function saveImage($folder, $name, $image)
    {
        $this->prepareImageFolder($folder);

        $imagePath = "/{$folder}/{$name}";

        $image = base64_decode($image);

        file_put_contents($this->uploadPath($imagePath), $image);

        return $imagePath;
    }

    protected function getUploadFolder()
    {
        if (env('APP_ENV') == 'testing') {
            $dir = config('defaults.upload.test');

            if (!file_exists($dir)) {
                mkdir($dir);
            }

            return $dir;
        }

        return config('defaults.upload.prod');
    }

    protected function uploadPath($folder)
    {
        return "{$this->getUploadFolder()}/{$folder}";
    }

    protected function prepareImageFolder($folder)
    {
        $folderPath = $this->uploadPath($folder);

        mkdir_recursively($folderPath);
    }

    protected function removeImage($imagePath)
    {
        $imagePath = $this->uploadPath($imagePath);

        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
}