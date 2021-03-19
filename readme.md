### TaxJar for Easy Digital Downloads

This plugin connects Easy Digital Downloads to [TaxJar.com](https://taxjar.com) so that all tax rate calculations are done by TaxJar.

By allowing TaxJar to handle tax rate determination, store owners can rest easy with accurate, up to date tax rate calculations without requiring any extensive manual entry for tax rates.

### Usage Instructions

1. Signup for an account at [TaxJar.com](https://taxjar.com)
2. Obtain an API Token from your account area
3. Install this plugin and activate it by uploading it via Plugins > Add New
4. Navigate to Downloads > Settings > Taxes and enable taxes
5. Navigate to Downloads > Settings > Taxes > TaxJar
6. Enter your TaxJar API token

All sales taxes will now be calculated automatically based on the zip/postal code and billing country entered on checkout.

### Notes

In order for the tax rate calculation to occur, your checkout screen must include the billing zip / postal field. If that field is not present on your checkout screen, no tax rate determination can be performed.

At this time this plugin only calculates the tax rate; it does not create order records in your TaxJar account, but we will add support for that soon. In the meantime, you can export your order history from Downloads > Reports > Export and then import it into TaxJar after the end of each month.