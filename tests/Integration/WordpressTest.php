<?php

uses(\WP_UnitTestCase::class);
use Towa\GebruederWeissWooCommerce\Support\WordPress;


test('it can send error notifications to the admin', function () {
    global $phpmailer;

    \update_option("admin_email", "admin@test.com");

    WordPress::sendMailToAdmin("subject", "message");

    // updating the admin email triggers a email, hence we need the second email
    expect($phpmailer->mock_sent[1])->not->toBeNull();
    $mail = $phpmailer->mock_sent[1];
    $this->assertStringContainsString("message", $mail["body"]);
    expect($mail["subject"])->toBe("subject");
    expect($mail["to"][0][0])->toBe("admin@test.com");
});

test('it can add a cron interval', function () {
    WordPress::addCronInterval("key", 5, "Display Name");

    $schedules = \wp_get_schedules();
    expect($schedules["key"])->not->toBeNull();
    $schedule = $schedules["key"];
    expect($schedule["interval"])->toBe(5);
    expect($schedule["display"])->toBe("Display Name");
});

test('it can schedule a cron job', function () {
    WordPress::scheduleCronjob("hook", strtotime("now"), "hourly");

    $event = \wp_get_schedule("hook");
    expect($event)->toBe("hourly");
});

test('it does not schedule cron events multiple times', function () {
    WordPress::scheduleCronjob("hook", strtotime("now"), "hourly");
    WordPress::scheduleCronjob("hook", time() + 3600, "daily");

    $event = \wp_get_schedule("hook");
    expect($event)->toBe("hourly");
});

test('it can add an action for a cronjob', function () {
    global $wp_filter;

    WordPress::addCronjobAction("hook", function () {
    });

    expect($wp_filter["hook"][10])->toHaveCount(1);
});

test('it can remove a cron event', function () {
    WordPress::scheduleCronjob("hook", strtotime("now"), "hourly");

    WordPress::clearScheduledHook("hook");

    expect(\wp_get_schedule("hook"))->toBeFalse();
});

test('it can retrieve all meta keys for a post type', function () {
    $postId = $this->factory()->post->create(["post_type" => "post"]);
    \update_post_meta($postId, "key1", "value1");

    expect(WordPress::getAllMetaKeysForPostType("post"))->toHaveCount(1);
});
