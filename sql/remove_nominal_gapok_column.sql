-- Database migration: Remove Nominal_Gapok column from DETAIL_GAJI table
-- This removes the redundant column since we can get the salary amount from GAJI_POKOK table

ALTER TABLE DETAIL_GAJI DROP COLUMN Nominal_Gapok;

-- Verify the changes
DESCRIBE DETAIL_GAJI;