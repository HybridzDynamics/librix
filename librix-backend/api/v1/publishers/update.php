<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";
require_once __DIR__ . "/../../../middleware/admin.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    MethodNotAllowedResponse(["PUT"]);
}


// Authentication & Admin Check

$User = RequireAuth();
RequireAdmin($User);
$UserId = (int)$User["id"];


// Publisher ID

$PublisherId = $publisherId ?? ($parts[3] ?? null);
$Input = GetJsonInput();

if ($PublisherId === null) {
    $PublisherId = $Input["id"] ?? null;
}

$IdError = ValidatePositiveInteger($PublisherId, "Publisher ID");
if ($IdError !== null) {
    ErrorResponse($IdError, 400);
}

$PublisherId = (int)$PublisherId;


// Find Existing Publisher

try {
    $Stmt = $pdo->prepare("SELECT id, name, address, website FROM publishers WHERE id = ? LIMIT 1");
    $Stmt->execute([$PublisherId]);
    $Existing = $Stmt->fetch();

    if (!$Existing) {
        NotFoundResponse("Publisher not found");
    }
} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}


// Update Fields

$Name = isset($Input["name"]) ? SanitizeString($Input["name"]) : $Existing["name"];
$Address = array_key_exists("address", $Input) ? ($Input["address"] ? SanitizeString($Input["address"]) : null) : $Existing["address"];
$Website = array_key_exists("website", $Input) ? ($Input["website"] ? SanitizeString($Input["website"]) : null) : $Existing["website"];

if (empty($Name)) {
    ErrorResponse("Publisher name cannot be empty", 422);
}

if (!empty($Website) && !filter_var($Website, FILTER_VALIDATE_URL)) {
    ErrorResponse("Invalid website URL", 422);
}


// Update Database

try {
    $UpdateStmt = $pdo->prepare("UPDATE publishers SET name = ?, address = ?, website = ? WHERE id = ?");
    $UpdateStmt->execute([$Name, $Address, $Website, $PublisherId]);

    LogAudit($UserId, "update_publisher", "publishers", $PublisherId, "Updated publisher '{$Name}'");

    SuccessResponse([
        "id" => $PublisherId,
        "name" => $Name,
        "address" => $Address,
        "website" => $Website
    ], "Publisher updated successfully");

} catch (PDOException $e) {
    ErrorResponse("Database error", 500);
}

?>
