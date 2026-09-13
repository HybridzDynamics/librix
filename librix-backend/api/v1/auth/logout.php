<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    MethodNotAllowedResponse(["POST"]);
}


// Authorization Token

$AccessToken = GetAuthorizationToken();


// Check Token

if (!$AccessToken) {
    UnauthorizedResponse("Authorization token is required");
}


// Delete Token

try {
    $Stmt = $pdo->prepare(
        "DELETE FROM auth_tokens
         WHERE token = ?"
    );

    $Stmt->execute([$AccessToken]);

} catch (PDOException $e) {
    ErrorResponse("Unable to logout", 500);
}


// Logout Response

SuccessResponse(
    [],
    "Logout successful"
);

?>