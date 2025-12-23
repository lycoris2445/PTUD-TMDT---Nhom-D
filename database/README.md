# Database Files

This directory contains SQL files that are automatically executed when the MySQL Docker container starts for the first time.

## Execution Order

Files are executed in **alphabetical order**:

1. **00-init.sql** - Database initialization and version tracking
2. **01-schema.sql** - Complete database schema (tables, indexes, foreign keys)
3. **02-data.sql** - Sample data for testing
4. **03-stripe-migration.sql** - Stripe payment integration columns
5. **04-payment-method-column.sql** - Additional payment method column

## Original Files (kept for reference)

- `Script.sql` - Original schema file
- `data.sql` - Original data file (duplicated as 02-data.sql)
- `stripe_migration.sql` - Original migration (duplicated as 03-stripe-migration.sql)
- `add_payment_method_column.sql` - Original migration (duplicated as 04-payment-method-column.sql)

## Database Configuration

**Database Name**: `ptud_tmdt`
**Character Set**: `utf8mb4`
**Collation**: `utf8mb4_unicode_ci`

## Resetting Database

If you need to reset the database:

```bash
# Stop and remove volumes
docker compose down -v

# Start fresh (will re-execute all SQL files)
docker compose up -d
```

## Notes

- All numbered files (00-*, 01-*, etc.) include `USE ptud_tmdt;` to ensure they run against the correct database
- The `_database_version` table tracks which migrations have been applied
- Files are only executed on **first container startup** or after volume reset
