-- Migration: Add Stripe columns to payment and refund tables
-- Run this SQL script in your database

USE ptud;

-- Add Stripe-specific columns to payment table
ALTER TABLE payment 
ADD COLUMN IF NOT EXISTS stripe_payment_intent_id VARCHAR(100) COMMENT 'Stripe PaymentIntent ID',
ADD COLUMN IF NOT EXISTS stripe_charge_id VARCHAR(100) COMMENT 'Stripe Charge ID (for refunds)',
ADD COLUMN IF NOT EXISTS payment_method_type VARCHAR(50) COMMENT 'card, wallet, etc.',
ADD COLUMN IF NOT EXISTS metadata JSON COMMENT 'Additional payment metadata';

-- Add indexes for Stripe fields
CREATE INDEX IF NOT EXISTS idx_stripe_payment_intent ON payment(stripe_payment_intent_id);
CREATE INDEX IF NOT EXISTS idx_stripe_charge ON payment(stripe_charge_id);

-- Add Stripe-specific columns to refund table
ALTER TABLE refund
ADD COLUMN IF NOT EXISTS stripe_refund_id VARCHAR(100) COMMENT 'Stripe refund ID',
ADD COLUMN IF NOT EXISTS refunded_at TIMESTAMP NULL COMMENT 'When refund was completed';

-- Create index for Stripe refund ID
CREATE INDEX IF NOT EXISTS idx_stripe_refund ON refund(stripe_refund_id);

-- Update payment method enum to include Stripe payment types
-- Note: This will recreate the column, so run carefully
ALTER TABLE payment 
MODIFY COLUMN method ENUM('COD', 'BANK', 'STRIPE_CARD', 'STRIPE_WALLET') NOT NULL;

-- Verify changes
SHOW COLUMNS FROM payment;
SHOW COLUMNS FROM refund;

SELECT 'Migration completed successfully!' as status;
