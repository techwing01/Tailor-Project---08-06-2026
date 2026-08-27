-- ============================================
-- TailorMate Enhanced - Database Migration
-- Run this to clean up the database schema.
-- Apply on your existing tailormate database.
-- ============================================

-- 1. Fix order_status inconsistency: standardize to lowercase
UPDATE orders SET order_status = LOWER(TRIM(order_status)) WHERE order_status IS NOT NULL;

-- 2. Remove redundant 'status' and 'amount' columns from orders
ALTER TABLE orders DROP COLUMN IF EXISTS `status`;
ALTER TABLE orders DROP COLUMN IF EXISTS `amount`;

-- 3. Ensure paid_amount and balance columns exist and have defaults
ALTER TABLE orders 
  MODIFY COLUMN `paid_amount` DECIMAL(10,2) DEFAULT 0.00,
  MODIFY COLUMN `balance` DECIMAL(10,2) DEFAULT 0.00;

-- 4. Standardize order_status to use consistent values
UPDATE orders SET order_status = 'pending' WHERE order_status NOT IN ('pending', 'in progress', 'ready', 'delivered', 'cancelled');

-- 5. Add email column to customers if missing (already exists in dump)
-- Already present in schema

-- 6. Clean up empty measurement records (all NULL values except customer_id/garment_type/created_at)
DELETE FROM measurements 
WHERE pant_waist IS NULL AND pant_seat IS NULL AND pant_length IS NULL
  AND shirt_length IS NULL AND shirt_shoulder IS NULL AND shirt_sleeve_length IS NULL;

-- 7. Drop unused admin table (users table is the active one)
DROP TABLE IF EXISTS `admin`;

-- 8. Add index on orders.customer_id for better join performance
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_customer_id (customer_id);

-- 9. Add index on orders.order_date for sorting
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_order_date (order_date);

-- 10. Add index on measurements for customer+garment lookup
ALTER TABLE measurements ADD INDEX IF NOT EXISTS idx_customer_garment (customer_id, garment_type);

-- 11. Update order_status enum-like check (informational - MariaDB uses varchar)
-- The application layer now handles status validation.

-- ============================================
-- NOTE: After applying this migration, run
-- migrate_passwords.php ONCE to hash passwords.
-- Then DELETE migrate_passwords.php!
-- ============================================