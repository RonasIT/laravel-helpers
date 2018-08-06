<?php

namespace RonasIT\Support\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

trait FilesTrait
{
    public function saveFile($name, $content, $returnUrl = false)
    {
        $preparedName = $this->prepareName($name);

        Storage::put($preparedName, $content);

        return $returnUrl ? Storage::url($preparedName) : Storage::path($preparedName);
    }

    public function removeFileByUrl($url)
    {
        $fileName = $this->getFileNameFromUrl($url);

        $this->removeFileByName($fileName);
    }

    public function removeFileByName($name)
    {
        Storage::delete($name);
    }

    protected function prepareName($name)
    {
        $explodedName = explode('.', $name);
        $extension = array_pop($explodedName);
        $timestamp = Carbon::now()->timestamp;
        $hash = md5($name);

        return "{$hash}_{$timestamp}.{$extension}";
    }

    protected function getFileNameFromUrl($url)
    {
        $explodedUrl = explode('/', $url);

        return last($explodedUrl);
    }
}