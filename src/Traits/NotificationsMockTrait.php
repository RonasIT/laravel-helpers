<?php

namespace RonasIT\Support\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use ReflectionMethod;

trait NotificationsMockTrait
{
    use FixturesTrait;
    use ReflectionTrait;

    /**
     * $options should look like the following construction:
     *   [
     *      'field_name' => ['step1', 'step2', ...],
     *   ]
     *
     * where each step is either a method call or a property access resolved sequentially
     * on the notification object. The test fails when any step cannot be resolved (e.g. a
     * misspelled method or property name), so the chain definition is validated loudly.
     *
     * Steps format:
     *   'method()' — calls the method on the notification or the result of the previous step
     *   'property' — accesses the property on the notification or the result of the previous step
     *
     * Only public members are resolvable, a non-public one fails the test instead of raising a PHP
     * error. Note that the 'notification' fixture key still contains all the properties of the
     * notification, including the non-public ones.
     *
     * The notifiable is passed as the first argument to every method that accepts at least one
     * parameter, like Laravel's channel dispatch (e.g. toMail($notifiable)); parameterless methods
     * are called without arguments, so chaining into internal classes (e.g. DateTimeImmutable::getTimestamp())
     * works as well. Intended for the notification's own channel methods.
     *
     * Field names must not collide with the reserved keys: 'notification', 'channels', 'notifiable', 'locale'.
     *
     * Entries are ordered the way the Notification fake groups them: by notifiable class, then by
     * notifiable key, and only then by the send order within that group. Sending a notification to
     * one notifiable, then to another one, and then to the first one again puts the third entry
     * before the second one, so the fixture must not be read as a chronological sequence.
     *
     * The 'notifiable' fixture key always contains the notifiable class, so notifiables of different
     * classes sharing a primary key stay distinguishable. Models are reduced to their class and
     * primary key, other notifiables to their class and public properties, override
     * prepareNotifiableFixtureData() to change that.
     *
     * Example:
     *   [
     *      'message'        => ['toExpoPush()', 'toArray()'],  // $notification->toExpoPush($notifiable)->toArray($notifiable)
     *      'broadcast_on'   => ['broadcastOn()'],              // $notification->broadcastOn($notifiable)
     *      'broadcast_data' => ['toBroadcast()', 'data'],      // $notification->toBroadcast($notifiable)->data
     *   ]
     *
     * @param  array<string, string[]>  $options
     */
    protected function assertNotificationsSent(string $fixture, array $options = [], bool $exportMode = false): void
    {
        $this->validateReservedOptions($options);

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

        $this->assertEqualsFixture($fixture, $this->castToJsonStructure($actualData), $exportMode);
    }

    protected function validateReservedOptions(array $options): void
    {
        $reservedKeys = ['notification', 'channels', 'notifiable', 'locale'];

        foreach (array_keys($options) as $key) {
            if (in_array($key, $reservedKeys, true)) {
                $this->fail("Options field '{$key}' collides with a reserved key. Reserved keys are: " . implode(', ', $reservedKeys) . '.');
            }
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
            } elseif (array_key_exists($step, get_object_vars($value))) {
                $value = $value->$step;
            } elseif (property_exists($value, $step)) {
                $this->fail("{$context} because property '{$step}' of {$valueClass} is not accessible, it's either non-public or uninitialized.");
            } else {
                $this->fail("{$context} because {$valueClass} doesn't have property '{$step}'.");
            }
        }

        return $value;
    }

    protected function prepareNotifiableFixtureData(object $notifiable): array
    {
        $attributes = ($notifiable instanceof Model)
            ? [$notifiable->getKeyName() => $notifiable->getKey()]
            : get_object_vars($notifiable);

        return [
            'class' => $notifiable::class,
            ...$attributes,
        ];
    }
}
