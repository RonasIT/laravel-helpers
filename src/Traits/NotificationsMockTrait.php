<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Facades\Notification;
use ReflectionClass;

trait NotificationsMockTrait
{
    protected function assertNotificationsSent(
        string $fixture,
        array $options = [],
        bool $exportMode = false,
    ): void {
        $actualData = [];

        foreach (Notification::sentNotifications() as $notifiableIDs) {
            foreach ($notifiableIDs as $modelNotifications) {
                foreach ($modelNotifications as $notificationClassName => $modelNotification) {
                    foreach ($modelNotification as $notification) {
                        $actualData[$notificationClassName][] = $this->prepareNotification($notification, $options);
                    }
                }
            }
        }

        $preparedActualData = json_decode(json_encode($actualData), true);

        $this->assertEqualsFixture($fixture, $preparedActualData, $exportMode);
    }

    protected function prepareNotification(array $notification, array $options): array
    {
        foreach ($options as $key => $chain) {
            $notification[$key] = $this->resolveNotificationChain($notification['notification'], $chain);
        }

        $attributes = $this->getNotificationAttributes($notification['notification']);

        $notification['notification'] = $attributes;

        return $notification;
    }

    protected function resolveNotificationChain(object $notification, array $chain): mixed
    {
        $value = $notification;

        foreach ($chain as $step) {
            if (str_ends_with($step, '()') && method_exists($value, rtrim($step, '()'))) {
                $value = $value->{rtrim($step, '()')}();
            } elseif (property_exists($value, $step)) {
                $value = $value->$step;
            } else {
                return null;
            }
        }

        return $value;
    }

    protected function getNotificationAttributes(object $notification): array
    {
        $reflection = new ReflectionClass($notification);
        $attributes = [];

        foreach ($reflection->getProperties() as $property) {
            if (str_starts_with($property->getDeclaringClass()->getName(), 'Illuminate\\')) {
                continue;
            }

            $attributes[$property->getName()] = $property->getValue($notification);
        }

        return $attributes;
    }
}
