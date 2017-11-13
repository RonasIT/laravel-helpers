<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 30.09.16
 * Time: 18:16
 */

namespace RonasIT\Support\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

trait FilesTrait
{
    public function saveFile($name, $content, $returnUrl = false)
    {
        $preparedName = $this->prepareName($name);

        $folder = $this->getStorageFolder();

        $path = "{$folder}/{$preparedName}";

        Storage::put($path, $content);

        return $returnUrl ? Storage::url($path) : Storage::path($path);
    }

    public function removeFileByUrl($url)
    {
        $fileName = $this->getFileNameFromUrl($url);

        $folder = $this->getStorageFolder();

        $path = "{$folder}/{$fileName}";

        Storage::delete($path);
    }

    public function removeFileByPath($path)
    {
        Storage::delete($path);
    }

    public function removeFileByName($name)
    {
        $folder = $this->getStorageFolder();

        $path = "{$folder}/{$name}";

        Storage::delete($path);
    }

    protected function getStorageFolder()
    {
        return (env('APP_ENV') == 'testing') ? config('defaults.upload.test') : config('defaults.upload.prod');
    }

    protected function prepareName($name)
    {
        $explodedName = explode('.', $name);
        $extension = array_pop($explodedName);
        $name = implode('_', $explodedName);
        $timestamp = Carbon::now()->timestamp;

        return "{$name}_{$timestamp}.{$extension}";
    }

    protected function getFileNameFromUrl($url)
    {
        $explodedUrl = explode('/', $url);

        return last($explodedUrl);
    }
}