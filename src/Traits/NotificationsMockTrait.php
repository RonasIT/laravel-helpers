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
     * on the notification object. Returns null if any step cannot be resolved.
     *
     * Steps format:
     *   'method()' — calls the method on the notification or the result of the previous step
     *   'property' — accesses the property on the notification or the result of the previous step
     *
     * Example:
     *   [
     *      'message'        => ['toExpoPush()', 'toArray()'],  // $notification->toExpoPush()->toArray()
     *      'broadcast_on'   => ['broadcastOn()'],              // $notification->broadcastOn()
     *      'broadcast_data' => ['toBroadcast()', 'data'],      // $notification->toBroadcast()->data
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
        foreach ($options as $key => $chain) {
            $notification[$key] = $this->resolveNotificationChain($notification['notification'], $chain);
        }

        $attributes = $this->getObjectAttributes($notification['notification']);

        $notification['notification'] = $attributes;

        unset($notification['notification']['id']);

        return $notification;
    }

    protected function resolveNotificationChain(object $notification, array $chain): mixed
    {
        $value = $notification;

        foreach ($chain as $step) {
            if (!is_object($value)) {
                return null;
            }

            if (str_ends_with($step, '()') && method_exists($value, rtrim($step, '()'))) {
                $method = rtrim($step, '()');
                $value = $value->{$method}();
            } elseif (property_exists($value, $step)) {
                $value = $value->$step;
            } else {
                return null;
            }
        }

        return $value;
    }
}
