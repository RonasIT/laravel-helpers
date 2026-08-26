<?php

namespace RonasIT\Support\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use JsonException;
use ReflectionMethod;

trait NotificationsMockTrait
{
    use FixturesTrait;
    use ReflectionTrait;

    protected const array RESERVED_NOTIFICATION_FIELDS = ['notification', 'channels', 'notifiable', 'locale'];

    /**
     * Asserts the notifications sent during the test against the $fixture.
     *
     * $options should look like the following construction:
     *   [
     *      'field_name' => ['step1', 'step2', ...],
     *   ]
     *
     * It adds a field to every fixture entry, resolving it by a chain of steps on the notification:
     *   [
     *      'message' => ['toExpoPush()', 'toArray()'],    // $notification->toExpoPush($notifiable)->toArray($notifiable)
     *      'broadcast_data' => ['toBroadcast()', 'data'], // $notification->toBroadcast($notifiable)->data
     *   ]
     *
     * A step ending with '()' is a method call, any other step is a property access. Both are applied
     * to the notification on the first step and to the result of the previous one afterwards. The
     * notifiable is passed as the first argument to every method that accepts at least one parameter.
     * An unresolvable step fails the test.
     *
     * Field names must not collide with {@see self::RESERVED_NOTIFICATION_FIELDS}.
     *
     * @see documentation/traits.md#notificationsmocktrait
     *
     * @param  array<string, string[]>  $options
     */
    protected function assertNotificationsSent(string $fixture, array $options = [], bool $exportMode = false): void
    {
        $this->validateNotificationOptions($options);

        $actualData = [];

        foreach (Notification::sentNotifications() as $notificationsByNotifiable) {
            foreach ($notificationsByNotifiable as $notificationsByClass) {
                foreach ($notificationsByClass as $notificationClass => $notifications) {
                    foreach ($notifications as $notification) {
                        $actualData[$notificationClass][] = $this->prepareNotificationFixtureData($notification, $options);
                    }
                }
            }
        }

        try {
            $preparedData = json_decode(
                json: json_encode($actualData, JSON_THROW_ON_ERROR),
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            $this->fail("Failed to prepare the sent notifications for the fixture comparison: {$exception->getMessage()}.");
        }

        $this->assertEqualsFixture($fixture, $preparedData, $exportMode);
    }

    protected function validateNotificationOptions(array $options): void
    {
        $collisions = array_keys(array_intersect_key($options, array_flip(self::RESERVED_NOTIFICATION_FIELDS)));

        if (!empty($collisions)) {
            $this->fail(sprintf(
                "Options fields '%s' collide with the reserved notification fields: %s.",
                implode("', '", $collisions),
                implode(', ', self::RESERVED_NOTIFICATION_FIELDS),
            ));
        }
    }

    protected function prepareNotificationFixtureData(array $notification, array $options): array
    {
        foreach ($options as $key => $chain) {
            $notification[$key] = $this->resolveNotificationChain($notification['notification'], $chain, $notification['notifiable']);
        }

        $notification['notification'] = $this->getObjectAttributes($notification['notification']);
        $notification['notifiable'] = $this->prepareNotifiableFixtureData($notification['notifiable']);

        unset($notification['notification']['id']);

        return $notification;
    }

    protected function resolveNotificationChain(object $notification, array $chain, object $notifiable): mixed
    {
        $notificationClass = $notification::class;
        $value = $notification;

        foreach ($chain as $step) {
            $context = "Notification {$notificationClass} cannot resolve options step '{$step}'";

            if (!is_object($value)) {
                $type = get_debug_type($value);

                $this->fail("{$context} because the previous step returned a non-object value of type '{$type}'.");
            }

            $valueClass = $value::class;

            if (str_ends_with($step, '()')) {
                $method = substr($step, 0, -2);

                if (!method_exists($value, $method)) {
                    $this->fail("{$context} because {$valueClass} doesn't have method '{$method}'.");
                }

                $reflectionMethod = new ReflectionMethod($value, $method);

                if (!$reflectionMethod->isPublic()) {
                    $this->fail("{$context} because method '{$method}' of {$valueClass} is not public.");
                }

                $value = ($reflectionMethod->getNumberOfParameters() > 0)
                    ? $value->{$method}($notifiable)
                    : $value->{$method}();
            } elseif ($this->isNotificationChainPropertyAccessible($value, $step)) {
                $value = $value->$step;
            } elseif (property_exists($value, $step)) {
                $this->fail("{$context} because property '{$step}' of {$valueClass} is not accessible, it's either non-public or uninitialized.");
            } else {
                $this->fail("{$context} because {$valueClass} doesn't have property '{$step}'.");
            }
        }

        return $value;
    }

    protected function isNotificationChainPropertyAccessible(object $value, string $step): bool
    {
        return array_key_exists($step, get_object_vars($value))
            || isset($value->$step)
            || (($value instanceof Model) && array_key_exists($step, $value->getAttributes()));
    }

    protected function prepareNotifiableFixtureData(object $notifiable): array
    {
        $attributes = ($notifiable instanceof Model)
            ? [$notifiable->getKeyName() => $notifiable->getKey()]
            : get_object_vars($notifiable);

        return [
            'class' => $notifiable::class,
            'attributes' => $attributes,
        ];
    }
}
