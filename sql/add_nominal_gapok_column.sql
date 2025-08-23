-- Database migration: Add Nominal_Gapok column to DETAIL_GAJI table
-- This fixes the "Unknown column 'Nominal_Gapok'" error

ALTER TABLE DETAIL_GAJI ADD COLUMN Nominal_Gapok DECIMAL(12,2) DEFAULT 0 AFTER Id_Gapok;

-- Verify the changes
DESCRIBE DETAIL_GAJI;