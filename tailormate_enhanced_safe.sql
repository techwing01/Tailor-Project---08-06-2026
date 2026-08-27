-- TailorMate Safe Enhancement Migration
-- Apply only after the original database backup has been verified.

START TRANSACTION;

-- 1. Normalize existing order statuses
UPDATE orders
SET order_status = LOWER(TRIM(order_status))
WHERE order_status IS NOT NULL;

-- 2. Convert unexpected statuses to pending
UPDATE orders
SET order_status = 'pending'
WHERE order_status IS NULL
   OR order_status NOT IN (
       'pending',
       'in progress',
       'ready',
       'delivered',
       'cancelled'
   );

-- 3. Ensure payment fields have sensible defaults
ALTER TABLE orders
    MODIFY COLUMN paid_amount DECIMAL(10,2) DEFAULT 0.00,
    MODIFY COLUMN balance DECIMAL(10,2) DEFAULT 0.00;

-- 4. Remove legacy duplicate fields
ALTER TABLE orders DROP COLUMN status;
ALTER TABLE orders DROP COLUMN amount;

-- 5. Remove unused legacy admin table
DROP TABLE IF EXISTS admin;

COMMIT;
