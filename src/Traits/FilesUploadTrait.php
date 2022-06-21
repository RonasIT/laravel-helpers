<?php

namespace RonasIT\Support\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait FilesUploadTrait
{
    protected function saveFile($fileName, $content): string
    {
        $preparedName = $this->generateName($fileName);

        Storage::put($preparedName, $content);

        return $preparedName;
    }

    protected function checkUploadedFile($path): bool
    {
        return Storage::exists($path);
    }

    protected function getFileForUploading($path)
    {
        return new UploadedFile($path, $this->generateName($path));
    }

    protected function clearUploadedFilesFolder()
    {
        $files = Storage::allFiles();

        foreach ($files as $file) {
            Storage::delete($file);
        }
    }

    protected function generateName($path): string
    {
        $name = basename($path);
        $explodedName = explode('.', $name);
        $extension = array_pop($explodedName);
        $hash = md5(uniqid());
        $timestamp = str_replace(['.', ' '], '_', microtime());

        return "{$timestamp}_{$hash}.{$extension}";
    }

    public function getFilePathFromUrl($url)
    {
        $explodedUrl = explode('/', $url);

        return last($explodedUrl);
    }
}
