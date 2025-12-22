# Stripe Webhook Setup - Local Development

## Prerequisites
- Stripe CLI installed
- Stripe account with test mode enabled
- Project running on Laragon

## Setup Steps

### 1. Install Stripe CLI

**Windows:**
```bash
# Download from: https://github.com/stripe/stripe-cli/releases/latest
# Or use Scoop:
scoop install stripe
```

**Mac/Linux:**
```bash
brew install stripe/stripe-cli/stripe
```

### 2. Login to Stripe

```bash
stripe login
```

This will open a browser window to authorize the CLI with your Stripe account.

### 3. Forward Webhooks to Local Server

```bash
stripe listen --forward-to localhost/PTUD%20TMĐT%20-%20Nhóm%20D/api/payments/stripe-webhook.php
```

**Expected Output:**
```
> Ready! Your webhook signing secret is whsec_xxxxxxxxxxxxxxxxxxxxx (^C to quit)
```

### 4. Update .env File

Copy the webhook signing secret from the CLI output and add it to your `.env` file:

```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxx
```

### 5. Keep CLI Running

**Important:** The Stripe CLI must remain running in a terminal window to forward webhooks. You'll see webhook events logged in real-time:

```
2025-12-22 10:30:15  --> payment_intent.created [evt_xxx]
2025-12-22 10:30:20  --> payment_intent.succeeded [evt_xxx]
2025-12-22 10:30:20  <--  [200] POST http://localhost/PTUD%20TMĐT%20-%20Nhóm%20D/api/payments/stripe-webhook.php [evt_xxx]
```

## Testing Webhooks

### Test Payment Success

```bash
stripe trigger payment_intent.succeeded
```

### Test Payment Failure

```bash
stripe trigger payment_intent.payment_failed
```

### Test Refund

```bash
stripe trigger charge.refunded
```

## Webhook Events Handled

Our webhook handler (`stripe-webhook.php`) processes these events:

1. **payment_intent.succeeded**
   - Updates PAYMENT status to 'paid'
   - Updates ORDERS status to 'on_hold'
   - Saves stripe_charge_id
   - Logs to ORDER_HISTORY

2. **payment_intent.payment_failed**
   - Updates PAYMENT status to 'failed'
   - Updates ORDERS status to 'cancelled'
   - Logs error message

3. **charge.refunded**
   - Updates REFUND status to 'completed'
   - If fully refunded, updates ORDERS status to 'refunded'
   - Records refund timestamp

## Troubleshooting

### Webhook not receiving events

1. Check Stripe CLI is running
2. Verify the forward-to URL matches your project path
3. Check .env has correct STRIPE_WEBHOOK_SECRET

### Signature verification fails

```
Error: Invalid signature
```

**Solution:** Make sure STRIPE_WEBHOOK_SECRET in .env matches the one shown by Stripe CLI.

### Webhook succeeds but database not updated

Check PHP error logs:
```bash
# Laragon log location:
C:\laragon\www\PTUD TMĐT - Nhóm D\storage\logs\
# Or check Apache error log
```

Look for lines starting with `[STRIPE WEBHOOK]`

## Production Deployment

For production, instead of Stripe CLI:

1. Go to Stripe Dashboard → Developers → Webhooks
2. Add endpoint: `https://yourdomain.com/api/payments/stripe-webhook.php`
3. Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`, `charge.refunded`
4. Copy the webhook signing secret
5. Update production .env with the new secret

## Webhook Log Format

Successful event:
```
[STRIPE WEBHOOK] Received event
[STRIPE WEBHOOK] Event type: payment_intent.succeeded
[STRIPE WEBHOOK] Event ID: evt_xxx
[STRIPE WEBHOOK] Processing payment success for Order #1050
[STRIPE WEBHOOK] ✅ Payment successful for Order #1050
```

Failed verification:
```
[STRIPE WEBHOOK] ⚠️ Signature verification failed: Invalid signature
```

Error handling:
```
[STRIPE WEBHOOK] ❌ Error: Order ID not found in payment metadata
```

## Best Practices

1. **Always keep Stripe CLI running** during development
2. **Monitor webhook logs** to ensure events are being processed
3. **Test all payment flows** (success, failure, refund) before deploying
4. **Use test cards** from Stripe documentation for testing
5. **Never commit** webhook secrets to version control

## Useful Commands

```bash
# List all webhook events
stripe events list

# Get details of a specific event
stripe events retrieve evt_xxxxxxxxxxxxx

# Resend a webhook event
stripe events resend evt_xxxxxxxxxxxxx

# View webhook logs
stripe logs tail
```

## Test Cards

**Successful Payment:**
- Card: 4242 4242 4242 4242
- Exp: Any future date
- CVC: Any 3 digits

**Payment Declined:**
- Card: 4000 0000 0000 0002

**Requires Authentication:**
- Card: 4000 0025 0000 3155

More test cards: https://stripe.com/docs/testing#cards
