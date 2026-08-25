<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\AssertionFailedError;
use RonasIT\Support\Tests\Support\Mock\Models\TestAnotherNotifiable;
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
            new TestNotifiable(['id' => 2]),
            new TestNotification(
                firstParam: 'value-3',
                secondParam: 'value-4',
                thirdParam: 2,
            ),
        );

        $this->assertNotificationsSent('assert_notifications_sent_to_multiple_notifiables');
    }

    public function testAssertNotificationsSentGroupsEntriesByNotifiable(): void
    {
        Notification::send(new TestNotifiable(), new TestNotification(firstParam: 'value-first'));
        Notification::send(new TestNotifiable(['id' => 2]), new TestNotification(firstParam: 'value-second'));
        Notification::send(new TestNotifiable(), new TestNotification(firstParam: 'value-third'));

        $this->assertNotificationsSent('assert_notifications_sent_groups_entries_by_notifiable');
    }

    public function testAssertNotificationsSentToNotifiablesOfDifferentClasses(): void
    {
        Notification::send(new TestNotifiable(), new TestNotification());
        Notification::send(new TestAnotherNotifiable(), new TestNotification());

        $this->assertNotificationsSent('assert_notifications_sent_to_notifiables_of_different_classes');
    }

    public function testAssertNotificationsSentWithAnonymousNotifiable(): void
    {
        $notification = new TestNotification(
            channels: ['mail'],
        );

        Notification::route('mail', 'test@example.com')->notify($notification);

        $this->assertNotificationsSent('assert_notifications_sent_with_anonymous_notifiable');
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

    public function testAssertNotificationsSentWithParameterlessInternalMethod(): void
    {
        Notification::send(new TestNotifiable(), new TestChainableNotification());

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_parameterless_internal_method',
            options: [
                'via_internal_method' => ['getDate()', 'getTimestamp()'],
            ],
        );
    }

    public function testAssertNotificationsSentWithModelAttributeStep(): void
    {
        Notification::send(new TestNotifiable(), new TestChainableNotification());

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_model_attribute_step',
            options: [
                'via_model_attribute' => ['getModel()', 'name'],
            ],
        );
    }

    public function testAssertNotificationsSentWithUnresolvableModelAttribute(): void
    {
        Notification::send(new TestNotifiable(), new TestChainableNotification());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("TestModel doesn't have property 'nonExistentAttribute'");

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_options',
            options: [
                'via_unresolvable_model_attribute' => ['getModel()', 'nonExistentAttribute'],
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

    public function testAssertNotificationsSentWithUnresolvableNestedStep(): void
    {
        Notification::send(new TestNotifiable(), new TestChainableNotification());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("stdClass doesn't have property 'nonExistentProperty'");

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_options',
            options: [
                'via_unresolvable_nested_step' => ['getDetails()', 'nonExistentProperty'],
            ],
        );
    }

    public function testAssertNotificationsSentWithNonPublicMethod(): void
    {
        Notification::send(new TestNotifiable(), new TestChainableNotification());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("method 'getPrivateStatus' of " . TestChainableNotification::class . ' is not public');

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_options',
            options: [
                'via_non_public_method' => ['getPrivateStatus()'],
            ],
        );
    }

    public function testAssertNotificationsSentWithNonPublicProperty(): void
    {
        Notification::send(new TestNotifiable(), new TestChainableNotification());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("property 'channels' of " . TestChainableNotification::class . ' is not accessible');

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_options',
            options: [
                'via_non_public_property' => ['channels'],
            ],
        );
    }

    public function testAssertNotificationsSentWithUninitializedProperty(): void
    {
        Notification::send(new TestNotifiable(), new TestNotificationWithUninitializedProperty());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("property 'uninitialized' of " . TestNotificationWithUninitializedProperty::class . ' is not accessible');

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_skips_uninitialized_property',
            options: [
                'via_uninitialized_property' => ['uninitialized'],
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

    public function testAssertNotificationsSentWithUnencodableValue(): void
    {
        Notification::send(new TestNotifiable(), new TestChainableNotification());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed to cast the provided data to a JSON structure: Malformed UTF-8 characters');

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_options',
            options: [
                'via_unencodable_value' => ['getMalformedString()'],
            ],
        );
    }

    public function testAssertNotificationsSentWithReservedOptionKeys(): void
    {
        Notification::send(new TestNotifiable(), new TestChainableNotification());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Options fields 'locale', 'channels' collide with the reserved notification fields");

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_options',
            options: [
                'locale' => ['getStatus()'],
                'channels' => ['getStatus()'],
            ],
        );
    }

    public function testAssertNotificationsSentSkipsNotificationId(): void
    {
        $notification = new TestNotification();
        $notification->id = 'notification-id';

        Notification::send(new TestNotifiable(), $notification);

        $this->assertNotificationsSent('assert_notifications_sent');
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

        $fixturePath = $this->getFixturePath('assert_notifications_sent_with_export.json');

        if (file_exists($fixturePath)) {
            unlink($fixturePath);
        }

        Notification::send(new TestNotifiable(), new TestNotification());

        $this->assertNotificationsSent(
            fixture: 'assert_notifications_sent_with_export',
            exportMode: true,
        );

        $this->assertFileExists($fixturePath);

        $this->assertEqualsFixture(
            fixture: 'assert_notifications_sent_with_export_example',
            data: json_decode(file_get_contents($fixturePath), true),
        );
    }

    public function testAssertNotificationsSentWithGlobalExportMode(): void
    {
        putenv('FAIL_EXPORT_JSON=false');
        $this->globalExportMode = true;

        $fixturePath = $this->getFixturePath('assert_notifications_sent_with_export.json');

        if (file_exists($fixturePath)) {
            unlink($fixturePath);
        }

        Notification::send(new TestNotifiable(), new TestNotification());

        $this->assertNotificationsSent('assert_notifications_sent_with_export');

        $this->assertFileExists($fixturePath);

        $this->globalExportMode = false;

        $this->assertEqualsFixture(
            fixture: 'assert_notifications_sent_with_export_example',
            data: json_decode(file_get_contents($fixturePath), true),
        );
    }
}
