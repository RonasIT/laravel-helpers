<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\AssertionFailedError;
use RonasIT\Support\Tests\Support\Mock\Models\TestNotifiable;
use RonasIT\Support\Tests\Support\Mock\Notifications\TestAnotherNotification;
use RonasIT\Support\Tests\Support\Mock\Notifications\TestChainableNotification;
use RonasIT\Support\Tests\Support\Mock\Notifications\TestNotification;
use RonasIT\Support\Tests\Support\Mock\Notifications\TestNotificationWithStaticProperty;
use RonasIT\Support\Tests\Support\Mock\Notifications\TestNotificationWithUninitializedProperty;

class NotificationsMockTraitTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function testAssertNotificationsSent(): void
    {
        Notification::send(new TestNotifiable(), new TestNotification());

        $this->assertNotificationsSent('assert_notifications_sent');
    }

    public function testAssertNotificationsSentWithMultipleChannels(): void
    {
        Notification::send(
            new TestNotifiable(),
            new TestNotification(
                channels: ['mail', 'database'],
            ),
        );

        $this->assertNotificationsSent('assert_notifications_sent_with_multiple_channels');
    }

    public function testAssertNotificationsSentToMultipleNotifiables(): void
    {
        Notification::send(
            new TestNotifiable(),
            new TestNotification(),
        );

        Notification::send(
            new TestNotifiable(2),
            new TestNotification(
                firstParam: 'value-3',
                secondParam: 'value-4',
                thirdParam: 2,
            ),
        );

        $this->assertNotificationsSent('assert_notifications_sent_to_multiple_notifiables');
    }

    public function testAssertMultipleNotificationTypesSent(): void
    {
        Notification::send(new TestNotifiable(), new TestNotification());
        Notification::send(new TestNotifiable(), new TestAnotherNotification());

        $this->assertNotificationsSent('assert_multiple_notification_types_sent');
    }

    public function testAssertNotificationsSentWithOptions(): void
    {
        Notification::send(new TestNotifiable(), new TestChainableNotification());

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_options',
            options: [
                'via_method' => ['getStatus()'],
                'via_property' => ['status'],
                'via_chain' => ['getDetails()', 'status'],
                'via_notifiable_argument' => ['getNotifiableKey()'],
            ],
        );
    }

    public function testAssertNotificationsSentWithUnresolvableMethod(): void
    {
        Notification::send(new TestNotifiable(), new TestChainableNotification());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("doesn't have method 'nonExistentMethod'");

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_options',
            options: [
                'via_unresolvable_method' => ['nonExistentMethod()'],
            ],
        );
    }

    public function testAssertNotificationsSentWithUnresolvableProperty(): void
    {
        Notification::send(new TestNotifiable(), new TestChainableNotification());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("doesn't have property 'nonExistentProperty'");

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_options',
            options: [
                'via_unresolvable_property' => ['nonExistentProperty'],
            ],
        );
    }

    public function testAssertNotificationsSentWithNonObjectStep(): void
    {
        Notification::send(new TestNotifiable(), new TestChainableNotification());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('returned a non-object value');

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_options',
            options: [
                'via_non_object_step' => ['getStatus()', 'nonExistentProperty'],
            ],
        );
    }

    public function testAssertNotificationsSentWithReservedOptionKey(): void
    {
        Notification::send(new TestNotifiable(), new TestChainableNotification());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Options field 'channels' collides with a reserved key");

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_options',
            options: [
                'channels' => ['getStatus()'],
            ],
        );
    }

    public function testAssertNotificationsSentSkipsStaticProperty(): void
    {
        Notification::send(new TestNotifiable(), new TestNotificationWithStaticProperty());

        $this->assertNotificationsSent('assert_notifications_sent_skips_static_property');
    }

    public function testAssertNotificationsSentSkipsUninitializedProperty(): void
    {
        Notification::send(new TestNotifiable(), new TestNotificationWithUninitializedProperty());

        $this->assertNotificationsSent('assert_notifications_sent_skips_uninitialized_property');
    }

    public function testAssertNotificationsSentWithExportMode(): void
    {
        putenv('FAIL_EXPORT_JSON=false');

        Notification::send(new TestNotifiable(), new TestNotification());

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_export',
            exportMode: true,
        );

        $this->assertFileExists($this->getFixturePath('assert_notifications_sent_with_export.json'));
    }

    public function testAssertNotificationsSentWithGlobalExportMode(): void
    {
        putenv('FAIL_EXPORT_JSON=false');
        $this->globalExportMode = true;

        Notification::send(new TestNotifiable(), new TestNotification());

        $this->assertNotificationsSent('assert_notifications_sent_with_export');

        $this->assertFileExists($this->getFixturePath('assert_notifications_sent_with_export.json'));
    }
}
