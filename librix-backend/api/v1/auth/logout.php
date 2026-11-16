<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}

$AccessToken = getAuthorizationToken();

if (!$AccessToken) {
    unauthorizedResponse("Authorization token is required");
}

try {
    $Stmt = $pdo->prepare(
        "DELETE FROM auth_tokens
         WHERE token = ?"
    );

    $Stmt->execute([$AccessToken]);

} catch (PDOException $e) {
    errorResponse("Unable to logout", 500);
}

successResponse(
    [],
    "Logout successful"
);

?>
