<?php

// Configuration

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";


// Request Method

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}


// Authentication

$User = requireAuth();
$UserId = (int)$User["id"];


// Request Data

$RequestData = getJsonInput();

$FineId = $RequestData["fine_id"] ?? null;
$PaymentMethod = trim($RequestData["payment_method"] ?? "cash");
$PaymentReference = trim($RequestData["payment_reference"] ?? "");
$Notes = trim($RequestData["notes"] ?? "");


// Validation

$Errors = [];

$IdError = validatePositiveInteger($FineId, "Fine ID");
if ($IdError !== null) {
    $Errors["fine_id"] = $IdError;
}

if (empty($PaymentMethod)) {
    $Errors["payment_method"] = "Payment method is required";
}

if (hasValidationErrors($Errors)) {
    validationErrorResponse($Errors);
}


// Check fine exists and belongs to user (unless admin)

try {
    $Stmt = $pdo->prepare("SELECT * FROM fines WHERE id = ? LIMIT 1");
    $Stmt->execute([(int)$FineId]);
    $Fine = $Stmt->fetch();
    
    if (!$Fine) {
        notFoundResponse("Fine not found");
    }
    
    if ($User["role"] !== "admin" && $Fine["user_id"] !== $UserId) {
        errorResponse("You can only pay your own fines", 403);
    }
    
    if ($Fine["status"] === "paid" || $Fine["status"] === "waived") {
        errorResponse("This fine has already been paid or waived", 400);
    }
    
} catch (PDOException $e) {
    errorResponse("Database error", 500);
}


// Process payment

try {
    $pdo->beginTransaction();
    
    // For demo payments, generate a reference if not provided
    if ($PaymentMethod === 'demo' && empty($PaymentReference)) {
        $PaymentReference = 'DEMO-' . strtoupper(uniqid());
    }
    
    // Create payment record
    $PaymentStmt = $pdo->prepare(
        "INSERT INTO fine_payments (user_id, issue_id, amount, payment_method, payment_reference, status, notes)
        VALUES (?, ?, ?, ?, ?, 'completed', ?)"
    );
    
    $PaymentStmt->execute([
        $Fine["user_id"],
        $Fine["issue_id"],
        $Fine["amount"],
        $PaymentMethod,
        $PaymentReference ?: null,
        $Notes ?: null
    ]);
    
    $PaymentId = (int)$pdo->lastInsertId();
    
    // Update fine status
    $UpdateStmt = $pdo->prepare(
        "UPDATE fines SET status = 'paid', paid_at = CURRENT_TIMESTAMP WHERE id = ?"
    );
    $UpdateStmt->execute([(int)$FineId]);
    
    logAudit($UserId, "fine_paid", "fines", $FineId, "Paid fine ID: $FineId with amount: {$Fine['amount']} via $PaymentMethod");
    
    $pdo->commit();
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    errorResponse("Failed to process payment", 500);
}


// Fetch updated fine

try {
    $Stmt = $pdo->prepare("SELECT * FROM fines WHERE id = ? LIMIT 1");
    $Stmt->execute([(int)$FineId]);
    $UpdatedFine = $Stmt->fetch();
    
    $UpdatedFine["id"] = (int)$UpdatedFine["id"];
    $UpdatedFine["user_id"] = (int)$UpdatedFine["user_id"];
    $UpdatedFine["issue_id"] = (int)$UpdatedFine["issue_id"];
    $UpdatedFine["amount"] = (float)$UpdatedFine["amount"];
    
} catch (PDOException $e) {
    errorResponse("Failed to retrieve updated fine", 500);
}


successResponse([
    "fine" => $UpdatedFine,
    "payment_id" => $PaymentId
], "Payment processed successfully");

?>