<?php

// Profile Picture Upload Endpoint
// POST /api/v1/auth/upload_profile_picture
// Accepts: multipart/form-data with field "profile_picture"

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";

$User = requireAuth();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}

// Check file was submitted
if (!isset($_FILES["profile_picture"]) || $_FILES["profile_picture"]["error"] !== UPLOAD_ERR_OK) {
    $uploadErr = $_FILES["profile_picture"]["error"] ?? 99;
    $errMessages = [
        UPLOAD_ERR_INI_SIZE   => "File exceeds server upload limit",
        UPLOAD_ERR_FORM_SIZE  => "File exceeds form size limit",
        UPLOAD_ERR_PARTIAL    => "File was only partially uploaded",
        UPLOAD_ERR_NO_FILE    => "No file was uploaded",
        UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
        UPLOAD_ERR_EXTENSION  => "Upload blocked by server extension",
    ];
    badRequestResponse($errMessages[$uploadErr] ?? "Upload error code: $uploadErr");
}

$File     = $_FILES["profile_picture"];
$TmpPath  = $File["tmp_name"];
$FileSize = $File["size"];

// Validate size: max 2 MB
if ($FileSize > 2 * 1024 * 1024) {
    badRequestResponse("Profile picture must be under 2 MB");
}

// Validate MIME type using finfo (not trusting Content-Type header)
$Finfo    = new finfo(FILEINFO_MIME_TYPE);
$MimeType = $Finfo->file($TmpPath);
$AllowedMimes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"];

if (!in_array($MimeType, $AllowedMimes)) {
    badRequestResponse("Only JPEG, PNG, GIF, or WebP images are allowed (detected: $MimeType)");
}

// Determine extension
$ExtMap = [
    "image/jpeg" => "jpg",
    "image/jpg"  => "jpg",
    "image/png"  => "png",
    "image/gif"  => "gif",
    "image/webp" => "webp",
];
$Ext = $ExtMap[$MimeType] ?? "jpg";

// Build safe unique filename
$UserId   = (int)$User["id"];
$Filename = "user_{$UserId}_" . time() . "_{$Ext}." . $Ext;

// Ensure upload directory exists
$UploadDir = defined("PROFILE_PIC_UPLOAD_PATH")
    ? PROFILE_PIC_UPLOAD_PATH
    : dirname(__DIR__, 3) . "/uploads/profile_pics/";

if (!is_dir($UploadDir)) {
    @mkdir($UploadDir, 0755, true);
}

$Destination = $UploadDir . $Filename;

if (!move_uploaded_file($TmpPath, $Destination)) {
    errorResponse("Failed to save the uploaded file", 500);
}

// Remove old profile picture if it exists
try {
    $OldStmt = $pdo->prepare("SELECT profile_picture_path FROM users WHERE id = ? LIMIT 1");
    $OldStmt->execute([$UserId]);
    $OldRow = $OldStmt->fetch();

    if ($OldRow && !empty($OldRow["profile_picture_path"])) {
        $OldFile = $UploadDir . basename($OldRow["profile_picture_path"]);
        if (file_exists($OldFile) && $OldFile !== $Destination) {
            @unlink($OldFile);
        }
    }
} catch (PDOException $e) {
    // Non-fatal: proceed even if we can't delete the old file
}

// Build the public URL for the frontend
$UrlPath = (defined("PROFILE_PIC_URL_PATH") ? PROFILE_PIC_URL_PATH : "/uploads/profile_pics/") . $Filename;

// Update users table
try {
    $Stmt = $pdo->prepare("UPDATE users SET profile_picture_path = ?, updated_at = NOW() WHERE id = ?");
    $Stmt->execute([$UrlPath, $UserId]);
} catch (PDOException $e) {
    // If column doesn't exist yet, add it first then retry
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture_path VARCHAR(500) NULL");
        $Stmt = $pdo->prepare("UPDATE users SET profile_picture_path = ?, updated_at = NOW() WHERE id = ?");
        $Stmt->execute([$UrlPath, $UserId]);
    } catch (PDOException $e2) {
        errorResponse("Database error updating profile picture", 500);
    }
}

logInfo("Profile picture uploaded", [
    "user_id"  => $UserId,
    "filename" => $Filename,
    "size"     => $FileSize,
    "mime"     => $MimeType
]);

successResponse([
    "message"             => "Profile picture updated successfully",
    "profile_picture_url" => $UrlPath,
    "filename"            => $Filename
]);
?>
