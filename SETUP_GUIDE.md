# LibriX Setup & Deployment Guide

Complete guide for setting up and deploying the LibriX Library Management System.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Project Structure](#project-structure)
3. [Database Setup](#database-setup)
4. [Backend Configuration](#backend-configuration)
5. [Frontend Configuration](#frontend-configuration)
6. [BookMind Integration](#bookmind-integration)
7. [Running the Application](#running-the-application)
8. [Deployment](#deployment)
9. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Required Software

- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher / MariaDB 10.3+
- **Web Server**: Apache (with mod_rewrite) or Nginx
- **Node.js**: 14+ (for frontend development, optional)
- **Python**: 3.8+ (for BookMind recommendations, optional)

### Required PHP Extensions

- PDO
- PDO_MySQL
- JSON
- MBString
- OpenSSL
- Fileinfo
- GD (for image processing)

---

## Project Structure

```
librix/
├── librix-backend/          # PHP Backend API
│   ├── api/                 # API endpoints
│   │   └── v1/             # API version 1
│   ├── config/             # Configuration files
│   ├── helpers/            # Helper functions
│   ├── middleware/         # Authentication middleware
│   ├── uploads/            # File upload directory
│   ├── logs/               # Application logs
│   ├── index.php           # Main entry point
│   └── db.sql              # Database schema
├── librix-frontend/        # Frontend Application
│   ├── assets/             # CSS, JS, images
│   ├── pages/              # HTML pages
│   └── index.html          # Main entry point
├── bookmind/               # ML Recommendation Engine (optional)
│   ├── api/                # FastAPI endpoints
│   ├── models/             # Trained ML models
│   └── app.py              # Streamlit dashboard
└── SETUP_GUIDE.md          # This file
```

---

## Database Setup

### 1. Create Database

The database schema is included in `librix-backend/db.sql`. You can import it using:

**Option 1: Using PHP Scripts (Recommended)**

```bash
# Reset and create database
php reset_database.php

# Import schema
php import_schema.php
```

**Option 2: Using MySQL Command Line**

```bash
mysql -u root -p < librix-backend/db.sql
```

**Option 3: Using phpMyAdmin**

1. Open phpMyAdmin
2. Create a new database named `librix`
3. Import the `librix-backend/db.sql` file

### 2. Configure Database Connection

Edit `librix-backend/config/database.php`:

```php
$Host = "localhost";
$Port = "3306";
$DbName = "librix";
$Username = "root";
$Password = "your_password";
```

### 3. Default Admin User

The database includes a default admin user:
- **Email**: `admin@librix.com`
- **Password**: `password`

**⚠️ IMPORTANT**: Change this password after first login!

---

## Backend Configuration

### 1. Application Settings

Edit `librix-backend/config/config.php`:

```php
// Application
define("APP_NAME", "LibriX");
define("APP_VERSION", "1.1.2");
define("APP_ENV", "development"); // or "production"

// API Configuration
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define("BASE_URL", "$protocol://$host");

// Security
define("JWT_SECRET", "CHANGE_THIS_TO_A_LONG_RANDOM_SECRET");

// CORS
define("CORS_ORIGIN", "*"); // Restrict in production

// Business Rules
define("FINE_RATE_PER_DAY", 5.00);
define("DEFAULT_LOAN_DAYS", 14);
```

### 2. BookMind Configuration

Edit `librix-backend/config/bookmind.php`:

```php
// BookMind API URL
define("BOOKMIND_API_URL", "http://127.0.0.1:8000");

// Fallback behavior
define("BOOKMIND_FALLBACK_ENABLED", true);
define("BOOKMEND_TIMEOUT", 5);
```

### 3. File Upload Permissions

Ensure the upload directories are writable:

```bash
chmod -R 755 librix-backend/uploads/
chmod -R 755 librix-backend/logs/
```

Create required subdirectories:

```bash
mkdir -p librix-backend/uploads/profile-pictures
mkdir -p librix-backend/uploads/org-logos
mkdir -p librix-backend/uploads/book-covers
mkdir -p librix-backend/uploads/other
```

---

## Frontend Configuration

### 1. API Base URL

Edit `librix-frontend/assets/js/api.js`:

```javascript
const API_BASE_URL = 'http://localhost:8000/index.php/api/v1';
```

For production, update to your production URL:

```javascript
const API_BASE_URL = 'https://your-domain.com/api/v1';
```

### 2. Environment Variables

Create `.env` file in `librix-frontend/` (optional):

```
API_BASE_URL=http://localhost:8000/index.php/api/v1
APP_ENV=development
```

---

## BookMind Integration (Optional)

BookMind provides ML-powered book recommendations. This is optional as the system has fallback database recommendations.

### 1. Install Python Dependencies

```bash
cd bookmind
python -m venv .venv
source .venv/bin/activate  # On Windows: .venv\Scripts\activate
pip install -r requirements.txt
```

### 2. Train ML Models

```bash
python src/train_all.py
```

### 3. Start BookMind API

```bash
uvicorn api.main:app --reload --port 8000
```

### 4. Test BookMind

```bash
curl http://localhost:8000/recommendations/popular?limit=5
```

### 5. Configure Backend

The backend will automatically use BookMind if available. Ensure `config/bookmind.php` points to the correct URL.

---

## Running the Application

### Development Mode

**1. Start Backend Server**

```bash
cd librix-backend
php -S localhost:8000
```

Backend will be available at: `http://localhost:8000`

**2. Start Frontend Server**

```bash
cd librix-frontend
python -m http.server 5500
```

Frontend will be available at: `http://localhost:5500`

**3. Start BookMind (Optional)**

```bash
cd bookmind
uvicorn api.main:app --reload --port 8001
```

### Production Mode

**Using Apache**

1. Configure Apache virtual host:

```apache
<VirtualHost *:80>
    ServerName librix.example.com
    DocumentRoot /path/to/librix/librix-frontend
    
    <Directory /path/to/librix/librix-frontend>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Proxy API requests to PHP backend
    ProxyPass /api/ http://localhost:8000/api/
    ProxyPassReverse /api/ http://localhost:8000/api/
</VirtualHost>
```

2. Enable mod_rewrite and mod_proxy:

```bash
a2enmod rewrite proxy proxy_http
systemctl restart apache2
```

**Using Nginx**

```nginx
server {
    listen 80;
    server_name librix.example.com;
    root /path/to/librix/librix-frontend;
    index index.html;
    
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    location /api/ {
        proxy_pass http://localhost:8000/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

---

## Deployment

### 1. Pre-Deployment Checklist

- [ ] Change default admin password
- [ ] Update JWT_SECRET in config
- [ ] Set APP_ENV to "production"
- [ ] Configure CORS_ORIGIN to your domain
- [ ] Set up SSL/HTTPS
- [ ] Configure database backups
- [ ] Set up monitoring/logging
- [ ] Test all API endpoints
- [ ] Review file upload permissions

### 2. Production Configuration

**Backend Config (`config/config.php`)**:

```php
define("APP_ENV", "production");
define("JWT_SECRET", "your-long-random-secret-here");
define("CORS_ORIGIN", "https://your-domain.com");
error_reporting(0);
ini_set("display_errors", 0);
```

**Database Config (`config/database.php`)**:

```php
$Host = "localhost";
$Username = "librix_user";
$Password = "strong_password_here";
$DbName = "librix_prod";
```

### 3. Security Hardening

**1. File Permissions**

```bash
# Set appropriate permissions
chmod 644 librix-backend/config/*.php
chmod 755 librix-backend/uploads/
chmod 644 librix-backend/uploads/*/*
```

**2. Disable Directory Browsing**

Create `.htaccess` in uploads directory:

```apache
Options -Indexes
```

**3. Enable HTTPS**

Use Let's Encrypt or purchase SSL certificate:

```bash
certbot --apache -d librix.example.com
```

**4. Set Up Firewall**

```bash
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

### 4. Database Backups

Set up automated backups:

```bash
# Backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u librix_user -p librix_prod > backup_$DATE.sql
gzip backup_$DATE.sql
```

Add to crontab:

```bash
0 2 * * * /path/to/backup-script.sh
```

### 5. Monitoring

**Enable Error Logging**

```php
// In config/config.php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/logs/error.log");
```

**Monitor Health Endpoint**

Set up monitoring for `GET /health` endpoint.

---

## Troubleshooting

### Common Issues

**1. Database Connection Failed**

```bash
# Check MySQL service
systemctl status mysql

# Test connection
mysql -u root -p -e "SELECT 1"

# Check credentials in config/database.php
```

**2. API Returns 404**

- Ensure backend server is running
- Check URL routing in `index.php`
- Verify `.htaccess` is configured correctly

**3. File Uploads Not Working**

```bash
# Check directory permissions
ls -la librix-backend/uploads/

# Fix permissions
chmod -R 755 librix-backend/uploads/
chown -R www-data:www-data librix-backend/uploads/
```

**4. CORS Errors**

- Check CORS_ORIGIN in config
- Verify preflight requests are handled
- Check browser console for specific errors

**5. Session/Auth Issues**

- Clear browser cookies and localStorage
- Check JWT_SECRET is consistent
- Verify token expiration time

**6. BookMind Not Working**

```bash
# Check if BookMind is running
curl http://localhost:8000/health

# Check Python dependencies
pip list

# Retrain models if needed
python src/train_all.py
```

### Debug Mode

Enable debug mode in development:

```php
// config/config.php
define("APP_ENV", "development");
error_reporting(E_ALL);
ini_set("display_errors", 1);
```

### Log Files

Check application logs:

```bash
# Backend logs
tail -f librix-backend/logs/error.log
tail -f librix-backend/logs/access.log

# PHP error logs
tail -f /var/log/php/error.log

# Apache/Nginx logs
tail -f /var/log/apache2/error.log
tail -f /var/log/nginx/error.log
```

---

## Performance Optimization

### 1. Database Optimization

```sql
-- Add indexes for common queries
CREATE INDEX idx_books_title ON books(title);
CREATE INDEX idx_issues_user_status ON book_issues(user_id, status);
CREATE INDEX idx_fines_user_status ON fines(user_id, status);
```

### 2. Enable OPcache

Edit `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

### 3. Enable Compression

Add to `.htaccess`:

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

### 4. Browser Caching

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

---

## Testing

### API Testing

**Using cURL**:

```bash
# Health check
curl http://localhost:8000/index.php/api/v1/health

# Login
curl -X POST http://localhost:8000/index.php/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@librix.com","password":"password"}'

# Get books
curl http://localhost:8000/index.php/api/v1/books
```

**Using Postman**:

1. Import API documentation
2. Set environment variables
3. Create collection for different endpoints
4. Run automated tests

### Frontend Testing

1. Open browser to `http://localhost:5500`
2. Test login functionality
3. Test book browsing
4. Test profile management
5. Test fine payments

---

## Support & Maintenance

### Regular Maintenance Tasks

**Weekly**:
- Check error logs
- Review system health
- Monitor database performance

**Monthly**:
- Update dependencies
- Review security patches
- Test backup restoration

**Quarterly**:
- Review and update documentation
- Performance audit
- Security audit

### Getting Help

- **API Documentation**: See `API_DOCUMENTATION.md`
- **Database Schema**: See `librix-backend/db.sql`
- **Issue Tracking**: Check logs in `librix-backend/logs/`
- **BookMind Docs**: See `bookmind/README.md`

---

## License

This project is proprietary software. All rights reserved.

---

## Version History

- **1.1.2** (2026-09-20): Added profile pictures, fine payments, BookMind integration
- **1.1.1** (2026-09-15): Enhanced recommendations system
- **1.1.0** (2026-09-10): Multi-tenant architecture
- **1.0.0** (2026-09-01): Initial release
