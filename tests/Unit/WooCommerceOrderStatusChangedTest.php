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
use Towa\GebruederWeissSDK\Api\WriteApi;
use Towa\GebruederWeissSDK\ApiException;
use Towa\GebruederWeissSDK\Configuration;
use Towa\GebruederWeissSDK\Model\LogisticsOrder;
use Towa\GebruederWeissWooCommerce\Support\WordPress;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthToken;

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

    /** @var MockInterface|FailedRequestRepository */
    private $failedRequestRepository;

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
            "getAccessToken" => new OAuthToken("test", time() + 3600),
            "getSiteUrl" => "http://test.com",
        ]);

        /** @var MockInterface|LogisticsOrderFactory */
        $this->logisticsOrderFactory = Mockery::mock(LogisticsOrderFactory::class);
        $this->logisticsOrderFactory->allows([
            "buildFromWooCommerceOrder" => new LogisticsOrder(),
        ]);

        /** @var MockInterface|OAuthAuthenticator */
        $this->authenticator = Mockery::mock(OAuthAuthenticator::class);
        $this->authenticator->allows("updateAuthTokenIfNecessary");

        /** @var MockInterface|FailedRequestRepository */
        $this->failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
        $this->failedRequestRepository->allows("create");

        $this->plugin->setWriteApiClient($this->writeApi);
        $this->plugin->setSettingsRepository($this->settingsRepository);
        $this->plugin->setAuthenticationClient($this->authenticator);
        $this->plugin->setLogisticsOrderFactory($this->logisticsOrderFactory);
        $this->plugin->setFailedRequestRepository($this->failedRequestRepository);
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

    public function test_it_creates_a_failed_request_if_the_command_fails()
    {
        /** @var MockInterface|WriteApi */
        $writeApi = Mockery::mock(WriteApi::class);
        $writeApi->allows(["getConfig" => new Configuration()]);
        $writeApi->shouldReceive("logisticsOrderPost")->andThrow(new ApiException("Unauthenticated", 401));
        $this->plugin->setWriteApiClient($writeApi);

        /** @var MockInterface|object */
        $order = Mockery::mock("WC_Order");
        $order->allows([
            "set_status" => null,
            "save" => null,
            "get_id" => 42
        ]);

        $this->plugin->wooCommerceOrderStatusChanged(21, "from-state", self::SELECTED_FULFILLMENT_STATE, $order);

        $this->failedRequestRepository->shouldHaveReceived("create");
    }

    /**
     * We need to isolate this test to able to alias mock the
     * WordPress class with our helper functions.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_it_handles_conflict_errors()
    {
        /** @var MockInterface */
        $wordpressMock = Mockery::mock("alias:" . WordPress::class);
        $wordpressMock->shouldReceive("sendMailToAdmin")->once();

        /** @var MockInterface|WriteApi */
        $writeApi = Mockery::mock(WriteApi::class);
        $writeApi->allows(["getConfig" => new Configuration()]);
        $writeApi->shouldReceive("logisticsOrderPost")->andThrow(new ApiException("Conflict", 409));
        $this->plugin->setWriteApiClient($writeApi);

        /** @var MockInterface|SettingsRepository */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows([
            "getFulfillmentState" => self::PREFIXED_SELECTED_FULFILLMENT_STATE,
            "getFulfillmentErrorState" => "wc-error",
            "getClientId" => "id",
            "getClientSecret" => "secret",
            "setAccessToken" => null,
            "getAccessToken" => new OAuthToken("token", time() + 3600),
            "getSiteUrl" => "http://test.com",
        ]);
        $this->plugin->setSettingsRepository($settingsRepository);

        /** @var MockInterface|stdClass */
        $order = Mockery::mock("WC_Order");
        $order->allows([
            "get_id" => 42
        ]);
        $order->shouldReceive("set_status")->once()->withArgs(["wc-error"]);
        $order->shouldReceive("save")->once();

        $this->plugin->wooCommerceOrderStatusChanged(21, "from-state", self::SELECTED_FULFILLMENT_STATE, $order);

        $this->failedRequestRepository->shouldNotHaveReceived("create");
    }
}
