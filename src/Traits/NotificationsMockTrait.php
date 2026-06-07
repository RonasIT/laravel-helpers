<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Facades\Notification;

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
     * The notifiable is always passed as the first argument to every method call, like Laravel's
     * channel dispatch (e.g. toMail($notifiable)); methods without parameters simply ignore it.
     * Intended for the notification's own channel methods.
     *
     * Field names must not collide with the reserved keys: 'notification', 'channels', 'notifiable', 'locale'.
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
        $actualData = [];

        foreach (Notification::sentNotifications() as $notifiableIDs) {
            foreach ($notifiableIDs as $modelNotifications) {
                foreach ($modelNotifications as $notificationClassName => $modelNotification) {
                    foreach ($modelNotification as $notification) {
                        $actualData[$notificationClassName][] = $this->prepareNotificationFixtureData($notification, $options);
                    }
                }
            }
        }

        $preparedActualData = json_decode(json_encode($actualData), true);

        $this->assertEqualsFixture($fixture, $preparedActualData, $exportMode);
    }

    protected function prepareNotificationFixtureData(array $notification, array $options): array
    {
        $reservedKeys = ['notification', 'channels', 'notifiable', 'locale'];

        foreach ($options as $key => $chain) {
            if (in_array($key, $reservedKeys, true)) {
                $this->fail("Options field '{$key}' collides with a reserved key. Reserved keys are: " . implode(', ', $reservedKeys) . '.');
            }

            $notification[$key] = $this->resolveNotificationChain($notification['notification'], $chain, $notification['notifiable']);
        }

        $attributes = $this->getObjectAttributes($notification['notification']);

        $notification['notification'] = $attributes;
        $notification['notifiable'] = $this->prepareNotifiableFixtureData($notification['notifiable']);

        unset($notification['notification']['id']);

        return $notification;
    }

    protected function resolveNotificationChain(object $notification, array $chain, object $notifiable): mixed
    {
        $notificationClass = $notification::class;
        $value = $notification;

        foreach ($chain as $step) {
            if (!is_object($value)) {
                $type = get_debug_type($value);

                $this->fail("Notification {$notificationClass} cannot resolve options step '{$step}' because the previous step returned a non-object value of type '{$type}'.");
            }

            if (str_ends_with($step, '()')) {
                $method = substr($step, 0, -2);

                if (!method_exists($value, $method)) {
                    $this->fail("Notification {$notificationClass} doesn't have method '{$method}' required by options step '{$step}'.");
                }

                $value = $value->{$method}($notifiable);
            } elseif (property_exists($value, $step)) {
                $value = $value->$step;
            } else {
                $this->fail("Notification {$notificationClass} doesn't have property '{$step}' required by options step '{$step}'.");
            }
        }

        return $value;
    }

    protected function prepareNotifiableFixtureData(object $notifiable): mixed
    {
        return $notifiable;
    }
}
