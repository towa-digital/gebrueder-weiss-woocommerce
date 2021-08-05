<?php

namespace Tests\Integration;

use Towa\GebruederWeissWooCommerce\Support\WordPress;

class WordPressTest extends \WP_UnitTestCase
{
    public function test_it_can_send_error_notifications_to_the_admin()
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
}
