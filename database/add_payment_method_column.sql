-- Migration: Add payment_method column to ORDERS table
-- Created: 2025-12-22
-- Purpose: Add payment method tracking to orders for team members who don't have this column yet

USE darling_cosmetics;

-- Check if column exists before adding (for MySQL 5.7+)
-- This script is safe to run multiple times

-- Add payment_method column to ORDERS table
ALTER TABLE ORDERS 
ADD COLUMN IF NOT EXISTS payment_method VARCHAR(20) DEFAULT 'COD' 
COMMENT 'Payment method: COD, STRIPE_CARD, STRIPE_WALLET, BANK' 
AFTER total_amount;

-- Verify the change
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    CHARACTER_MAXIMUM_LENGTH, 
    COLUMN_DEFAULT, 
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'darling_cosmetics' 
  AND TABLE_NAME = 'ORDERS' 
  AND COLUMN_NAME = 'payment_method';

-- Show success message
SELECT 'payment_method column added successfully!' as Status;

-- Optional: Update existing orders without payment_method to 'COD'
-- Uncomment the line below if you want to update existing NULL values
-- UPDATE ORDERS SET payment_method = 'COD' WHERE payment_method IS NULL;
