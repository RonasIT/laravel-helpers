<?php

namespace RonasIT\Support\Traits;

/**
 * @deprecated
 */
trait ImageTestTrait
{
    use ImagesTrait;

    protected function clearPublicImages($folder)
    {
        $imagesPath = $this->uploadPath($folder);

        if (!file_exists($imagesPath)) {
            mkdir_recursively($imagesPath);
        }

        $images = scandir($imagesPath);

        array_map(function ($image) use ($folder) {
            $except = ['.', '..'];

            if (!in_array($image, $except)) {
                $imagePath = $this->uploadPath("{$folder}/{$image}");

                unlink($imagePath);
            }
        }, $images);
    }

    protected function getEncryptedImage($imageName)
    {
        $image = $this->getFixture($imageName);

        return base64_encode($image);
    }

    protected function checkImage($expectedFixture, $imagePath)
    {
        $imagePath = preg_replace('/^\//', '', $imagePath);

        $imagePath = $this->uploadPath($imagePath);

        $this->assertEquals(
            $this->getFixture($expectedFixture),
            file_get_contents($imagePath)
        );
    }

    protected function copyImage($imageName, $destination)
    {
        if (!file_exists($this->uploadPath('flags'))) {
            mkdir_recursively($this->uploadPath('flags'));
        }

        copy(
            $this->getFixturePath($imageName),
            $this->uploadPath($destination)
        );
    }

    protected function getImageRoute($imagePath)
    {
        $route = config('imagecache.route');
        $templates = config('imagecache.templates');
        $templatesKeys = array_keys($templates);

        return "/{$route}/{$templatesKeys[0]}/{$imagePath}";
    }
}