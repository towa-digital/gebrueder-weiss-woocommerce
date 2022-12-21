# Fulfillment Process
A high-level overview for the combined process of ordering and shipping an item via Gebrüder Weiss looks like this:

1) A customer orders something in the WooCommerce store.
2) The status for the order gets set to the fulfillment state. This can happen as a result of one of the following two events:
   1) The payment processor receives the payment for the order and updates the order state.
   2) The shop manager updates the status manually.
3) During the state transition, the WooCommerce Plugin triggers an API Call to the Gebrüder Weiss API to trigger shipping for the order and sets the order state to pending state. Depending on the success of the API call, the flow is different from here:
   1) If the request is successful, state transition progresses to the fulfillment state, and the flow continues with step 4.
   2) If the request fails, a retry flow gets started:
      1) The request gets added to a failed requests queue.
      2) Every five minutes, the requests in this queue get retried.
      3) If the request is successful, this subflow ends.
      4) If a request fails for the third time, a notification is sent to the store owner, and the order state gets set to the error state.
4) Once the shipping is created, the Gebrüder Weiss triggers a WebHook that calls a REST endpoint provided by the plugin (`{WORDPRESS_REST_API_BASE_URL}/gebrueder-weiss-woocommerce/v1/orders/{WOOCOMMERCE_ORDER_ID}/callbacks/success`).
5) The plugin attaches the Gebrüder Weiss shipping id to the order.
6) Once the Gebrüder Weiss has shipped the item, they trigger another WebHook that calls a REST endpoint provided by the plugin (`{WORDPRESS_REST_API_BASE_URL}/gebrueder-weiss-woocommerce/v1/orders/{WOOCOMMERCE_ORDER_ID}/callbacks/fulfillment`).
7) The plugin attaches the carrier and the shipping status URL to the order.
8) The order state gets updated to the fulfilled state.
