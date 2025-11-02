=== Tutor bKash ===
Contributors: saifullahsiddique
Tags: tutor, lms, bkash, payment, gateway
Requires at least: 5.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enable bKash payments in Tutor LMS for one-time or subscription courses using Tokenized Checkout for a secure, seamless payment experience.

== Description ==

Tutor bKash integrates bKash with Tutor LMS, enabling secure course payments via bKash wallets using the Tokenized Checkout API.

== Supported bKash API Version ==

* Test/Sandbox - `Tokenized Sandbox v2`
* Live/Production - `Tokenized Pay v1.2.0-beta`

= Features =

* Supports both one-time and subscription course payments (Subscription payment will require to initiate and authorize the transaction due to tokenized checkout limitations)
* Uses bKash Tokenized Checkout for secure transactions
* Includes Sandbox and Live modes for testing and real payments
* IPN integration for automatic payment and order updates
* Secure transaction verification to prevent payment issues
* Full support for bKash mobile wallet payments
* Uses the WordPress HTTP API for safe communication with bKash
* Built-in error handling and logging for easier debugging

= Requirements =

* WordPress 5.3 or higher
* PHP 7.4 or higher
* Tutor LMS (Free version)
* bKash merchant account

= How It Works =

1. Student initiates course purchase
2. Plugin sends payment request to bKash Tokenized API
3. Student redirected to bKash payment page
4. Student completes payment using bKash
5. bKash sends webhook notification to your site
6. Plugin validates transaction and updates order status
7. Student gains course access upon successful payment

= Security Features =

* Token-based authentication
* Transaction verification through bKash API
* Amount verification to prevent tampering
* SSL-secured API communications

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins`
2. Activate the plugin through the WordPress admin
3. Ensure Tutor LMS is installed and activated
4. Go to **Tutor LMS > Settings > Payments**
5. Enable bKash and configure settings

== Configuration ==

**Step 1: Get bKash Credentials**

*Sandbox (Testing):*
1. Register at bKash merchant portal
2. Apply for SandBox credentials by contacting bKash
3. Receive username, password, app_key, and app_secret

*Live (Production):*
1. Apply for live/production API username, password, app_key, and app_secret
2. Complete necessary verification (follow your bKash Key Account Manager's (KAM) instructions)
3. Get credentials from bKash

**Step 2: Configure Plugin**

1. Go to **Tutor LMS > Settings > Payments**
2. Find **bKash** in payment gateways
3. Enable and configure:
   * **Environment**: Sandbox for testing, Live for production
   * **Username**: Your bKash merchant username (Usually it's your registered merchant phone number)
   * **Password**: Your merchant password
   * **App Key**: Your bKash App Key
   * **App Secret**: Your bKash App Secret
   * **Webhook URL**: Copy this URL

**Step 3: Configure bKash Panel**

1. Login to bKash merchant portal
2. Go to Webhook Settings
3. Add the webhook URL from plugin settings
4. Save settings

== Frequently Asked Questions ==

= Do I need a bKash account? =

Yes, you need a merchant account. Contact bKash for merchant registration.

= Supported bKash API Version =

Currently this plugin supports `Tokenized Sandbox v2` for Test/Sandbox and `Tokenized Pay v1.2.0-beta` for Live/Production.

= Does this support subscriptions? =

Yes, This plugin allows students to make one-time or subscription course payments using bKash.

= Can I test before going live? =

Yes, use Sandbox environment with test credentials provided by bKash.

= What currencies are supported? =

BDT (Bangladeshi Taka) is the primary currency.

= How do I troubleshoot payment issues? =

1. Verify credentials are correct
2. Ensure webhook URL is configured in bKash panel
3. Check environment settings (Sandbox vs Live)
4. Enable WordPress debug logging
5. Verify SSL certificate on your site

= What payment methods are supported? =

bKash mobile wallet payments through Tokenized Checkout.

= Is there a transaction fee? =

Transaction fees depend on your bKash merchant agreement. Contact bKash for pricing details.

= Can I process refunds? =

Refunds must be processed manually through the bKash merchant panel. The plugin doesn't handle automatic refunds.

== Changelog ==

= 1.0.0 =
* Initial release
* One-time and subscription payment support
* Sandbox and Live environments
* Webhook integration
* Transaction validation

== Upgrade Notice ==

= 1.0.0 =
Initial release of bKash payment gateway for Tutor LMS.

== Support ==

For plugin issues: [GitHub Issues](https://github.com/asmsaiff/tutor-bkash/issues)
For bKash Merchant API: Contact [support@bkash.com](mailto:support@bkash.com) or [Apply Here](https://www.bkash.com/en/business/merchant)
For Tutor LMS: Contact [Tutor LMS Support](https://tutorlms.com/support)

== Credits ==

Developed by [S. Saif](https://profiles.wordpress.org/saifullahsiddique)
Based on Tutor LMS Custom Payment Gateway Framework
bKash API integration
