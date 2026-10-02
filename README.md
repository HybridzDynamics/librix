# LibriX

LibriX is a PHP and MySQL library management system designed for small to mid-size library environments. It includes catalog management, borrowing workflows, reservations, fines, recommendations, and role-based access for administrators, librarians, and end users.

## Features

- Book catalog with metadata, ISBN support, categories, authors, publishers, tags, and cover images
- User authentication and role-based access for admin, librarian, and member users
- Borrowing, returning, reservation, and renewal workflows
- Fines and payment tracking for overdue items
- Favorites, reviews, notifications, and reading history
- Readability analysis and recommendation support
- Multi-organization support through organization and request workflows
- HTML/CSS/vanilla JavaScript frontend with PHP backend API

## Tech stack

- Backend: PHP 8+
- Database: MySQL 8 / MariaDB compatible
- Frontend: HTML, CSS, vanilla JavaScript
- API style: REST-style JSON responses under /api/v1/
- Security: password_hash(), prepared statements, session-based auth, environment-based configuration

## Project structure

```text
librix/
├── .env.example
├── .gitignore
├── LICENSE
├── README.md
├── librix-backend/
│   ├── api/
│   ├── config/
│   ├── docs/
│   ├── helpers/
│   ├── middleware/
│   ├── uploads/
│   ├── db.sql
│   ├── index.php
│   └── api_documentation.md
├── librix-frontend/
│   ├── assets/
│   ├── pages/
│   └── index.html
├── books.csv
├── books_clean.csv
├── book_tags.csv
├── tags.csv
├── import_schema.php
├── reset_database.php
├── update_schema.php
├── migrate_database.php
├── import_books_simple.php
├── import_books_comprehensive.php
├── import_tags.php
└── export_datasets.ps1
```

## Requirements

- PHP 8.1 or later
- MySQL 8.0 or compatible database server
- A web server such as Apache or Nginx, or PHP’s built-in server for local development
- Optional: Python if you want to run BookMind-related analysis utilities outside the default fallback workflow

## Database setup

1. Create a MySQL database and user.
2. Copy .env.example to .env and populate the values.
3. Run the schema import script:

```bash
php import_schema.php
```

The schema is stored in librix-backend/db.sql and is designed to be reproducible. The reset script drops and recreates the database for a clean rebuild when needed.

## Backend setup

1. Copy .env.example to .env.
2. Set your database credentials and application settings.
3. Start the backend:

```bash
cd librix-backend
php -S 127.0.0.1:8000
```

The backend entry point is librix-backend/index.php and the API base route is:

```text
http://localhost:8000/index.php/api/v1
```

## Frontend setup

Serve the frontend from the project root or any static host:

```bash
cd librix-frontend
python -m http.server 5500
```

Then open:

```text
http://localhost:5500
```

## Configuration

Use environment variables instead of hardcoded credentials. The project includes a safe template in .env.example. These values are read in the PHP config layer and support local development, staging, and deployment.

Example:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=librix
DB_USERNAME=librix_user
DB_PASSWORD=
APP_ENV=development
APP_URL=http://localhost:8000
API_URL=http://localhost:8000/index.php/api/v1
JWT_SECRET=replace_with_a_secure_random_string
```

## API overview

API routes are organized under /api/v1/ and are implemented in the backend route controller plus feature folders under librix-backend/api/v1/. The main categories include:

- /auth
- /books
- /authors
- /categories
- /publishers
- /organizations
- /library
- /favorites
- /reviews
- /notifications
- /fines
- /recommendations
- /readability
- /admin

See librix-backend/api_documentation.md for the current route reference.

## User roles

- Admin: full system access, auditing, user management, and configuration
- Librarian: catalog and circulation administration for an assigned organization
- Member/User: borrowing, reservations, favorites, reviews, profile, and notifications

## Importing dataset

The repository includes CSV datasets used for import and testing. Use the import scripts carefully and only after the database schema is in place.

```bash
php import_schema.php
php import_books_simple.php
php import_tags.php
```

The project avoids fake or placeholder records in normal runtime flows, and the import scripts are intended for reproducible seed data rather than production secrets.

## Running locally

Use two terminals:

Terminal 1:

```bash
cd librix
php -S 127.0.0.1:8000
```

Terminal 2:

```bash
cd librix-frontend
python -m http.server 5500
```

Then load the frontend in the browser and verify the backend API at /api/v1/health.

## Deployment notes

- Keep PHP and MySQL behind a real web server in production.
- Put .env in the deployment environment, not in Git.
- Reverse proxy the PHP application and serve the static frontend from a public directory or a web server asset folder.
- Configure CORS carefully.
- Do not expose database credentials or JWT secrets in client-side code or public repositories.

## Security notes

- Passwords are stored with password_hash() and verified with password_verify().
- API responses avoid exposing stack traces or internal server details.
- Authenticated routes must enforce role checks and file validation.
- Uploads should be restricted by extension and MIME validation in production.
- The repository is intended to be safe for GitHub by keeping secrets in .env and not in tracked source files.

## Testing

At minimum, verify:

- user registration and login
- session and auth checks
- book search and book detail pages
- borrowing and returning flows
- reservation and favorites flows
- admin and librarian authorization checks
- uploads and validation behavior
- health checks and error handling

## Contribution

Contributions are welcome. Please keep changes focused, preserve the existing architecture, and use environment-based configuration rather than embedding credentials.

## License

This project is distributed under the MIT License. See LICENSE for details.

---

This repository is intended as a clean, maintainable starting point for a library management application and should be reviewed and tuned to the target deployment environment before production use.

