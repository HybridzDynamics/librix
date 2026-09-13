<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/functions.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    methodNotAllowedResponse(["GET"]);
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
    errorResponse("Unable to retrieve maintenance schedule", 500);
}


// Response

successResponse($Windows);

?>
