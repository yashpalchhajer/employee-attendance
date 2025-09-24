# Employee Attendance System

A simple PHP-based employee attendance system with location tracking and admin panel.

## Features

- **Employee Login**: Secure authentication for employees
- **Attendance Marking**: One-click attendance with GPS location capture
- **Admin Panel**: Complete employee management and attendance monitoring
- **Location Tracking**: Automatic GPS coordinates and address capture
- **Responsive Design**: Works on desktop and mobile devices
- **Database Storage**: All data securely stored in MySQL database

## Requirements

- PHP 7.0 or higher
- MySQL 5.6 or higher
- Web server (Apache/Nginx)
- Modern web browser with location services

## Installation

1. **Upload Files**: Extract and upload all files to your web server directory

2. **Create Database**: 
   - Create a new MySQL database named `employee_attendance`
   - Import the `database.sql` file into your database
   - Or run the SQL commands manually from the database.sql file

3. **Configure Database**:
   - Open `config.php`
   - Update the database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USERNAME', 'your_db_username');
     define('DB_PASSWORD', 'your_db_password');
     define('DB_NAME', 'employee_attendance');
     ```

4. **Set Permissions**:
   - Ensure the web server has read/write access to all files
   - Set appropriate file permissions (typically 644 for files, 755 for directories)

5. **Access the System**:
   - Navigate to your website URL
   - You'll be redirected to the login page

## Default Login Credentials

### Admin Account
- **Username**: admin
- **Password**: admin123

### Sample Employee Account
- **Username**: john_doe
- **Password**: password123

**Important**: Change these default passwords after installation!

## File Structure

```
employee_attendance/
├── index.php                 # Main entry point (redirects to login)
├── login.php                # Login page
├── logout.php               # Logout handler
├── config.php               # Database configuration
├── dashboard.php            # Employee dashboard
├── mark_attendance.php      # Attendance marking handler
├── database.sql             # Database schema and default data
├── README.md               # This file
├── admin/
│   ├── dashboard.php        # Admin dashboard
│   ├── employees.php        # Employee management
│   └── attendance.php       # Attendance reports
└── assets/
    ├── css/
    │   └── style.css        # Main stylesheet
    └── js/
        └── dashboard.js     # JavaScript functionality
```

## Usage

### For Employees:
1. Login with your credentials
2. Click "Mark Attendance" button
3. Allow location access when prompted
4. Attendance will be recorded with timestamp and location

### For Administrators:
1. Login with admin credentials
2. **Dashboard**: View attendance statistics and recent records
3. **Manage Employees**: Add new employees, enable/disable accounts
4. **View Attendance**: Filter and view attendance by date

## Features in Detail

### Location Tracking
- Uses browser's geolocation API for precise coordinates
- Reverse geocoding to get readable addresses
- Fallback handling when location services are unavailable

### Security Features
- Password hashing using PHP's password_hash()
- SQL injection prevention with prepared statements
- Session management and access control
- CSRF protection through proper form handling

### Admin Capabilities
- Add unlimited employees
- Enable/disable employee accounts
- View detailed attendance reports
- Filter attendance by date
- See present/absent employee lists

## Troubleshooting

### Common Issues:

1. **Database Connection Error**:
   - Check database credentials in config.php
   - Ensure database exists and is accessible
   - Verify MySQL service is running

2. **Location Not Working**:
   - Ensure HTTPS connection (required for geolocation)
   - Check browser permissions for location access
   - Verify internet connection for address lookup

3. **Login Issues**:
   - Check if default admin user exists in database
   - Verify password hashing is working correctly
   - Clear browser cache and cookies

4. **Permission Denied**:
   - Set proper file permissions on server
   - Ensure web server can read/write files

## Browser Compatibility

- Chrome 50+
- Firefox 45+
- Safari 10+
- Edge 14+
- Opera 37+

## Security Considerations

1. **Change Default Passwords**: Immediately change admin password after installation
2. **Use HTTPS**: Enable SSL/TLS for secure communication
3. **Regular Backups**: Backup database regularly
4. **Update Regularly**: Keep PHP and MySQL updated
5. **File Permissions**: Set restrictive file permissions
6. **Database Security**: Use strong database passwords and limit access

## Support

For support and questions:
- Check this README for common issues
- Verify all installation steps were followed
- Ensure server requirements are met
- Check browser console for JavaScript errors

## License

This is a free and open-source project. You can modify and distribute as needed.

---

**Note**: This system is designed for shared hosting compatibility and uses standard PHP/MySQL without external dependencies.