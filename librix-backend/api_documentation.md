# LibriX Backend API Documentation (v1)

- **API Base URL**: `/api/v1`
- **Current Version**: `1.0.0`
- **Authentication**: Bearer Token via `Authorization: Bearer <TOKEN>` header

---

## 1. System & Public Endpoints

### 1.1 Root Info
- **Endpoint**: `GET /` or `GET /api/v1`
- **Authentication**: None
- **Response (200)**:
```json
{
  "success": true,
  "data": {
    "name": "LibriX",
    "version": "1.0.0",
    "api": "v1",
    "status": "online"
  }
}
```

### 1.2 Health Check
- **Endpoint**: `GET /api/v1/health`
- **Authentication**: None
- **Response (200)**:
```json
{
  "success": true,
  "data": {
    "api": "online",
    "database": "connected"
  },
  "message": "Health check passed"
}
```

---

## 2. Public Status Page API

### 2.1 Overall System Status
- **Endpoint**: `GET /api/v1/status`
- **Authentication**: None
- **Response (200)**:
```json
{
  "success": true,
  "data": {
    "status": "operational",
    "name": "LibriX",
    "version": "1.0.0",
    "updated_at": "2026-09-10 20:00:00"
  }
}
```
*Possible statuses: `operational`, `degraded`, `partial_outage`, `major_outage`, `maintenance`.*

### 2.2 Component Services Status
- **Endpoint**: `GET /api/v1/status/services`
- **Authentication**: None
- **Response (200)**:
```json
{
  "success": true,
  "data": [
    { "name": "API", "status": "operational", "response_time_ms": 1 },
    { "name": "Database", "status": "operational", "response_time_ms": 3 },
    { "name": "Authentication", "status": "operational", "response_time_ms": 2 },
    { "name": "Books", "status": "operational", "response_time_ms": 2 },
    { "name": "Authors", "status": "operational", "response_time_ms": 2 },
    { "name": "Library", "status": "operational", "response_time_ms": 2 },
    { "name": "Admin", "status": "operational", "response_time_ms": 2 },
    { "name": "Search", "status": "operational", "response_time_ms": 3 }
  ]
}
```

### 2.3 System Incidents
- **Endpoint**: `GET /api/v1/status/incidents`
- **Query Parameters**: `status` (`investigating`, `identified`, `monitoring`, `resolved`)
- **Authentication**: None

### 2.4 Service Uptime
- **Endpoint**: `GET /api/v1/status/uptime`
- **Authentication**: None
- **Response (200)**:
```json
{
  "success": true,
  "data": {
    "uptime_percentage": 100.0,
    "total_checks": 120,
    "avg_response_time_ms": 2.1,
    "services": [
      {
        "service": "Database",
        "uptime_percentage": 100.0,
        "total_checks": 15,
        "avg_response_time_ms": 2.5
      }
    ]
  }
}
```

### 2.5 Status History
- **Endpoint**: `GET /api/v1/status/history`
- **Query Parameters**: `page` (int), `limit` (int), `service` (string)

### 2.6 Scheduled Maintenance
- **Endpoint**: `GET /api/v1/status/maintenance`
- **Authentication**: None

---

## 3. Authentication (`/api/v1/auth`)

### 3.1 Register
- **Endpoint**: `POST /api/v1/auth/register`
- **Body**:
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "StrongPassword123"
}
```
- **Response (201)**:
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "role": "user",
      "status": "active"
    }
  },
  "message": "Registration successful"
}
```

### 3.2 Login
- **Endpoint**: `POST /api/v1/auth/login`
- **Body**:
```json
{
  "email": "jane@example.com",
  "password": "StrongPassword123"
}
```
- **Response (200)**:
```json
{
  "success": true,
  "data": {
    "token": "a1b2c3d4e5...",
    "expires_at": "2026-09-17 20:00:00",
    "user": {
      "id": 1,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "role": "user",
      "status": "active"
    }
  },
  "message": "Login successful"
}
```

### 3.3 Session Check
- **Endpoint**: `GET /api/v1/auth/session`
- **Header**: `Authorization: Bearer <TOKEN>`

### 3.4 Logout
- **Endpoint**: `POST /api/v1/auth/logout`
- **Header**: `Authorization: Bearer <TOKEN>`

### 3.5 Forgot Password
- **Endpoint**: `POST /api/v1/auth/forgot-password`
- **Body**: `{"email": "jane@example.com"}`
- **Response (200)**:
```json
{
  "success": true,
  "message": "If an account exists for this email, a password reset request has been created."
}
```

### 3.6 Reset Password
- **Endpoint**: `POST /api/v1/auth/reset-password`
- **Body**: `{"token": "<RESET_TOKEN>", "password": "<NEW_PASSWORD>"}`

### 3.7 Verify Email
- **Endpoint**: `POST /api/v1/auth/verify-email`
- **Body**: `{"token": "<VERIFY_TOKEN>"}`

### 3.8 Resend Email Verification
- **Endpoint**: `POST /api/v1/auth/resend-verification`
- **Body**: `{"email": "jane@example.com"}`

---

## 4. Books (`/api/v1/books`)

### 4.1 Get Books (with Search, Filtering, Sorting & Pagination)
- **Endpoint**: `GET /api/v1/books`
- **Query Parameters**:
  - `search`: search term across title, isbn, and author name
  - `category`: category name filter
  - `author_id`: integer author ID filter
  - `available`: `true` or `1` for available copies only
  - `sort`: `title`, `created_at`, `publication_year`, `id`
  - `order`: `asc` or `desc` (default `desc`)
  - `page`: page number (default 1)
  - `limit`: items per page (default 20, max 100)
- **Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Clean Architecture",
      "author_id": 1,
      "author_name": "Robert C. Martin",
      "isbn": "9780134494166",
      "category": "Software",
      "total_copies": 5,
      "available_copies": 4
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 1,
    "total_pages": 1
  }
}
```

### 4.2 Get Single Book
- **Endpoint**: `GET /api/v1/books/{id}`

### 4.3 Create Book
- **Endpoint**: `POST /api/v1/books`
- **Header**: `Authorization: Bearer <ADMIN_TOKEN>`
- **Body**:
```json
{
  "title": "Clean Architecture",
  "author_id": 1,
  "isbn": "9780134494166",
  "category": "Software",
  "publisher": "Prentice Hall",
  "publication_year": 2017,
  "total_copies": 5
}
```

### 4.4 Update Book
- **Endpoint**: `PUT /api/v1/books/{id}`
- **Header**: `Authorization: Bearer <ADMIN_TOKEN>`

### 4.5 Delete Book
- **Endpoint**: `DELETE /api/v1/books/{id}`
- **Header**: `Authorization: Bearer <ADMIN_TOKEN>`

---

## 5. Authors (`/api/v1/authors`)

### 5.1 Get Authors
- **Endpoint**: `GET /api/v1/authors`

### 5.2 Get Author
- **Endpoint**: `GET /api/v1/authors/{id}`

### 5.3 Create Author
- **Endpoint**: `POST /api/v1/authors` (Admin)
- **Body**: `{"name": "Robert C. Martin", "biography": "..."}`

### 5.4 Update Author
- **Endpoint**: `PUT /api/v1/authors/{id}` (Admin)

### 5.5 Delete Author
- **Endpoint**: `DELETE /api/v1/authors/{id}` (Admin)

---

## 6. Library Operations (`/api/v1/library`)

### 6.1 Issue Book
- **Endpoint**: `POST /api/v1/library/issue`
- **Header**: `Authorization: Bearer <TOKEN>`
- **Body**: `{"book_id": 1, "due_date": "2026-09-24"}`

### 6.2 Return Book
- **Endpoint**: `POST /api/v1/library/return`
- **Header**: `Authorization: Bearer <TOKEN>`
- **Body**: `{"issue_id": 1}` or `{"book_id": 1}`
- Automatically calculates and stores overdue fines if returned past `due_date`.

### 6.3 Reserve Book
- **Endpoint**: `POST /api/v1/library/reserve`
- **Header**: `Authorization: Bearer <TOKEN>`
- **Body**: `{"book_id": 1}`

### 6.4 Cancel Reservation
- **Endpoint**: `POST /api/v1/library/cancel-reservation`
- **Header**: `Authorization: Bearer <TOKEN>`
- **Body**: `{"reservation_id": 1}`

### 6.5 My Library Activity
- **Endpoint**: `GET /api/v1/library/my-books`
- **Header**: `Authorization: Bearer <TOKEN>`
- Returns: `currently_issued`, `history`, `reservations`, and `fines`.

---

## 7. User Profile (`/api/v1/users`)

### 7.1 Get Profile
- **Endpoint**: `GET /api/v1/users/profile`
- **Header**: `Authorization: Bearer <TOKEN>`

### 7.2 Update Profile
- **Endpoint**: `PUT /api/v1/users/update`
- **Header**: `Authorization: Bearer <TOKEN>`
- **Body**: `{"name": "...", "email": "...", "password": "..."}`

---

## 8. Admin Management (`/api/v1/admin`)

*All admin endpoints require `Authorization: Bearer <ADMIN_TOKEN>`.*

### 8.1 Users Management
- `GET /api/v1/admin/users`: paginated user list with `status`, `role`, `search` filters.
- `PUT /api/v1/admin/users/{id}`: update user status (`active`, `inactive`, `suspended`) or `role`.

### 8.2 Inventory & Statistics
- `GET /api/v1/admin/books`: inventory report with active issue and reservation counts.
- `GET /api/v1/admin/statistics`: real-time SQL aggregates across all system entities.

### 8.3 Issues, Reservations & Fines
- `GET /api/v1/admin/issues`: paginated issues with overdue flag.
- `GET /api/v1/admin/reservations`: paginated reservations.
- `GET /api/v1/admin/fines`: paginated fines.
- `PUT /api/v1/admin/fines/{id}`: update fine status (`paid`, `waived`).

### 8.4 Status & Incident Administration
- `POST /api/v1/admin/status/incidents`: create incident.
- `PUT /api/v1/admin/status/incidents/{id}`: update/resolve incident.
- `DELETE /api/v1/admin/status/incidents/{id}`: remove incident.
- `POST /api/v1/admin/status/maintenance`: schedule maintenance.
- `PUT /api/v1/admin/status/maintenance/{id}`: update maintenance window.
- `DELETE /api/v1/admin/status/maintenance/{id}`: cancel/remove maintenance.

---

## 9. Reviews & Ratings API (`/api/v1/reviews`)

### 9.1 Get Book Reviews
- **Endpoint**: `GET /api/v1/reviews?book_id={id}`
- **Authentication**: None
- **Response**: List of patron reviews, average rating score (e.g. 4.8), and pagination.

### 9.2 Create / Update Review
- **Endpoint**: `POST /api/v1/reviews`
- **Header**: `Authorization: Bearer <TOKEN>`
- **Body**: `{"book_id": 1, "rating": 5, "review_text": "Remarkable read."}`

### 9.3 Delete Review
- **Endpoint**: `DELETE /api/v1/reviews/{id}`
- **Header**: `Authorization: Bearer <TOKEN>`

---

## 10. Favorites Wishlist API (`/api/v1/favorites`)

### 10.1 Get Favorites
- **Endpoint**: `GET /api/v1/favorites`
- **Header**: `Authorization: Bearer <TOKEN>`
- **Response**: Paginated list of user's saved book titles with current stock availability.

### 10.2 Toggle Favorite
- **Endpoint**: `POST /api/v1/favorites`
- **Header**: `Authorization: Bearer <TOKEN>`
- **Body**: `{"book_id": 1}`
- **Response**: `{"favorited": true|false, "book_id": 1}`

---

## 11. Notifications API (`/api/v1/notifications`)

### 11.1 Get Notifications
- **Endpoint**: `GET /api/v1/notifications`
- **Header**: `Authorization: Bearer <TOKEN>`
- **Response**: List of user notifications with `unread_count`.

### 11.2 Mark Read
- **Endpoint**: `PUT /api/v1/notifications`
- **Header**: `Authorization: Bearer <TOKEN>`
- **Body**: `{"id": 5}` or `{"all": true}`

---

## 12. Readability Analysis API (`/api/v1/readability`)

### 12.1 Get Book Readability
- **Endpoint**: `GET /api/v1/readability?book_id={id}`
- **Authentication**: None
- **Response**: `flesch_reading_ease`, `flesch_kincaid_grade`, `difficulty_level`, `estimated_reading_minutes`, `word_count`.

### 12.2 Analyze Text
- **Endpoint**: `POST /api/v1/readability`
- **Header**: `Authorization: Bearer <TOKEN>`
- **Body**: `{"book_id": 1, "text": "Sample excerpt..."}`

---

## 13. Categories & Publishers APIs

- `GET /api/v1/categories`: Public list of categories with total book counts.
- `POST / PUT / DELETE /api/v1/categories`: Admin taxonomy management.
- `GET /api/v1/publishers`: Public list of publishing houses.
- `POST / PUT / DELETE /api/v1/publishers`: Admin publisher CRUD.

---

## 14. Library Renewals & History

- `POST /api/v1/library/renew`: Extends book due date by 14 days (up to 2 renewals).
- `GET /api/v1/library/history`: Dedicated borrowing history with fine status.

