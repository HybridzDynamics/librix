<?php

// Configuration

require __DIR__ . "/../../../config/config.php";
require __DIR__ . "/../../../config/database.php";
require __DIR__ . "/../../../helpers/response.php";
require __DIR__ . "/../../../helpers/validation.php";
require __DIR__ . "/../../../helpers/functions.php";
require __DIR__ . "/../../../middleware/auth.php";
require __DIR__ . "/../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    methodNotAllowedResponse(["PUT"]);
}


// Authentication & Admin Check

$User = requireAuth();
requireAdmin($User);
$UserId = (int)$User["id"];


// Publisher ID

$PublisherId = $publisherId ?? ($parts[3] ?? null);
$Input = getJsonInput();

if ($PublisherId === null) {
    $PublisherId = $Input["id"] ?? null;
}

$IdError = validatePositiveInteger($PublisherId, "Publisher ID");
if ($IdError !== null) {
    errorResponse($IdError, 400);
}

$PublisherId = (int)$PublisherId;


// Find Existing Publisher

try {
    $Stmt = $pdo->prepare("SELECT id, name, address, website FROM publishers WHERE id = ? LIMIT 1");
    $Stmt->execute([$PublisherId]);
    $Existing = $Stmt->fetch();

    if (!$Existing) {
        notFoundResponse("Publisher not found");
    }
} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Update Fields

$Name = isset($Input["name"]) ? sanitizeString($Input["name"]) : $Existing["name"];
$Address = array_key_exists("address", $Input) ? ($Input["address"] ? sanitizeString($Input["address"]) : null) : $Existing["address"];
$Website = array_key_exists("website", $Input) ? ($Input["website"] ? sanitizeString($Input["website"]) : null) : $Existing["website"];

if (empty($Name)) {
    errorResponse("Publisher name cannot be empty", 422);
}

if (!empty($Website) && !filter_var($Website, FILTER_VALIDATE_URL)) {
    errorResponse("Invalid website URL", 422);
}


// Update Database

try {
    $UpdateStmt = $pdo->prepare("UPDATE publishers SET name = ?, address = ?, website = ? WHERE id = ?");
    $UpdateStmt->execute([$Name, $Address, $Website, $PublisherId]);

    logAudit($UserId, "update_publisher", "publishers", $PublisherId, "Updated publisher '{$Name}'");

    successResponse([
        "id" => $PublisherId,
        "name" => $Name,
        "address" => $Address,
        "website" => $Website
    ], "Publisher updated successfully");

} catch (PDOException $e) {
    errorResponse("Database error", 500);
}

?>
