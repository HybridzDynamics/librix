<?php

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../helpers/validation.php";
require_once __DIR__ . "/../helpers/functions.php";
require_once __DIR__ . "/../middleware/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}

$Body = json_decode(file_get_contents("php://input"), true);

if (!isset($Body["name"]) || !isset($Body["email"]) || !isset($Body["subject"]) || !isset($Body["message"])) {
    errorResponse("name, email, subject, and message are required", 400);
}

$Name = trim($Body["name"]);
$Email = trim($Body["email"]);
$Subject = trim($Body["subject"]);
$Message = trim($Body["message"]);

if (empty($Name) || empty($Email) || empty($Subject) || empty($Message)) {
    errorResponse("All fields are required", 400);
}

if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
    errorResponse("Invalid email address", 400);
}

$ValidSubjects = ["borrowing", "recommendation", "account", "fines", "technical", "other"];
if (!in_array($Subject, $ValidSubjects)) {
    errorResponse("Invalid subject", 400);
}

try {
    $Stmt = $pdo->prepare(
        "INSERT INTO contact_inquiries (name, email, subject, message, created_at)
         VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)"
    );
    $Stmt->execute([$Name, $Email, $Subject, $Message]);

    successResponse(null, "Your inquiry has been received");

} catch (PDOException $e) {
    errorResponse("Failed to submit inquiry", 500);
}

?>
