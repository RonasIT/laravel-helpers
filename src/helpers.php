<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Round all values in list of floats.
 *
 * @param array $array
 * @return array
 */
function array_round(array $array): array
{
    $keys = array_keys($array);

    $values = array_map(function ($value) {
        if (is_numeric($value)) {
            return round($value);
        }

        return $value;
    }, $array);

    return array_combine($keys, $values);
}

/**
 * Get list of element which placed in $path in $array
 *
 * @param array|string|null $array
 * @param array|string $path
 *
 * @return mixed
 */
function array_get_list(array|string|null $array, array|string $path): mixed
{
    if (is_string($path)) {
        $path = explode('.', $path);
    }

    $key = array_shift($path);

    if (empty($path)) {
        return ($key === '*') ? $array : Arr::get($array, $key);
    }

    if ($key === '*') {
        if (empty($array)) {
            return [];
        }

        $values = array_map(function ($item) use ($path) {
            $value = array_get_list($item, $path);

            if (!is_array($value) || Arr::isAssoc($value)) {
                return [$value];
            }

            return $value;
        }, $array);

        return Arr::collapse($values);
    } else {
        $value = Arr::get($array, $key);

        return array_get_list($value, $path);
    }
}

/**
 * Verifies whether input is array or arrays or not
 *
 * @param array $array
 *
 * @return boolean
 */
function is_multidimensional(array $array): bool
{
    return is_array(Arr::first($array));
}

/**
 * Create directory recursively. The native mkdir() function recursively create directory incorrectly.
 * This is solution.
 *
 * @param string $path
 */
function mkdir_recursively(string $path): void
{
    $currentPath = getcwd();

    // handling Windows paths
    if (DIRECTORY_SEPARATOR === '\\') {
        // @codeCoverageIgnoreStart
        $currentPath = str_replace(DIRECTORY_SEPARATOR, '/', $currentPath);
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        // @codeCoverageIgnoreEnd
    }

    $path = Str::replaceFirst($currentPath, '', $path);
    $explodedPath = explode('/', $path);

    array_walk($explodedPath, function ($dir) use (&$currentPath) {
        if ($currentPath !== '/') {
            $currentPath .= '/' . $dir;
        } else {
            // @codeCoverageIgnoreStart
            $currentPath .= $dir;
            // @codeCoverageIgnoreEnd
        }

        if (!file_exists($currentPath)) {
            mkdir($currentPath);
        }
    });
}

/**
 * Check equivalency of two arrays
 *
 * @param array $array1
 * @param array $array2
 *
 * @return boolean
 */
function array_equals(array $array1, array $array2): bool
{
    if (Arr::isAssoc($array1)) {
        return array_equals_assoc($array1, $array2);
    }

    $array1 = (new Collection($array1))->sort()->values()->toArray();
    $array2 = (new Collection($array2))->sort()->values()->toArray();

    return $array1 === $array2;
}

/**
 * Check equivalency of two associative arrays
 *
 * @param array $array1
 * @param array $array2
 *
 * @return boolean
 */
function array_equals_assoc(array $array1, array $array2): bool
{
    $array1 = (new Collection($array1))->sortKeys()->toArray();
    $array2 = (new Collection($array2))->sortKeys()->toArray();

    return $array1 === $array2;
}

/**
 * Return subtraction of two arrays
 *
 * @param array $array1
 * @param array $array2
 *
 * @return array
 */
function array_subtraction(array $array1, array $array2): array
{
    $intersection = array_intersect($array1, $array2);

    return array_diff($array1, $intersection);
}

/**
 * Generate GUID
 *
 * @return string
 *
 * @codeCoverageIgnore
 */
function getGUID(): string
{
    mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
    $charId = strtoupper(md5(uniqid(rand(), true)));
    $hyphen = chr(45);// "-"

    return chr(123)// "{"
        . substr($charId, 0, 8) . $hyphen
        . substr($charId, 8, 4) . $hyphen
        . substr($charId, 12, 4) . $hyphen
        . substr($charId, 16, 4) . $hyphen
        . substr($charId, 20, 12)
        . chr(125);// "}"
}

function array_concat(array $array, callable $callback): string
{
    $content = '';

    foreach ($array as $key => $value) {
        $content .= $callback($value, $key);
    }

    return $content;
}

function rmdir_recursively(string $dir): void
{
    if ($objs = glob($dir . "/*")) {
        foreach ($objs as $obj) {
            is_dir($obj) ? rmdir_recursively($obj) : unlink($obj);
        }
    }
    rmdir($dir);
}

function fPutQuotedCsv($handle, array $row, string $fd = ',', string $quot = '"'): int
{
    $cells = array_map(function ($cell) use ($quot) {
        if (preg_match("/[;.\",\n]/", $cell)) {
            $cell = $quot . str_replace($quot, "{$quot}{$quot}", $cell) . $quot;
        }

        return $cell;
    }, $row);

    $str = implode($fd, $cells);

    fputs($handle, $str . "\n");

    return strlen($str);
}

function clear_folder(string $path): void
{
    $files = glob("$path/*");

    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }

        if (is_dir($file)) {
            clear_folder($file);
        }
    }
}

/**
 * Builds an associative array by gotten keys and values
 *
 * @param array $array
 * @param callable $callback - should return associate array with "key" and "value" keys
 *
 * @example $callback
 *  function ($value, $key) {
 *      return [
 *        'key' => $key,
 *        'value' => $value,
 *      ];
 *  }
 *
 * @return array
 *
 * @deprecated Use array_walk, forEach or mapWithKeys instead
 */
function array_associate(array $array, callable $callback): array
{
    $result = [];

    foreach ($array as $key => $value) {
        $callbackResult = $callback($value, $key);

        if (!empty($callbackResult)) {
            $result[$callbackResult['key']] = $callbackResult['value'];
        }
    }

    return $result;
}

/**
 * Get duplicate values of array
 *
 * @param array $array
 *
 * @return array
 */
function array_get_duplicates(array $array): array
{
    return array_diff_key($array, array_unique($array));
}

/**
 * Get only unique objects from array by key (array of keys) or by closure
 *
 * @param array $objectsList
 * @param string|callable|array $filter
 *
 * @return array
 */
function array_unique_objects(array $objectsList, string|callable|array $filter = 'id'): array
{
    $uniqueKeys = [];

    $uniqueObjects = array_map(function ($object) use (&$uniqueKeys, $filter) {
        if (is_string($filter)) {
            $value = $object[$filter];
        }

        if (is_callable($filter)) {
            $value = $filter($object);
        }

        if (is_array($filter)) {
            $value = Arr::only($object, $filter);
        }

        if (in_array($value, $uniqueKeys)) {
            return null;
        }
        $uniqueKeys[] = $value;

        return $object;
    }, $objectsList);

    return array_filter($uniqueObjects, function ($item) {
        return !is_null($item);
    });
}

function array_trim(array $array): array
{
    return array_map(
        function ($item) {
            return (is_string($item)) ? trim($item) : $item;
        },
        $array
    );
}

function array_remove_by_field(array $array, string|int $fieldName, mixed $fieldValue): array
{
    $array = array_values($array);
    $key = array_search($fieldValue, array_column($array, $fieldName));
    if ($key !== false) {
        unset($array[$key]);
    }

    return array_values($array);
}

function array_remove_elements(array $array, array $elements): array
{
    return array_diff($array, $elements);
}

/**
 * @deprecated use str_pad and mb_str_pad instead
 * @codeCoverageIgnore
 */
function prepend_symbols($string, $expectedLength, $symbol)
{
    while (strlen($string) < $expectedLength) {
        $string = "{$symbol}{$string}";
    }

    return $string;
}

function array_default(array &$array, string|int $key, mixed $default): void
{
    $array[$key] = Arr::get($array, $key, $default);
}

/**
 * inverse transformation from array_dot
 * @param $array
 * @return array
 */
function array_undot(array $array): array
{
    $result = [];

    foreach ($array as $key => $value) {
        Arr::set($result, $key, $value);
    }

    return $result;
}

function extract_last_part(string $string, string $separator = '.'): array
{
    $entities = explode($separator, $string);

    $fieldName = array_pop($entities);

    $relation = implode($separator, $entities);

    return [$fieldName, $relation];
}
