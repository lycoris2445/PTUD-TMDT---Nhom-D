-- Migration: Add Stripe columns to PAYMENT and REFUND tables
-- Run this SQL script in your database

USE ptud_tmdt;

-- Add Stripe-specific columns to PAYMENT table
ALTER TABLE PAYMENT 
ADD COLUMN IF NOT EXISTS stripe_payment_intent_id VARCHAR(100) COMMENT 'Stripe PaymentIntent ID',
ADD COLUMN IF NOT EXISTS stripe_charge_id VARCHAR(100) COMMENT 'Stripe Charge ID (for refunds)',
ADD COLUMN IF NOT EXISTS payment_method_type VARCHAR(50) COMMENT 'card, wallet, etc.',
ADD COLUMN IF NOT EXISTS metadata JSON COMMENT 'Additional payment metadata';

-- Add indexes for Stripe fields
CREATE INDEX IF NOT EXISTS idx_stripe_payment_intent ON PAYMENT(stripe_payment_intent_id);
CREATE INDEX IF NOT EXISTS idx_stripe_charge ON PAYMENT(stripe_charge_id);

-- Add Stripe-specific columns to REFUND table
ALTER TABLE REFUND
ADD COLUMN IF NOT EXISTS stripe_refund_id VARCHAR(100) COMMENT 'Stripe Refund ID',
ADD COLUMN IF NOT EXISTS refunded_at TIMESTAMP NULL COMMENT 'When refund was completed';

-- Create index for Stripe refund ID
CREATE INDEX IF NOT EXISTS idx_stripe_refund ON REFUND(stripe_refund_id);

-- Update PAYMENT method enum to include Stripe payment types
-- Note: This will recreate the column, so run carefully
ALTER TABLE PAYMENT 
MODIFY COLUMN method ENUM('COD', 'BANK', 'STRIPE_CARD', 'STRIPE_WALLET') NOT NULL;

-- Verify changes
SHOW COLUMNS FROM PAYMENT;
SHOW COLUMNS FROM REFUND;

SELECT 'Migration completed successfully!' as status;
