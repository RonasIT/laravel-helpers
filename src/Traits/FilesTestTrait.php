<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * @deprecated
 */
trait FilesTestTrait
{
    use FilesTrait;

    public function checkFile($uploadedFileName, $fixturePath)
    {
        return $this->assertEqualsFixture($fixturePath, Storage::get($uploadedFileName));
    }

    public function clearFolder()
    {
        $files = Storage::allFiles();

        if (!empty($files)) {
            $this->deleteFiles($files);
        }
    }

    public function getFilePathFromUrl($url)
    {
        $explodedUrl = explode('/', $url);

        return last($explodedUrl);
    }

    protected function deleteFiles($files)
    {
        foreach ($files as $file) {
            Storage::delete($file);
        }
    }
}
