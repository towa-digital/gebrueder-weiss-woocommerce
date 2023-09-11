# FAQ


<details>
<summary>
What does the plugin do and what user problems does it solve?
</summary>

The plugin **facilitates the process of transferring shipping information** by providing a direct link between the WooCommerce store and Gebrüder Weiss. This allows the user to **save time and effort**. Furthermore, the plugin provides predefined states for processing and shippment of orders, which simplifies the shipping process by automatically sending the information directly from the WooCommerce store. 

</details>

<details>
<summary>
What are the limitations of the plugin?
</summary>

The plugin has the following limitations:

1. Usage is restricted to Gebrüder Weiss customers
2. Gebrüder Weiss is the only possible carrier
3. Fulfillment and order states are limited to the predefined states
4. Non automated payment options, such as bank transfer are not processed automatically, but rather put on hold until the order is paid. 

</details>

<details>
<summary>
What areas can Gebrüder Weiss deliver to?
</summary>

To find out what areas Gebrüder Weiss is able to deliver to please contact your local branch of Gebrüder Weiss or a sales representative.

</details>

<details>
<summary>
What should I do if I am asked to enter ftp-credentials for the webserver during the installation process?
</summary>

**FTP-credentials** are another security layer to prevent unauthorized users from installing plugins. Please **contact your IT Department** or the hosting partner of your website to solve this issue.
</details>

<details>
<summary>
What if I don’t see the option to upload plugins within wordpress?
</summary>

It is possible, that a **user does not have permission** to install or activate plugins or that **installation of plugins is restricted** and can only be done using composer. Please **contact your IT Department** if this is the case.
</details>

<details>
<summary>
How can I view information on failed orders?
</summary>

If an error occurs during processing of an order, an e-mail is sent to the administrator of the wordpress-site, which contains information on the source of the error and how it might be fixed.
</details>

<details>
<summary>
Why can’t I see custom fields for my order?
</summary>

The option to display custom fields within the wordpress-backend must be toggled on. This can be done by opening an order, clicking on the **Screen Options** Button, and toggling the option **Custom Fields.** If you are using the ACF (Advanced custom fields plugin) you might need to add the following code snippet to your `functions.php` file.

```php
add_filter('acf/settings/remove_wp_meta_box', '__return_false');
```
</details>

<details>
<summary>
I have been contacted by Gebrüder Weiss regarding errors with one of my orders. Why is that the case?
</summary>

During processing of the order, the order is **validated** by different departments of Gebrüder Weiss. During these validations it is possible that missing or conflicting information or other errors with the order arise that were not found during the initial validation. If that is the case Gebrüder Weiss might reach out to you, to resolve these problems.
</details>

