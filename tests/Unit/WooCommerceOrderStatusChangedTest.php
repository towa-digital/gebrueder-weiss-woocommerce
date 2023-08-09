<?php

namespace Tests\Unit;

use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequestRepository;
use Towa\GebruederWeissWooCommerce\Plugin;
use Towa\GebruederWeissWooCommerce\LogisticsOrderFactory;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthAuthenticator;
use Towa\GebruederWeissWooCommerce\SettingsRepository;
use PHPUnit\Framework\TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Towa\GebruederWeissSDK\Api\DefaultApi;
use Towa\GebruederWeissSDK\ApiException;
use Towa\GebruederWeissSDK\Configuration;
use Towa\GebruederWeissSDK\Model\InlineObject as CreateLogisticsOrderPayload;
use Towa\GebruederWeissWooCommerce\Support\WordPress;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthToken;
use WC_Order;

class WooCommerceOrderStatusChangedTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const STATE_SELECTED_FULFILLMENT          = "selected-state";
    private const STATE_PREFIXED_SELECTED_FULFILLMENT = "wc-selected-state";
    private const STATE_ON_HOLD                       = "on-hold";
    private const STATE_ERROR                         = "wc-error";

    private const ONE_HOUR_IN_SECONDS = 3600;

    private const HTTP_STATUS_CONFLICT = 409;

    /** @var Plugin */
    private $plugin;

    /** @var MockInterface|DefaultApi */
    private $gebruederWeissApi;

    /** @var MockInterface|FailedRequestRepository */
    private $failedRequestRepository;

    /** @var MockInterface|object */
    private $order;

    public function setUp(): void
    {
        parent::setUp();

        $this->plugin = Plugin::getInstance();

        $this->gebruederWeissApi = Mockery::mock(DefaultApi::class);
        $this->gebruederWeissApi->allows("logisticsOrderPost");
        $this->gebruederWeissApi->allows(["getConfig" => new Configuration()]);

        /** @var MockInterface|SettingsRepository $settingsRepository */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows([
            "getFulfillmentState" => self::STATE_PREFIXED_SELECTED_FULFILLMENT,
            "getPendingState"     => self::STATE_ON_HOLD,
            "getClientId"         => "id",
            "getClientSecret"     => "secret",
            "setAccessToken"      => null,
            "getAccessToken"      => new OAuthToken("test", time() + self::ONE_HOUR_IN_SECONDS),
            "getSiteUrl"          => "http://test.com",
            "getUseGBWShippingZones" => false,
        ]);

        /** @var MockInterface|LogisticsOrderFactory $logisticsOrderFactory */
        $logisticsOrderFactory = Mockery::mock(LogisticsOrderFactory::class);
        $logisticsOrderFactory->allows([
            "buildFromWooCommerceOrder" => new CreateLogisticsOrderPayload(),
        ]);

        /** @var MockInterface|OAuthAuthenticator $authenticator */
        $authenticator = Mockery::mock(OAuthAuthenticator::class);
        $authenticator->allows("updateAuthTokenIfNecessary");

        $this->failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
        $this->failedRequestRepository->allows("create");

        $this->plugin->setGebruederWeissApiClient($this->gebruederWeissApi);
        $this->plugin->setSettingsRepository($settingsRepository);
        $this->plugin->setAuthenticationClient($authenticator);
        $this->plugin->setLogisticsOrderFactory($logisticsOrderFactory);
        $this->plugin->setFailedRequestRepository($this->failedRequestRepository);

        $this->order = Mockery::mock(WC_Order::class);
    }

    public function test_it_does_not_call_the_api_if_fulfillment_state_does_not_match_the_selection()
    {
        $this->plugin->wooCommerceOrderStatusChanged(21, "from-state", "some-state", (object)[]);

        $this->gebruederWeissApi->shouldNotHaveBeenCalled(["logisticsOrderPost"]);
    }

    public function test_it_calls_the_api_if_the_fulfillment_state_matches_the_selection()
    {
        $this->order->allows("set_status");
        $this->order->allows("save");

        $this->plugin->wooCommerceOrderStatusChanged(21, "from-state", self::STATE_SELECTED_FULFILLMENT, $this->order);

        $this->gebruederWeissApi->shouldHaveReceived("logisticsOrderPost", ["en-US", CreateLogisticsOrderPayload::class]);
    }

    public function test_it_creates_a_failed_request_if_the_command_fails()
    {
        /** @var MockInterface|DefaultApi $gebruederWeissApi */
        $gebruederWeissApi = Mockery::mock(DefaultApi::class);
        $gebruederWeissApi->allows(["getConfig" => new Configuration()]);
        $gebruederWeissApi
            ->shouldReceive("logisticsOrderPost")
            ->andThrow(new ApiException("Unauthenticated", 401));

        $this->plugin->setGebruederWeissApiClient($gebruederWeissApi);

        $this->order->allows([
            "set_status" => null,
            "save"       => null,
            "get_id"     => 42
        ]);

        $this->plugin->wooCommerceOrderStatusChanged(21, "from-state", self::STATE_SELECTED_FULFILLMENT, $this->order);

        $this->failedRequestRepository->shouldHaveReceived("create");
    }

    /**
     * We need to isolate this test to be able to alias mock the
     * WordPress class with our helper functions.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_it_handles_conflict_errors()
    {
        /** @var MockInterface $wordpressMock */
        $wordpressMock = Mockery::mock("alias:" . WordPress::class);
        $wordpressMock
            ->shouldReceive("sendMailToAdmin")
            ->once();

        /** @var MockInterface|DefaultApi $gebruederWeissApi */
        $gebruederWeissApi = Mockery::mock(DefaultApi::class);
        $gebruederWeissApi->allows(["getConfig" => new Configuration()]);
        $gebruederWeissApi
            ->shouldReceive("logisticsOrderPost")
            ->andThrow(new ApiException("Conflict", self::HTTP_STATUS_CONFLICT));

        $this->plugin->setGebruederWeissApiClient($gebruederWeissApi);

        /** @var MockInterface|SettingsRepository $settingsRepository */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows([
            "getFulfillmentState"      => self::STATE_PREFIXED_SELECTED_FULFILLMENT,
            "getFulfillmentErrorState" => self::STATE_ERROR,
            "getPendingState"          => self::STATE_ON_HOLD,
            "getClientId"              => "id",
            "getClientSecret"          => "secret",
            "setAccessToken"           => null,
            "getAccessToken"           => new OAuthToken("token", time() + self::ONE_HOUR_IN_SECONDS),
            "getSiteUrl"               => "http://test.com",
            "getUseGBWShippingZones"   => false,
        ]);
        $this->plugin->setSettingsRepository($settingsRepository);

        $this->order->allows(["get_id" => 42]);
        $this->order
            ->shouldReceive("set_status")
            ->once()
            ->withArgs(["wc-error"]);
        $this->order
            ->shouldReceive("save")
            ->once();

        $this->plugin->wooCommerceOrderStatusChanged(21, "from-state", self::STATE_SELECTED_FULFILLMENT, $this->order);

        $this->failedRequestRepository->shouldNotHaveReceived("create");
    }

    public function test_it_should_do_nothing_if_gbw_shipping_zones_are_active_but_the_order_has_no_shipping_zone()
    {
        $this->order->shouldReceive("has_shipping_method")->andReturn(false);

        $this->plugin->setSettingsRepository(Mockery::mock(SettingsRepository::class)->allows([
            'getFulfillmentState' => 'wc-' . self::STATE_SELECTED_FULFILLMENT,
            'getUseGBWShippingZones' => true,
        ]));

        $this->plugin->wooCommerceOrderStatusChanged(21, "from-state", self::STATE_SELECTED_FULFILLMENT, $this->order);

        $this->gebruederWeissApi->shouldNotHaveReceived("logisticsOrderPost");
    }
}
