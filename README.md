# FinersPay - bKash Payment Gateway for Tutor LMS

Enable fast and secure bKash payments for Tutor LMS & let students pay instantly using bKash.

## Features

✅ One-time and subscription (manual) payments for course purchases\
✅ Tokenized Checkout for secure payment processing\
✅ Sandbox and Live environment support\
✅ IPN (Instant Payment Notification) integration\
✅ Secure payment processing with transaction verification\
✅ WordPress HTTP API for secure external communications\
✅ Internationalization (i18n) support for translations

## Requirements

- WordPress 5.3 or higher
- PHP 7.4 or higher
- Tutor LMS (Free version)
- bKash merchant account

## Installation

1. Upload the plugin folder to `/wp-content/plugins`
2. Activate the plugin through WordPress admin
3. Ensure Tutor LMS is activated
4. Configure settings in Tutor LMS > Settings > Payments

## Configuration

### Step 1: Get bKash Credentials

**For Sandbox (Testing):**
1. Register at bKash merchant portal
2. Apply for SandBox credentials by contacting bKash
3. Receive username, password, app_key, and app_secret

**For Live (Production):**
1. Apply for live/production API username, password, app_key, and app_secret
2. Complete necessary verification (follow your bKash Key Account Manager's (KAM) instructions)
3. Get credentials from bKash

### Step 2: Configure Plugin

1. Go to **Tutor LMS > Settings > Payments**
2. Find **bKash** in the payment gateways list
3. Click to enable and configure:
   - **Environment**: Select `Sandbox` for testing or `Live` for production
   - **Username**: Enter your bKash merchant username
   - **Password**: Enter your merchant password
   - **App Key**: Enter your bKash App Key
   - **App Secret**: Enter your bKash App Secret
   - **Webhook URL**: Copy this URL

### Step 3: Configure bKash Merchant Panel

1. Login to your bKash merchant portal
2. Go to Webhook Settings
3. Add the webhook URL from step 2
4. Save settings

## Testing

### Using Sandbox Environment

1. Set environment to "Sandbox"
2. Use sandbox credentials provided by bKash
3. Test with bKash mobile app

### Test Transaction Flow

1. Create a test course in your LMS
2. Set a price for the course
3. Add course to cart and proceed to checkout
4. Select bKash as payment method
5. Complete payment on bKash page
6. Verify order status in Tutor LMS

## How It Works

### Payment Flow

```
Student clicks "Purchase"
    ↓
Plugin authenticates with bKash and creates payment
    ↓
Student redirected to bKash payment page
    ↓
Student completes payment
    ↓
bKash sends webhook notification to your site
    ↓
Plugin validates transaction with bKash API
    ↓
Order status updated (Success/Failed/Cancelled)
    ↓
Student gets access to course (if successful)
```

### Security Features

1. **Token Authentication**: Uses grant token for secure API access
2. **Transaction Validation**: Double-checks payment status with bKash API
3. **Amount Verification**: Ensures paid amount matches order amount
4. **SSL Communication**: All API calls use HTTPS

## Supported APIs

bKash Tokenized Checkout API v1.2.0-beta

**Live Environment:**
```
Tokenized Pay v1.2.0-beta
```

**Sandbox Environment:**
```
Tokenized Sandbox v2
```

Please verify that you are using the correct API version before going live.

### API Endpoints

- **Token Grant**: `/tokenized/checkout/token/grant`
- **Payment Create**: `/tokenized/checkout/payment/create`
- **Payment Execute**: `/tokenized/checkout/payment/execute`
- **Payment Query**: `/tokenized/checkout/payment/query`

## File Structure

```
finerspay/
├── finerspay.php           # Main plugin file (entry point with plugin headers)
├── composer.json             # Composer autoload configuration
├── composer.lock             # Composer dependency lock file
├── README.md                 # GitHub/Documentation readme
├── readme.txt                # WordPress.org-style readme
│
├── assets/                   # Static assets (images, CSS, JS)
│   └── bkash-logo.png        # bKash payment gateway logo
│
├── integration/              # Integration layer with Tutor LMS
│   ├── Init.php              # Handles plugin hooks and initialization
│   ├── BkashConfig.php       # Configuration (API keys, credentials, environment)
│   ├── BkashGateway.php      # Registers bKash as a Tutor LMS payment gateway
│   ├── ExecutePayment.php    # Handles payment execution via API
│   └── RewriteRules.php      # Custom URL rewrite rules for API callbacks
│
├── languages/                # Localization files
│   └── finerspay.pot       # Base translation template file
│
├── payments/                 # Core payment logic
│   └── Bkash.php             # Handles payment creation, validation, and processing
│
└── vendor/                   # Composer dependencies (autoloaded libraries)
```

## Internationalization (i18n)

This plugin supports internationalization and is translation-ready. All user-facing strings are wrapped with WordPress translation functions.

### For Translators

1. Use the `languages/finerspay.pot` file as a template
2. Create language-specific `.po` files using tools like Poedit or Loco Translate
3. Compile `.mo` files and place them in the `languages/` directory
4. File naming: `finerspay-{locale}.mo` (e.g., `finerspay-bn_BD.mo` for Bengali)

### Text Domain

- **Text Domain:** `finerspay`
- **Domain Path:** `/languages/`

### Available Languages

Currently available in:
- English (default)

Contributions for additional language translations are welcome!

## Troubleshooting

### Payment Not Processing

1. **Check Credentials**: Ensure Username, Password, App Key, and App Secret are correct
2. **Environment Mismatch**: Sandbox credentials won't work in Live mode
3. **Webhook URL**: Verify webhook URL is correctly configured in bKash panel
4. **SSL Certificate**: Ensure your site has valid SSL certificate

### Transaction Validation Failed

1. Check if webhook URL is accessible (not blocked by firewall)
2. Copy the webhook URL and test it using your browser’s network tab or an API client (like Postman). Make sure the HTTP response status is 200 (OK) — that means it’s working correctly.
3. Verify webhook_url in plugin settings
4. Enable debug logging in WordPress (WP_DEBUG)
5. Check error/debug logs for detailed messages

### Order Status Not Updating

1. Verify webhook is configured correctly
2. Check if order ID is being passed correctly
3. Ensure authentication tokens are working
4. Check webhook response in browser console

## Known Limitations

1. **Wrong Timezone**: Check your website timezone from Settings > General > Timezone and choose either a city in the same timezone as you or a UTC (Coordinated Universal Time) time offset.
2. **Currency Support**: Currently supports BDT (Bangladeshi Taka) only
3. **Refunds**: Manual refund processing through bKash merchant panel required

## Support

For issues related to:
- **Plugin functionality**: Contact [plugin developer](mailto:asmsaif15@gmail.com)
- **bKash API**: Contact [support@bkash.com](mailto:support@bkash.com) or [developer@bkash.com](mailto:developer@bkash.com)
- **Tutor LMS**: Contact [Themeum support](https://tutorlms.com/support)

## License

This plugin is licensed under GPLv2 or later.

## Credits

- Developed by S. Saif
- bKash Tokenized API integration
- Based on [Tutor LMS Custom Payment Gateway Framework](https://docs.themeum.com/tutor-lms/developer-documentation/custom-payment-gateways)

## Disclaimer

This plugin is **not affiliated with, maintained, endorsed, or sponsored** by Themeum, Tutor LMS, or bKash.

- "Tutor" and "Tutor LMS" are trademarks of Themeum.
- "bKash" is a trademark of bKash Limited.

These names are used solely to indicate compatibility.
The plugin is developed and maintained independently by the open-source community.

## Additional Resources

- [bKash Documentation](https://developer.bka.sh)
- [Tutor LMS Documentation](https://docs.themeum.com/tutor-lms)
- [bKash Merchant Portal](https://merchantportal.bkash.com)
