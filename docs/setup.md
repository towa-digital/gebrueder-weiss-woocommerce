# Installation Guide

The Gebrüder Weiss Woocommerce Plugin allows the management and creation of logistic orders for WooCommerce, which are sent to Gebrüder Weiss via the logistics Order API.

The plugin **facilitates the process of transferring shipping information** by providing a direct link between the WooCommerce store and Gebrüder Weiss. This allows the user to **save time and effort**. Furthermore, the plugin provides predefined states for processing and shippment of orders, which are **additional security measures** to ensure that the shipping data will be processed correctly. 

## Process Diagram
![process-diagram](./assets/images/installation-process.png)

*Figure 1: Schematic of the installation process and the steps to obtain the required credentials and configure the plugin for use.*

## Plugin Requirements
The following requirements need to be fulfilled to be able to use the plugin.

- PHP > 7.3
- WooCommerce installed & activated
- Plugin Credentials

## Plugin Credentials

The following information is required to use the plugin and secure the process of order creation:

The **Customer Id** is a unique identifier of your shop set by Gebrüder Weiss and is required to attribute orders to your shop. It can be found in the E-Mail sent to you by Gebrüder Weiss.

The **Consumer Key** and **Consumer Secret**, often also referred to as **Client Key** and **Client Secret**, are required to authenticate requests made using the logistics-order-API, which is used to send orders made within WooCommerce to Gebrüder Weiss. To obtain these credentials follow the steps below:

1. Navigate to [https://developer.my.gw-world.com/home](https://developer.my.gw-world.com/home) and log in with the credentials provided in your communication with Gebrüder Weiss. If you don't have your credentials yet follow the steps under [Register as Customer](./register.md).
2. Select the tab **Applications**, where you can view your current and generate a new **Consumer Key** and **Consumer Secret.**
3. Copy the **Consumer Key** and **Consumer Secret** and save them for use during the plugin configuration.

![logistics-order-api-plugin-credentials](./assets/images/logistics-order-api-plugin-credentials.jpg)

*Figure 2: The fields for Consumer Key and Secret within the Tab **Applications** for the logistics-order-api*

## Wordpress Installation Process

### Manually
To install the Gebrüder Weiss Woocommerce Plugin the following steps must be taken: 

1. Ensure that the WordPress-site fulfils the requirements listed above.
2. Click on Upload Plugin and select the zip-file of the plugin sent via E-Mail to upload the plugin to WordPress. 
3. Click on the button "Install Now" and wait for the installation process to finish. 
4. Activate the plugin by clicking on "Activate Plugin" after the installation process has finished or by clicking on Activate in the list of installed plugins. 

---

> After activation an error message will be shown that credentials are not set. This is intended - the message will be removed once the credentials are set.

--- 
More information on the installation process and troubleshooting can be found in the [FAQ](./FAQ.md).

![installation-process-upload](./assets/images/installation-process-upload.png)

*Figure 3: The screen shown when the button Upload Plugin in Step 2 was clicked on. Clicking on Install Now will start the installation process for the plugin provided as zip-file.*

![installation-process-activation](./assets/images/installation-process-activation.png)

*Figure 4: The list entry for the Gebrüder Weiss Woocommerce Plugin within wordpress. Pressing on Activate allows the activation of the plugin.*

### Via Composer
The plugin is also available via [packagist](https://packagist.org/packages/towa/gebrueder-weiss-woocommerce), if you prefer an installation via composer.
If you have composer set up you can install the plugin with 
```bash
composer require towa/gebrueder-weiss-woocommerce
```

afterwards you can continue with [Step 4](#manually) of the manual installation process

## Plugin Configuration

To configure the plugin for use, navigate to Tab **Settings Gebrüder Weiss WooCommerce.**

![Sidebar menu Plugin](./assets/images/gbw-plugin-wordpress-sidebar.png ':size=300')

*Figure 5: Gebrüder Weiss Woocommerce Plugin Settings in Wordpress Sidebar*

There are three groups of settings that need to be configured to use the plugin:

### Settings Tab Account 
Contains the credentials to authenticate the plugin and create logistic orders for Gebrüder Weiss. The required information can be obtained following the instructions in the previous section. The credentials are validated whenever new information is entered, and a message will be shown whether the credentials were validated successfully or not.

| Setting       | Description                                                                                                |
| ------------- | ---------------------------------------------------------------------------------------------------------- |
| Customer Id   | The unique identifier of your shop set by Gebrüder Weiss.                                                  |
| Client Id     | The Client ID or Consumer Key, which is required to generate an access token and make API-requests.        |
| Client Secret | The Client Secret or Consumer Secret, which is required to generate an access token and make API-requests. |

![gbw-plugin-settings-account](./assets/images/gbw-plugin-settings-account.png ':size=400')

*Figure 6: Settings Tab for Account settings in Plugin*

### Settings Tab Fulfillment

Defines the WooCommerce states that should be used by Gebrüder Weiss, for the processing and fulfillment, and which state should be used if the order was successful or failed.
If any of the fields is not defined an error message is shown indicating which field has yet to be defined. If more information is required please refer to the outlined [use cases](./how-it-works.md#use-cases)

| Setting                 | Description                                                                                                                                                                                                                                                                                                                                                     |
| ----------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Fulfillment State       | Orders in this state are ready to be shipped and start the processing of the Order by Gebrüder Weiss.                                                                                                                                                                                                                                                           |
| Pending State           | Orders in this state are currently being handled by Gebrüder Weiss. This state exists to differentiate between Wocommerce's default on hold state (which is also used by payment providers). Default would be "on-hold". If a custom state should be used, it has to be created. An explanation on how and why can be found [here](./how-it-works.md#use-cases) |
| Fulfilled State         | The order was successfully shipped.                                                                                                                                                                                                                                                                                                                             |
| Fulfillment Error State | An error was encountered during processing of the order, or during shipment.                                                                                                                                                                                                                                                                                                        |

![gbw-plugin-settings-fullfillment](./assets/images/gbw-plugin-settings-fullfillment.png ':size=400')

*Figure 7: Settings Tab for Fullfillment Settings in Plugin. Define which states should be used by the Plugin*

### Settings Tab Order

Define the *custom fields* used to hold the order id assigned by Gebrüder Weiss to the order, a tracking link to track delivery progress and information on the used carrier. If any of the fields are not defined an error message is shown indicating which field has yet to be defined.
Read more about how you can use these custom fields here: [Display custom fields added by Plugin](./how-it-works.md#display-custom-fields-added-by-plugin)

| Setting                   | Description                                                                                       |
| ------------------------- | ------------------------------------------------------------------------------------------------- |
| Order Id                  | The unique identifier set by the plugin to identify your order once it is sent to Gebrüder Weiss. |
| Tracking Link             | A tracking link to track the delivery progress of the shipment.                                   |
| Carrier Information Field | The name of the Carrier used for shipment                                                         | 

![gbw-plugin-settings-order](./assets/images/gbw-plugin-settings-order.png ':size=400')

*Figure 8: Settings Tab for Order Settings in Plugin. Define in which custom fields the information should be stored.*

### Settings Tab Shipping Details

---
> This is only relevant if your shop offers multiple shipping zones or multiple shipping methods.
---

Define if the custom shipping method supplied by the plugin should be used. This is useful if your shop offers different shipping methods or shipping zones. You can also define custom Warehouses for each shipping zone. If enabled only orders with a chosen Gebrüder Weiss shipping method will be sent to Gebrüder Weiss. 
[Learn more about how you can use the custom shipping method](gbw-shipping-method.md)

![gbw-plugin-settings-shipping-details](./assets/images/gbw-plugin-settings-shipping-details.png ':size=900')

*Figure 9: Settings Tab for Shipping Details in Plugin. Select if you want to use GBW Shipping Zones/Methods*
