# ClinIQ - School Clinic Management System

A web-based clinic management system for schools, built with PHP and MySQL.

## Requirements

- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- XAMPP, WAMP, or LAMP stack recommended

## Installation

### 1. Clone or Download

Clone the repository or download the ZIP file and extract it to your web server's document root (e.g., `htdocs` for XAMPP).

### 2. Database Setup

#### Option A: Using phpMyAdmin
1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Create a new database named `school_clinic_db`
3. Import the schema file:
   - Go to the `Import` tab
   - Select `schema/school_clinic_db.sql` from the project folder
   - Click "Go" to import

#### Option B: Using MySQL Command Line
```bash
mysql -u root -p
CREATE DATABASE school_clinic_db;
USE school_clinic_db;
SOURCE path/to/schema/school_clinic_db.sql;
```

### 3. Configure Database Connection

1. Copy `config/database.example.php` to `config/database.php`
2. Edit `config/database.php` with your database credentials:

```php
<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'school_clinic_db';
    private $username = 'root';
    private $password = ''; // Your MySQL password
    
    public function connect() {
        // ... rest of the code
    }
}
```

### 4. Set File Permissions

Ensure the following directories are writable:
- `uploads/` - For file uploads (certificates, backups)
- `uploads/certificates/`
- `uploads/backups/`

On Linux/Mac:
```bash
chmod -R 755 uploads/
chmod -R 777 uploads/certificates/
chmod -R 777 uploads/backups/
```

On Windows, these should already be writable if using XAMPP.

### 5. Access the Application

Open your browser and navigate to:
```
http://localhost/school-clinic-main/
```

## Default Login

The system comes with a default admin account:
- **Username:** admin
- **Password:** admin123

**Important:** Change the default password after first login!

## Features

- **Student Records** - Manage student information and health assessments
- **Medicine Inventory** - Track clinic medicines and stock levels
- **Visit Logs** - Record student clinic visits
- **Health Clearance** - Enrollment health assessment tracking
- **Certificate Management** - Issue and manage health certificates
- **Reports** - Generate various clinic reports
- **Settings** - Configure clinic information and system settings

## Folder Structure

```
school-clinic-main/
├── actions/          # PHP action handlers
├── assets/           # CSS, JS, images
├── classes/          # PHP classes (Student, Visit, etc.)
├── config/           # Configuration files
├── includes/         # Reusable PHP includes
├── modules/          # Main application pages
├── schema/           # Database schema files
└── uploads/          # User-uploaded files
```

## Troubleshooting

### Database Connection Error
- Verify MySQL is running
- Check database credentials in `config/database.php`
- Ensure the database `school_clinic_db` exists

### File Upload Errors
- Check folder permissions for `uploads/` directory
- Verify PHP upload limits in `php.ini`:
  - `upload_max_filesize`
  - `post_max_size`

### Session Issues
- Check `session.save_path` in `php.ini`
- Ensure the session directory is writable

## Backup

The system includes automatic backup functionality. Backups are stored in `uploads/backups/`.

To create a manual backup:
1. Go to Settings → System tab
2. Click "Create Backup Now"

## Support

For issues or questions, please contact the development team.

## License

This project is proprietary software. All rights reserved.
