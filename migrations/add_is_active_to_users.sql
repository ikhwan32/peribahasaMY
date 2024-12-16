-- First modify the role enum to include both old and new values
ALTER TABLE users
MODIFY COLUMN role ENUM('admin', 'moderator', 'contributor', 'user') NOT NULL DEFAULT 'user';

-- Update contributor to user
UPDATE users 
SET role = 'user' 
WHERE role = 'contributor';

-- Now remove the old contributor value from enum
ALTER TABLE users
MODIFY COLUMN role ENUM('admin', 'moderator', 'user') NOT NULL DEFAULT 'user';

-- Add is_active column (will error if exists, that's OK)
ALTER TABLE users
ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT TRUE;
