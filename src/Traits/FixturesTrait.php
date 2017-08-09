<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 10.10.16
 * Time: 14:56
 */

namespace RonasIT\Support\Traits;

use Illuminate\Support\Facades\DB;

trait FixturesTrait
{
    protected function loadTestDump()
    {
        $dump = $this->getFixture('dump.sql');

        if (!empty($dump)) {
            DB::unprepared($dump);
        }
    }

    public function getFixturePath($fn)
    {
        $class = get_class($this);
        $explodedClass = explode('\\', $class);
        $className = array_last($explodedClass);

        return base_path("tests/fixtures/{$className}/{$fn}");
    }

    public function getFixture($fn)
    {
        $path = $this->getFixturePath($fn);

        if (!file_exists($path)) {
            return null;
        }

        return file_get_contents($path);
    }

    public function getJsonFixture($fn, $assoc = true)
    {
        return json_decode($this->getFixture($fn), $assoc);
    }

    public function getJsonResponse()
    {
        $response = $this->response->getContent();

        return json_decode($response, true);
    }

    public function assertEqualsFixture($fixture, $data)
    {
        $this->assertEquals($this->getJsonFixture($fixture), $data);
    }

    public function exportJsonResponse($fixture)
    {
        $response = $this->getJsonResponse();
        $content = json_encode($response, JSON_PRETTY_PRINT);

        return file_put_contents($this->getFixturePath($fixture), $content);
    }
}