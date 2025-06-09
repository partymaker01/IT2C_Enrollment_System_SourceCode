ALTER TABLE student_subjects
ADD COLUMN semester VARCHAR(50),
ADD COLUMN school_year VARCHAR(50);

ALTER TABLE students
ADD COLUMN date_registered DATETIME DEFAULT CURRENT_TIMESTAMP;


-- wag mo ilalagay ito pag hindi nag error ang fil-up form
-- don't put this if the fill-up form doesn't have an error
ALTER TABLE enrollments DROP INDEX student_id;
ALTER TABLE enrollments ADD UNIQUE KEY uniq_enrollment (student_id, semester, school_year);