-- ALTER script to update existing database to match ERD diagram
-- This script adds the missing Jumlah_Lembur column to DETAIL_GAJI table
-- and updates the GAJI table structure

-- Add Total_Lembur column to GAJI table if it doesn't exist
ALTER TABLE GAJI 
ADD COLUMN IF NOT EXISTS Total_Lembur DECIMAL(12,2) DEFAULT 0 
AFTER Total_Tunjangan;

-- Add Jumlah_Lembur column to DETAIL_GAJI table if it doesn't exist
ALTER TABLE DETAIL_GAJI 
ADD COLUMN IF NOT EXISTS Jumlah_Lembur DECIMAL(12,2) DEFAULT 0 
AFTER Jumlah_Potongan;

-- Remove Nominal_Gapok column from DETAIL_GAJI if it exists (not in ERD)
ALTER TABLE DETAIL_GAJI 
DROP COLUMN IF EXISTS Nominal_Gapok;

-- Remove Uang_Lembur column from PRESENSI if it exists (not in ERD)
ALTER TABLE PRESENSI 
DROP COLUMN IF EXISTS Uang_Lembur;
