-- ==========================================
-- DOCKER AUTO-INIT DATABASE SCRIPT
-- ==========================================
-- This file is automatically executed when MySQL container starts for the first time
-- Files in /docker-entrypoint-initdb.d are executed in alphabetical order
-- ==========================================

-- Create database if not exists (Docker already creates it, but this is a safety check)
CREATE DATABASE IF NOT EXISTS ptud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ptud;

-- Note: The actual schema and data will be loaded from other SQL files in this directory
-- Files are executed in this order:
-- 1. 00-init.sql (this file)
-- 2. Script.sql (schema)
-- 3. data.sql (sample data)
-- 4. stripe_migration.sql (payment columns)
-- 5. add_payment_method_column.sql (additional columns)

-- Create a marker table to check if database is initialized
CREATE TABLE IF NOT EXISTS _database_version (
    version VARCHAR(50) PRIMARY KEY,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO _database_version (version) VALUES ('1.0.0') ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP;
