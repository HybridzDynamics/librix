# LibriX API Documentation

This document describes the currently implemented REST-style routes under /api/v1/ for the LibriX backend.

## Base URL

Use the configured application URL plus the API route prefix:

```text
http://localhost:8000/index.php/api/v1
```

If your deployment uses a domain or proxy, replace the host while preserving the /api/v1 prefix.

## Authentication

Most protected routes require a bearer token issued after login. Authorization headers should be sent like this:

```http
Authorization: Bearer <token>
```

## Common response format

Success responses:

```json
{
  "success": true,
  "message": "Request completed successfully",
  "data": {}
}
```

Error responses:

```json
{
  "success": false,
  "error": "Something went wrong",
  "message": "Human-readable failure message"
}
```

## Routes

### Health

- GET /health
- Purpose: health check for backend and database availability
- Auth: none
- Response: success flag plus service state

### Auth

- POST /auth/register
- POST /auth/login
- POST /auth/logout
- GET /auth/session
- POST /auth/forgot-password
- POST /auth/reset-password
- POST /auth/verify-email
- POST /auth/resend-verification

Example login request:

```json
{
  "email": "admin@example.com",
  "password": "your-password"
}
```

Example response:

```json
{
  "success": true,
  "data": {
    "token": "generated-token",
    "user": {
      "id": 1,
      "name": "System Admin",
      "email": "admin@example.com",
      "role": "admin"
    }
  },
  "message": "Login successful"
}
```

### Books

- GET /books
- GET /books/search
- GET /books?id={id}
- POST /books
- PUT /books?id={id}
- DELETE /books?id={id}

Query parameters may include search, category, author, page, and limit.

### Authors

- GET /authors
- POST /authors
- PUT /authors?id={id}
- DELETE /authors?id={id}

### Categories

- GET /categories
- POST /categories
- PUT /categories?id={id}
- DELETE /categories?id={id}

### Publishers

- GET /publishers
- POST /publishers
- PUT /publishers?id={id}
- DELETE /publishers?id={id}

### Organizations

- GET /organizations
- POST /organizations
- PUT /organizations?id={id}
- DELETE /organizations?id={id}

### Library Operations

- POST /library/issue
- POST /library/return
- POST /library/renew
- POST /library/reserve
- POST /library/cancel-reservation
- GET /library/my-books
- GET /library/history

### Favorites

- GET /favorites
- POST /favorites/toggle

### Reviews

- GET /reviews
- POST /reviews/create
- DELETE /reviews?id={id}

### Notifications

- GET /notifications
- POST /notifications/read

### Fines

- GET /fines
- POST /fines/pay

### Recommendations and Readability

- GET /recommendations
- GET /readability
- POST /readability/analyze

### Admin

- GET /admin/statistics
- GET /admin/users
- GET /admin/books
- GET /admin/issues
- GET /admin/reservations
- GET /admin/fines
- GET /admin/audit-logs

## Error handling

The backend returns consistent status codes for common failures:

- 200 OK
- 201 Created
- 400 Bad Request
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 405 Method Not Allowed
- 422 Validation Failed
- 429 Too Many Requests
- 500 Internal Server Error

Do not rely on raw stack traces from production responses. Use the standardized error object returned by the API layer.

## Notes

- Some endpoints are role-protected and require authenticated admin or librarian access.
- The frontend uses the same API contract and should be kept in sync with backend field names.
- For production deployments, set the public API URL in environment variables rather than in static frontend code.
