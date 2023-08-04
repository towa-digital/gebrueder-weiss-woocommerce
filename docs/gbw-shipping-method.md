# GBW Shipping Method

## Setup

---
> The `GBW Shipping Method` will only be used if enabled in the [Plugin settings](./setup#settings-tab-shipping-details), otherwise all orders will be sent to Gebrüder Weiss.
---

The Plugin offers a custom shipping method that can be used for defining custom shipping zones and custom shipping costs. 
How shipping zones are defined is documented here: [Setting up Shipping Zones](https://woocommerce.com/document/setting-up-shipping-zones/)
You can add the Gebrüder Weiss shipping Method by editing one of your shipping zones, and adding the `GBW Shipping` Option. 

![woocommerce-shipping-add-gbw-shipping.png](./assets/images/woocommerce-shipping-add-gbw-shipping.png ':size=900')

*Figure 1: Add GBW Shipping as custom Shipping Method*

Once you have added the shipping Method, you can configure this method by clicking `edit` on the shipping method.

![woocommerce-shipping-edit-gbw-shipping.png](./assets/images/woocommerce-shipping-edit-gbw-shipping.png ':size=900')

![woocommerce-shipping-add-warhouse-id](./assets/images/woocommerce-shipping-add-warhouse-id.png ':size=900')

*Figure 2 & 3: Edit GBW Shipping Method and add Gebrüder Weiss Warehouse ID*

In the overlay you can add a specific Gebrüder Weiss Warehouse ID. This is only relevant if you are a customer who has stock in different warehouses from Gebrüder Weiss. You can configure your shipping zones to use the nearest Warehouse by supplying the nearest warehouse id within the shipping zone. 
If no Warehouse ID is supplied, the default of your account will be used. Please Contact Gebrüder Weiss Support if you have further Questions about this. 

## Shipping Rate

Per default the `GBW Shipping Method` has no additional price defined. If you want to override the default shipping rate calculation from the Plugin you can use the filter `gbw_shipping_rate` as follows:

```php
// functions.php
function custom_gbw_shipping_rate(array $rate)
{
	// you can set the label displayed to the endcustomer. 
	$rate['label'] = __('Gebrüder Weiss', 'your-theme-language-domain');

	// you can set the cost of this shipping method
	$rate['cost'] = '0';

	// you can set how you want the tax to be calculated
	$rate['calc_tax'] = 'per_item';
	
	return $rate;
}

add_filter('gbw_shipping_rate', 'custom_gbw_shipping_rate');
```

