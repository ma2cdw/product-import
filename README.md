Installation:

Run composer to import the required dependencies.

Alter the database table with the command in update_database.sql

Running:

php productImport.php "/path/to/csv/file.csv" "/path/to/save/report.txt"

use --test to turn off database import 

additional arguments for database connection

--host defaults to localhost if not set
--user defaults to root if not set
--pass defaults to no password if not set
--name database name