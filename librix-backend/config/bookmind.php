<?php

// BookMind API Configuration

// BookMind runs on port 8001 by default (to avoid conflict with backend)
// Update this URL if BookMind is running on a different host/port
define("BOOKMIND_API_URL", getenv("BOOKMIND_API_URL") ?: "http://127.0.0.1:8001");

// BookMind endpoints
define("BOOKMEND_POPULAR_ENDPOINT", BOOKMIND_API_URL . "/recommendations/popular");
define("BOOKMEND_CONTENT_ENDPOINT", BOOKMIND_API_URL . "/recommendations/content");
define("BOOKMEND_COLLABORATIVE_ENDPOINT", BOOKMIND_API_URL . "/recommendations/collaborative");
define("BOOKMEND_HYBRID_ENDPOINT", BOOKMIND_API_URL . "/recommendations/hybrid");
define("BOOKMEND_HEALTH_ENDPOINT", BOOKMIND_API_URL . "/health");

// Fallback behavior when BookMind is unavailable
define("BOOKMIND_FALLBACK_ENABLED", true); // Use database recommendations if BookMind is down
define("BOOKMEND_TIMEOUT", 5); // Timeout in seconds for BookMind API calls

?>
