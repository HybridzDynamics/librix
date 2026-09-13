<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}


// Authorization Token

$AccessToken = getAuthorizationToken();


// Check Token

if (!$AccessToken) {
    unauthorizedResponse("Authorization token is required");
}


// Delete Token

try {
    $Stmt = $pdo->prepare(
        "DELETE FROM auth_tokens
         WHERE token = ?"
    );

    $Stmt->execute([$AccessToken]);

} catch (PDOException $e) {
    errorResponse("Unable to logout", 500);
}


// Logout Response

successResponse(
    [],
    "Logout successful"
);

?>