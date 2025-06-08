ALTER TABLE students ADD COLUMN date_registered DATETIME DEFAULT CURRENT_TIMESTAMP;


-- Add the formatted_id column to students table
ALTER TABLE students ADD COLUMN formatted_id VARCHAR(20) NULL;

-- Create index for better performance
CREATE INDEX idx_formatted_id ON students(formatted_id);

-- Check if column was added successfully
DESCRIBE students;

SELECT 'formatted_id column added successfully!' as status;

