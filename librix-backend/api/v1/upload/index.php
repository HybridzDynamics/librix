<?php

require_once __DIR__ . "/../../../config/config.php";
require_once __DIR__ . "/../../../config/database.php";
require_once __DIR__ . "/../../../helpers/response.php";
require_once __DIR__ . "/../../../helpers/validation.php";
require_once __DIR__ . "/../../../helpers/functions.php";
require_once __DIR__ . "/../../../middleware/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    methodNotAllowedResponse(["POST"]);
}

$User = requireAuth();

if (!isset($_FILES["file"]) || $_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
    errorResponse("No file uploaded or upload error", 400);
}

$UploadType = $_POST["upload_type"] ?? "other";

$ValidTypes = ["profile_picture", "org_logo", "book_cover", "other"];
if (!in_array($UploadType, $ValidTypes)) {
    errorResponse("Invalid upload type", 400);
}

$File = $_FILES["file"];
$FileName = $File["name"];
$FileSize = $File["size"];
$FileType = $File["type"];
$TempPath = $File["tmp_name"];

if ($FileSize > 5 * 1024 * 1024) {
    errorResponse("File size exceeds 5MB limit", 400);
}

$AllowedMimeTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"];
if (!in_array($FileType, $AllowedMimeTypes)) {
    errorResponse("Invalid file type. Only JPG, PNG, GIF, and WebP are allowed", 400);
}

$Extension = pathinfo($FileName, PATHINFO_EXTENSION);
$UniqueFileName = uniqid() . "_" . time() . "." . $Extension;

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

if (!is_dir($UploadDir)) {
    mkdir($UploadDir, 0755, true);
}

$TargetPath = $UploadDir . $UniqueFileName;
if (!move_uploaded_file($TempPath, $TargetPath)) {
    errorResponse("Failed to move uploaded file", 500);
}

$RelativePath = "uploads/" . ($UploadType === "profile_picture" ? "profile-pictures/" :
                              ($UploadType === "org_logo" ? "org-logos/" :
                              ($UploadType === "book_cover" ? "book-covers/" : "other/"))) . $UniqueFileName;

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

    if ($UploadType === "profile_picture") {
        $UpdateStmt = $pdo->prepare("UPDATE users SET profile_picture_url = ? WHERE id = ?");
        $UpdateStmt->execute([$RelativePath, $UserId]);
    }

    if ($UploadType === "org_logo" && $OrgId) {
        $UpdateStmt = $pdo->prepare("UPDATE organizations SET logo_url = ? WHERE id = ?");
        $UpdateStmt->execute([$RelativePath, $OrgId]);
    }

    logAudit($UserId, "file_uploaded", "file_uploads", $UploadId, "Uploaded file: {$FileName} ({$UploadType})");

} catch (PDOException $e) {

    if (file_exists($TargetPath)) {
        unlink($TargetPath);
    }
    errorResponse("Failed to save file information", 500);
}

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
