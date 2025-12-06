-- MySQL initialization script
-- Grants full privileges to mob_user for test databases
-- This script runs automatically when MySQL container starts for the first time

GRANT ALL PRIVILEGES ON *.* TO 'mob_user'@'%';
FLUSH PRIVILEGES;

-- Log for debugging
SELECT 'MySQL initialization complete: mob_user has been granted all privileges' AS status;
