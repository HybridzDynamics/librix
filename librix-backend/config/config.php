<?php

// LibriX Application Configuration

define("APP_NAME", "LibriX");
define("APP_VERSION", "1.1.2");
define("API_VERSION", "v1");
define("APP_ENV", getenv("APP_ENV") ?: "development");


// API

define("API_BASE_URL", "/api/" . API_VERSION);
define("API_FULL_VERSION", API_VERSION);
define("BASE_URL", "http://localhost:8000");


// Server

define("SERVER_NAME", "LibriX Backend");
define("SERVER_STATUS", "online");


// Security

define("JWT_SECRET", getenv("JWT_SECRET") ?: "CHANGE_THIS_TO_A_LONG_RANDOM_SECRET");
define("SESSION_NAME", "LIBRIX_SESSION");


// CORS

define("CORS_ORIGIN", getenv("CORS_ORIGIN") ?: (APP_ENV === "production" ? "http://127.0.0.1:5500/" : "*"));

// Profile picture upload paths
// Filesystem path for storing uploaded profile pictures (ensure this directory exists and is writable)
define("PROFILE_PIC_UPLOAD_PATH", dirname(__DIR__) . "/uploads/profile_pics/");
// URL path used by frontend to display profile pictures
define("PROFILE_PIC_URL_PATH", "/uploads/profile_pics/");


// Response

define("JSON_FLAGS", JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);


// Timezone

date_default_timezone_set("Asia/Kolkata");


// Library Business Rules

define("FINE_RATE_PER_DAY", (float)(getenv("FINE_RATE_PER_DAY") ?: 5.00));
define("DEFAULT_LOAN_DAYS", (int)(getenv("DEFAULT_LOAN_DAYS") ?: 14));
define("DEFAULT_PAGE_SIZE", 20);
define("MAX_PAGE_SIZE", 100);
define("EMAIL_VERIFICATION_ENABLED", filter_var(getenv("EMAIL_VERIFICATION_ENABLED") ?: "false", FILTER_VALIDATE_BOOLEAN));


// Rate Limiting

define("RATE_LIMIT_LOGIN_MAX", 5);
define("RATE_LIMIT_AUTH_MAX", 10);
define("RATE_LIMIT_WINDOW_SECONDS", 900); // 15 minutes


// Debug

if (APP_ENV === "development") {
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
} else {
    error_reporting(0);
    ini_set("display_errors", 0);
}

?>
