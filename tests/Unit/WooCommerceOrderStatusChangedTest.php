<?php

namespace Tests\Unit;

use Towa\GebruederWeissWooCommerce\Plugin;
use Towa\GebruederWeissWooCommerce\LogisticsOrderFactory;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthAuthenticator;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthToken;
use Towa\GebruederWeissWooCommerce\SettingsRepository;
use PHPUnit\Framework\TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Towa\GebruederWeissSDK\Api\WriteApi;
use Towa\GebruederWeissSDK\ApiException;
use Towa\GebruederWeissSDK\Configuration;
use Towa\GebruederWeissSDK\Model\LogisticsOrder;

class WooCommerceOrderStatusChangedTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const SELECTED_FULFILLMENT_STATE = "selected-state";
    private const PREFIXED_SELECTED_FULFILLMENT_STATE = "wc-selected-state";

    /** @var Plugin */
    private $plugin;

    /** @var MockInterface|WriteApi */
    private $writeApi;

    /** @var MockInterface|SettingsRepository */
    private $settingsRepository;

    /** @var MockInterface|OAuthAuthenticator */
    private $authenticator;

    /** @var MockInterface|LogisticsOrderFactory */
    private $logisticsOrderFactory;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Plugin */
        $this->plugin = Plugin::getInstance();

        /** @var MockInterface|WriteApi */
        $this->writeApi = Mockery::mock(WriteApi::class);
        $this->writeApi->allows("logisticsOrderPost");
        $this->writeApi->allows(["getConfig" => new Configuration()]);

        /** @var MockInterface|SettingsRepository */
        $this->settingsRepository = Mockery::mock(SettingsRepository::class);
        $this->settingsRepository->allows([
            "getFulfillmentState" => self::PREFIXED_SELECTED_FULFILLMENT_STATE,
            "getClientId" => "id",
            "getClientSecret" => "secret",
            "setAccessToken" => null,
            "getAccessToken" => "token",
            "getSiteUrl" => "http://test.com",
        ]);

        /** @var MockInterface|LogisticsOrderFactory */
        $this->logisticsOrderFactory = Mockery::mock(LogisticsOrderFactory::class);
        $this->logisticsOrderFactory->allows([
            "buildFromWooCommerceOrder" => new LogisticsOrder(),
        ]);

        /** @var MockInterface|OAuthAuthenticator */
        $this->authenticator = Mockery::mock(OAuthAuthenticator::class);
        $this->authenticator->allows([
            "authenticate" => new OAuthToken("token", "Bearer", 3600)
        ]);

        $this->plugin->setWriteApiClient($this->writeApi);
        $this->plugin->setSettingsRepository($this->settingsRepository);
        $this->plugin->setAuthenticationClient($this->authenticator);
        $this->plugin->setLogisticsOrderFactory($this->logisticsOrderFactory);
    }

    public function test_it_does_not_call_the_api_if_fulfillment_state_does_not_match_the_selection()
    {
        $this->plugin->wooCommerceOrderStatusChanged(21, "from-state", "some-state", (object)[]);

        $this->writeApi->shouldNotHaveBeenCalled(["logisticsOrderPost"]);
    }

    public function test_it_calls_the_api_if_the_fulfillment_state_matches_the_selection()
    {
        /** @var MockInterface|object */
        $order = Mockery::mock("WC_Order");
        $order->allows("set_status");
        $order->allows("save");

        $this->plugin->wooCommerceOrderStatusChanged(21, "from-state", self::SELECTED_FULFILLMENT_STATE, $order);

        $this->writeApi->shouldHaveReceived("logisticsOrderPost", [LogisticsOrder::class]);
    }
}
