<?php

define("BOOKMIND_API_URL", getenv("BOOKMIND_API_URL") ?: "http://127.0.0.1:8001");

define("BOOKMEND_POPULAR_ENDPOINT", BOOKMIND_API_URL . "/recommendations/popular");
define("BOOKMEND_CONTENT_ENDPOINT", BOOKMIND_API_URL . "/recommendations/content");
define("BOOKMEND_COLLABORATIVE_ENDPOINT", BOOKMIND_API_URL . "/recommendations/collaborative");
define("BOOKMEND_HYBRID_ENDPOINT", BOOKMIND_API_URL . "/recommendations/hybrid");
define("BOOKMEND_HEALTH_ENDPOINT", BOOKMIND_API_URL . "/health");

define("BOOKMIND_FALLBACK_ENABLED", true);
define("BOOKMEND_TIMEOUT", 5);

?>
