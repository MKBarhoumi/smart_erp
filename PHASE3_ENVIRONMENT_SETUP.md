# Phase 3 Environment Variables Configuration

## Required Stripe Environment Variables

Add these to your `.env` file:

```env
# Stripe Configuration
STRIPE_KEY=pk_test_your_stripe_public_key
STRIPE_SECRET=sk_test_your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret

# Stripe Price IDs (from your Stripe Dashboard)
STRIPE_PRO_PRICE_ID=price_pro_plan_id
STRIPE_ENTERPRISE_PRICE_ID=price_enterprise_plan_id

# Currency Configuration
CASHIER_CURRENCY=usd
CASHIER_CURRENCY_LOCALE=en
```

## Getting Your Stripe Credentials

### 1. Get Stripe API Keys

1. Go to [Stripe Dashboard](https://dashboard.stripe.com/)
2. Navigate to Developers → API keys
3. Copy your Publishable key (STRIPE_KEY)
4. Copy your Secret key (STRIPE_SECRET)

### 2. Create Stripe Products and Prices

#### Create Free Plan (No Stripe price needed)
- This is handled internally without Stripe

#### Create Pro Plan
```bash
# Create product
stripe products create \
  --name="Pro Plan" \
  --description="For growing businesses"

# Create price (monthly)
stripe prices create \
  --unit-amount=2900 \
  --currency=usd \
  --recurring=interval=month \
  --product="prod_PRO_PLAN_ID"

# Copy the price ID (starts with price_)
```

#### Create Enterprise Plan
```bash
# Create product
stripe products create \
  --name="Enterprise Plan" \
  --description="For large organizations"

# Create price (monthly)
stripe prices create \
  --unit-amount=9900 \
  --currency=usd \
  --recurring=interval=month \
  --product="prod_ENTERPRISE_PLAN_ID"

# Copy the price ID (starts with price_)
```

### 3. Set Up Webhook

#### Create Webhook Endpoint
```bash
stripe webhooks create \
  --url="https://your-domain.com/stripe/webhook" \
  --events="customer.subscription.created,customer.subscription.updated,customer.subscription.deleted,invoice.payment_succeeded,invoice.payment_failed,customer.subscription.trial_will_end"
```

#### Get Webhook Secret
```bash
stripe webhooks endpoint \
  --id="we_WEBHOOK_ID" \
  --reveal
```

Copy the `secret` value (starts with `whsec_`)

## Testing Environment Variables

For local testing, use Stripe test mode:

```env
# Test Mode
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

For production:

```env
# Live Mode
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

## Local Development with Stripe CLI

### Install Stripe CLI
```bash
# macOS
brew install stripe/stripe-cli/stripe

# Windows
# Download from https://stripe.com/docs/stripe-cli

# Linux
curl -s https://packages.stripe.com/api/security/v1/keys/.../stripe-el7-x86_64.tar.gz | tar xz
sudo mv stripe /usr/local/bin/
```

### Login to Stripe
```bash
stripe login
```

### Forward Webhooks Locally
```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

This will give you a webhook secret for local testing.

### Trigger Test Events
```bash
# Trigger successful payment
stripe trigger invoice.payment_succeeded

# Trigger failed payment
stripe trigger invoice.payment_failed

# Trigger subscription created
stripe trigger customer.subscription.created

# Trigger subscription deleted
stripe trigger customer.subscription.deleted
```

## Additional Configuration

### Cashier Configuration

You can customize Cashier behavior in `config/cashier.php` (if published):

```php
return [
    'currency' => env('CASHIER_CURRENCY', 'usd'),
    'currency_locale' => env('CASHIER_CURRENCY_LOCALE', 'en'),
    'logger' => env('CASHIER_LOGGER'),
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
        'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],
];
```

### Plan Configuration

Update `database/seeders/PlansSeeder.php` with your actual Stripe price IDs:

```php
[
    'name' => 'Pro',
    'slug' => 'pro',
    'stripe_price_id' => env('STRIPE_PRO_PRICE_ID'), // Update this
    'price' => 2900,
    // ...
],
```

## Security Best Practices

1. **Never commit `.env` file** to version control
2. **Use different keys** for test and production
3. **Rotate webhook secrets** periodically
4. **Use environment variables** for all sensitive data
5. **Restrict API key permissions** in Stripe Dashboard

## Troubleshooting

### Webhook Verification Fails
- Ensure `STRIPE_WEBHOOK_SECRET` matches your webhook endpoint
- Check webhook tolerance setting (default 300 seconds)
- Verify webhook events are enabled in Stripe Dashboard

### Stripe API Errors
- Verify API keys are correct
- Check if you're in test or live mode
- Ensure products and prices exist in Stripe

### Payment Processing Issues
- Check Stripe account status
- Verify payment methods are enabled
- Review Stripe dashboard for failed payments

## Environment Variable Template

```env
# ============================================
# STRIPE CONFIGURATION
# ============================================
STRIPE_KEY=pk_test_your_key_here
STRIPE_SECRET=sk_test_your_secret_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here

# Stripe Price IDs
STRIPE_PRO_PRICE_ID=price_pro_plan_id_here
STRIPE_ENTERPRISE_PRICE_ID=price_enterprise_plan_id_here

# Cashier Configuration
CASHIER_CURRENCY=usd
CASHIER_CURRENCY_LOCALE=en
```

---

**Note**: Replace all placeholder values with your actual Stripe credentials before deploying to production.