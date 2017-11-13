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

        return last($explodedUrl);
    }

    public function clearFolder()
    {
        $files = Storage::allFiles();

        if (!empty($files)) {
            $this->deleteFiles($files);
        }
    }

    public function checkImage($uploadedFileName, $fixturePath)
    {
        return $this->assertEquals(
            file_get_contents($fixturePath),
            Storage::get($uploadedFileName)
        );
    }

    protected function deleteFiles($files)
    {
        foreach ($files as $file) {
            Storage::delete($file);
        }
    }
}