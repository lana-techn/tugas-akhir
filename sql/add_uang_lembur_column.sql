-- Database migration: Add Uang_Lembur column to PRESENSI table
-- This fixes the "Unknown column 'Uang_Lembur'" error

ALTER TABLE PRESENSI ADD COLUMN Uang_Lembur DECIMAL(12,2) DEFAULT 0 AFTER Jam_Lembur;

-- Update existing records to calculate overtime pay at 20,000 per hour
UPDATE PRESENSI SET Uang_Lembur = Jam_Lembur * 20000 WHERE Jam_Lembur > 0 AND (Uang_Lembur IS NULL OR Uang_Lembur = 0);

-- Verify the changes
SELECT * FROM PRESENSI LIMIT 5;