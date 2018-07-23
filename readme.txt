=== EDD TaxJar ===
Contributors: easydigitaldownloads, mordauk
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=EFUPMPEZPGW7L
Tags: easy digital downloads, digital download, edd, e-commerce, tax, taxjar
Requires at least: 4.5
Tested up to: 4.9
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically calculate sales tax in Easy Digital Downloads with TaxJar.

== Description ==

This plugin connects Easy Digital Downloads to [TaxJar.com](https://taxjar.com) so that all tax rate calculations are donen by TaxJar.

By allowing TaxJar to handle tax rate determination, store owners can rest easy with accurate, up to date tax rate calculations without requiring any extensive manual entry for tax rates.

== Installation ==

1. Signup for an account at [TaxJar.com](https://taxjar.com)
2. Obtain an API Token from your account area
3. Install this plugin and activate it by uploading it via Plugins > Add New
4. Navigate to Downloads > Settings > Taxes and enable taxes
5. Navigate to Downloads > Settings > Taxes > Tax Jar
5. Enter your TaxJar API token

== Notes ==

In order for the tax rate calculation to occur, your checkout screen must include the billing zip / postal field. If that field is not present on your checkout screen, no tax rate determination can be performed.

At this time this plugin only calculates the tax rate, it does not create order records in your TaxJar acccount, but we will add support for that soon. In the mean time, you can export your order history from Downloads > Reports > Export and then import it into TaxJar after the end of each month.

== Changelog ==

= 1.0.1, July 23, 2018 =

* Fix: Undefined PHP notice in upcoming Easy Digital Downloads 3.0 version

= 1.0, June 22, 2018 =

* Initial release
