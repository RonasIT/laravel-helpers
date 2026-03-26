<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Notification;
use RonasIT\Support\Tests\Support\Mock\Models\TestNotifiable;
use RonasIT\Support\Tests\Support\Mock\Notifications\TestNotification;
use RonasIT\Support\Tests\Support\Mock\Notifications\TestOrderNotification;
use RonasIT\Support\Traits\NotificationsMockTrait;

class NotificationsMockTraitTest extends TestCase
{
    use NotificationsMockTrait;

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
            new TestNotifiable(
                id: 2,
            ),
            new TestNotification(
                name: 'Jane Doe',
                email: 'jane@example.com',
                userId: 2,
            ),
        );

        $this->assertNotificationsSent('assert_notifications_sent_to_multiple_notifiables');
    }

    public function testAssertMultipleNotificationTypesSent(): void
    {
        Notification::send(new TestNotifiable(), new TestNotification());
        Notification::send(new TestNotifiable(), new TestOrderNotification());

        $this->assertNotificationsSent('assert_multiple_notification_types_sent');
    }

    public function testAssertNotificationsSentWithOptions(): void
    {
        Notification::send(new TestNotifiable(), new TestOrderNotification());

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_options',
            options: [
                'via_method' => ['getStatus()'],
                'via_property' => ['status'],
                'via_chain' => ['getOrder()', 'status'],
                'via_unresolvable' => ['nonExistentMethod()'],
            ],
        );
    }

    public function testAssertNotificationsSentWithExportMode(): void
    {
        putenv('FAIL_EXPORT_JSON=false');

        Notification::send(new TestNotifiable(), new TestNotification());

        $this->assertNotificationsSent(fixture: 'assert_notifications_sent_with_export', exportMode: true);

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
