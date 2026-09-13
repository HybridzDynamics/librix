<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    MethodNotAllowedResponse(["GET"]);
}


// Query Maintenance Windows

try {
    $Stmt = $pdo->prepare(
        "SELECT
            id,
            title,
            description,
            service,
            starts_at,
            ends_at,
            status,
            created_at,
            updated_at
         FROM maintenance_windows
         WHERE status IN ('scheduled', 'active')
         ORDER BY starts_at ASC"
    );

    $Stmt->execute();

    $Windows = $Stmt->fetchAll();

    foreach ($Windows as &$Item) {
        $Item["id"] = (int)$Item["id"];
    }

} catch (PDOException $e) {
    ErrorResponse("Unable to retrieve maintenance schedule", 500);
}


// Response

SuccessResponse($Windows);

?>
