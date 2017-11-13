<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 10.10.16
 * Time: 14:56
 */

namespace RonasIT\Support\Traits;

use Illuminate\Support\Facades\Storage;

trait FilesTestTrait
{
    use FilesTrait;

    public function getFilePathFromUrl($url)
    {
        $explodedUrl = explode('/', $url);

        $fileName = last($explodedUrl);
        $folder = $this->getStorageFolder();

        return "{$folder}/{$fileName}";
    }

    public function clearFolder()
    {
        Storage::deleteDirectory($this->getStorageFolder());
    }

}