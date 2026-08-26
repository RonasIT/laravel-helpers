<?php

namespace RonasIT\Support\Traits;

use ReflectionClass;

trait ReflectionTrait
{
    protected function getObjectAttributes(object $object): array
    {
        $reflection = new ReflectionClass($object);
        $attributes = [];

        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic() || !$property->isInitialized($object)) {
                continue;
            }

            $attributes[$property->getName()] = $property->getValue($object);
        }

        return $attributes;
    }
}
