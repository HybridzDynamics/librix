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


// Check if file was uploaded

if (!isset($_FILES["file"]) || $_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
    errorResponse("No file uploaded or upload error", 400);
}


$UploadType = $_POST["upload_type"] ?? "other";


// Validate upload type

$ValidTypes = ["profile_picture", "org_logo", "book_cover", "other"];
if (!in_array($UploadType, $ValidTypes)) {
    errorResponse("Invalid upload type", 400);
}


// File validation

$File = $_FILES["file"];
$FileName = $File["name"];
$FileSize = $File["size"];
$FileType = $File["type"];
$TempPath = $File["tmp_name"];


// Check file size (max 5MB)

if ($FileSize > 5 * 1024 * 1024) {
    errorResponse("File size exceeds 5MB limit", 400);
}


// Check file type

$AllowedMimeTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"];
if (!in_array($FileType, $AllowedMimeTypes)) {
    errorResponse("Invalid file type. Only JPG, PNG, GIF, and WebP are allowed", 400);
}


// Generate unique filename

$Extension = pathinfo($FileName, PATHINFO_EXTENSION);
$UniqueFileName = uniqid() . "_" . time() . "." . $Extension;


// Determine upload directory based on type

switch ($UploadType) {
    case "profile_picture":
        $UploadDir = __DIR__ . "/../../../uploads/profile-pictures/";
        break;
    case "org_logo":
        $UploadDir = __DIR__ . "/../../../uploads/org-logos/";
        break;
    case "book_cover":
        $UploadDir = __DIR__ . "/../../../uploads/book-covers/";
        break;
    default:
        $UploadDir = __DIR__ . "/../../../uploads/other/";
}


// Create directory if it doesn't exist

if (!is_dir($UploadDir)) {
    mkdir($UploadDir, 0755, true);
}


// Move uploaded file

$TargetPath = $UploadDir . $UniqueFileName;
if (!move_uploaded_file($TempPath, $TargetPath)) {
    errorResponse("Failed to move uploaded file", 500);
}


// Get relative path for database

$RelativePath = "uploads/" . ($UploadType === "profile_picture" ? "profile-pictures/" : 
                              ($UploadType === "org_logo" ? "org-logos/" : 
                              ($UploadType === "book_cover" ? "book-covers/" : "other/"))) . $UniqueFileName;


// Save to database

try {
    $Stmt = $pdo->prepare(
        "INSERT INTO file_uploads (user_id, org_id, file_name, original_name, file_path, file_size, mime_type, upload_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    $UserId = $User["id"];
    $OrgId = $User["org_id"] ?? null;
    
    $Stmt->execute([
        $UserId,
        $OrgId,
        $UniqueFileName,
        $FileName,
        $RelativePath,
        $FileSize,
        $FileType,
        $UploadType
    ]);
    
    $UploadId = (int)$pdo->lastInsertId();
    
    // Update user profile picture if applicable
    if ($UploadType === "profile_picture") {
        $UpdateStmt = $pdo->prepare("UPDATE users SET profile_picture_url = ? WHERE id = ?");
        $UpdateStmt->execute([$RelativePath, $UserId]);
    }
    
    // Update organization logo if applicable
    if ($UploadType === "org_logo" && $OrgId) {
        $UpdateStmt = $pdo->prepare("UPDATE organizations SET logo_url = ? WHERE id = ?");
        $UpdateStmt->execute([$RelativePath, $OrgId]);
    }
    
    logAudit($UserId, "file_uploaded", "file_uploads", $UploadId, "Uploaded file: {$FileName} ({$UploadType})");
    
} catch (PDOException $e) {
    // Delete the uploaded file if database insert fails
    if (file_exists($TargetPath)) {
        unlink($TargetPath);
    }
    errorResponse("Failed to save file information", 500);
}


// Return success response

successResponse([
    "upload_id" => $UploadId,
    "file_path" => $RelativePath,
    "file_url" => BASE_URL . "/" . $RelativePath,
    "original_name" => $FileName,
    "file_size" => $FileSize,
    "mime_type" => $FileType,
    "upload_type" => $UploadType
], "File uploaded successfully", 201);

?>