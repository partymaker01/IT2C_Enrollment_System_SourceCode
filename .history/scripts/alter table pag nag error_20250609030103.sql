ALTER TABLE students ADD COLUMN date_registered DATETIME DEFAULT CURRENT_TIMESTAMP;


-- Add the formatted_id column to students table
ALTER TABLE students ADD COLUMN formatted_id VARCHAR(20) NULL;

-- Create index for better performance
CREATE INDEX idx_formatted_id ON students(formatted_id);

-- Check if column was added successfully
DESCRIBE students;

SELECT 'formatted_id column added successfully!' as status;



-- Add formatted_id column to students table if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'students' 
    AND COLUMN_NAME = 'formatted_id'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE students ADD COLUMN formatted_id VARCHAR(20) NULL', 
    'SELECT "formatted_id column already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create index for better performance
SET @index_sql = IF(@column_exists = 0, 
    'CREATE INDEX idx_formatted_id ON students(formatted_id)', 
    'SELECT "Index already exists or not needed" as message'
);

PREPARE stmt FROM @index_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Setup completed successfully!' as status;
