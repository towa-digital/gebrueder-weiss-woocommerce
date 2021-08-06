<?php

namespace Tests\Integration;

use Towa\GebruederWeissWooCommerce\Support\WordPress;

class WordPressTest extends \WP_UnitTestCase
{
    public function test_it_can_sen_error_notifications_to_the_admin()
    {
        global $phpmailer;

        \update_option("admin_email", "admin@test.com");

        WordPress::sendMailToAdmin("subject", "message");

        // updating the admin email triggers a email, hence we need the second email
        $this->assertNotNull($phpmailer->mock_sent[1]);
        $mail = $phpmailer->mock_sent[1];
        $this->assertStringContainsString("message", $mail["body"]);
        $this->assertSame("subject", $mail["subject"]);
        $this->assertSame("admin@test.com", $mail["to"][0][0]);
    }

    public function test_it_can_add_a_cron_interval()
    {
        WordPress::addCronInterval("key", 5, "Display Name");

        $schedules = \wp_get_schedules();
        $this->assertNotNull($schedules["key"]);
        $schedule = $schedules["key"];
        $this->assertSame(5, $schedule["interval"]);
        $this->assertSame("Display Name", $schedule["display"]);
    }

    public function test_it_can_schedule_a_cron_job()
    {
        WordPress::scheduleCronjob("hook", strtotime("now"), "hourly");

        $event = \wp_get_schedule("hook");
        $this->assertSame("hourly", $event);
    }

    public function test_it_does_not_schedule_cron_events_multiple_times()
    {
        WordPress::scheduleCronjob("hook", strtotime("now"), "hourly");
        WordPress::scheduleCronjob("hook", time() + 3600, "daily");

        $event = \wp_get_schedule("hook");
        $this->assertSame("hourly", $event);
    }

    public function test_it_can_add_an_action_for_a_cronjob()
    {
        global $wp_filter;

        WordPress::addCronjobAction("hook", function () {
        });

        $this->assertCount(1, $wp_filter["hook"][10]);
    }

    public function test_it_can_remove_a_cron_event()
    {
        WordPress::scheduleCronjob("hook", strtotime("now"), "hourly");

        WordPress::clearScheduledHook("hook");

        $this->assertFalse(\wp_get_schedule("hook"));
    }
}
