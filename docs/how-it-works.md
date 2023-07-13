# How the Gebrüder Weiss Woocommerce Plugin works

## Overview

A high-level overview for the combined process of ordering and shipping an item via Gebrüder Weiss looks like this:

1. A customer orders something in the WooCommerce store.
2. The status for the order gets set to the [fulfillment state](./setup#settings-tab-fulfillment). This can happen as a result of one of the following two events:
	1. The payment processor receives the payment for the order and updates the order state. to the fullfillment state *(this would be the default processing state from woocommerce)*
	2. The shop manager updates the status manually. *(this would be done if pay by check or something similar is activated)*
3. During the state transition, the WooCommerce Plugin triggers an API Call to the Gebrüder Weiss API to trigger shipping for the order and sets the order state to [pending state](./setup.md#settings-tab-fulfillment). Depending on the success of the API call, the flow is different from here:
	1. If the request is successful, state transition progresses to the [pending state](./setup.md#settings-tab-fulfillment), and the flow continues with step 4.
	2. If the request fails, a retry flow gets started:
		1. The request gets added to a failed requests queue.
		2. Every five minutes, the requests in this queue get retried.
		3. If the request is successful, this subflow ends.
		4. If a **request fails** for the third time, a **notification is sent to the store owner**, and the order state gets set to the [fulfillment error state](./setup.md#settings-tab-settings-tab-fulfillment).
4. Once the shipping is created, the Gebrüder Weiss triggers a WebHook that calls a REST endpoint provided by the plugin (`{WORDPRESS_REST_API_BASE_URL}/gebrueder-weiss-woocommerce/v1/orders/{WOOCOMMERCE_ORDER_ID}/callbacks/success`).
	1. The plugin attaches the Gebrüder Weiss shipping id to the order. This will be saved in the custom field defined in the [Order Id Field](./setup.md#settings-tab-order)
	2. The plugin attaches shipping status URL to the order. This will be saved in the custom field defined in the [Tracking Link Field](./setup.md#settings-tab-order)
	3. The plugin attaches the carrier information to the order. This will be saved in the custom field defined in the [Tracking Link Field](./setup.md#settings-tab-order)
5. Once the Gebrüder Weiss has shipped the item, they trigger another WebHook that calls a REST endpoint provided by the plugin (`{WORDPRESS_REST_API_BASE_URL}/gebrueder-weiss-woocommerce/v1/orders/{WOOCOMMERCE_ORDER_ID}/callbacks/fulfillment`).
6. The order state gets updated to the [fulfilled state](./setup.md#settings-tab-fulfillment).

### State Diagram

![gbw-plugin-status-flow](./assets/images/gbw-plugin-status-flow.png)

*Figure 1: State Diagram of the Gebrüder Weiss Woocommerce Plugin*

## Use Cases

### All Orders fulfilled by Gebrüder Weiss, only automated payment

The Default use case is that Gebrüder Weiss fulfils all orders coming into Woocommerce. There are only automated payment options available. Automated payment options like paypal, apple pay or credit card, will get the **Processing** state automatically, after successful payment. In this case the [Fulfillment Settings](./setup#settings-tab-fulfillment) can be set as follows:

| Setting                | State in Woocommerce |
| ---------------------- | -------------------- |
| Fulfillment State      | Processing           |
| Pending State          | On hold              |
| Fulfilled State        | Completed            |
| Fulfilment Error State | Failed               | 

### All Orders fulfilled by Gebrüder Weiss - manual payment options available

If the Shop offers manual payment options, like bank transfer or similar, in most cases the **orders will be set to "On Hold" by the payment provider** until the order is paid. In this case a Woocommerce **Backend user can not differentiate** if the status "On Hold" was set by the payment provider or the Gebrüder Weiss Plugin, if the pending state was set to On Hold, like in the first usecase. Therefore it is a good idea to create a new state within Woocommerce to differentiate the states. To do this the shop owner has to create the new state by them selfes. This can be done via code, documented here: [Woocommerce Add/Modify States](https://woocommerce.com/document/addmodify-states/) 

| Setting                | State in Woocommerce | Comment |
| ---------------------- | -------------------- | ------- |
| Fulfillment State      | Processing           |         |
| Pending State          | Pending Shipment     | custom registered state | 
| Fulfilled State        | Completed            |         |
| Fulfilment Error State | Failed               |         |

#### Add custom State to Woocommerce TL;DR
- Option 1: add this two functions to your `functions.php` file

```php
/**
 * Registers custom pending state post status.
 */
function register_custom_pending_state() {
   register_post_status( 'wc-pending-shipment', array(
       'label'                     => 'Pending Shipment',
       'public'                    => true,
       'show_in_admin_status_list' => true,
       'show_in_admin_all_list'    => true,
       'exclude_from_search'       => false,
       'label_count'               => _n_noop( 'Pending Shipment <span class="count">(%s)</span>', 'Pending Shipment <span class="count">(%s)</span>' )
   ) );
}

/**
 * Adds the Pending state to the woocommerce state dropdown.
 */
function add_pending_state_to_wc_order_statuses( $order_statuses ) {
	$order_statuses['wc-pending-shipment'] = 'Pending Shipment';
	return $new_order_statuses;
}

add_action( 'init', 'register_custom_pending_state' );
add_filter( 'wc_order_statuses', 'add_pending_state_to_wc_order_statuses' );
```
- Option 2: use a Plugin wich enables custom states like ["Custom Order Status Manager for Woocommerce"](https://wordpress.org/plugins/bp-custom-order-status-for-woocommerce/)