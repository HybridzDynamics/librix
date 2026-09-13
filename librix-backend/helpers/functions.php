<?php

// Get JSON Input

function getJsonInput()
{
    $Input = file_get_contents("php://input");

    if (empty($Input)) {
        return [];
    }

    $Data = json_decode($Input, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($Data)) {
        return [];
    }

    return $Data;
}


// Get Request Header

function getRequestHeader($Name)
{
    if (function_exists("getallheaders")) {
        $Headers = getallheaders();

        foreach ($Headers as $Key => $Value) {
            if (strtolower($Key) === strtolower($Name)) {
                return $Value;
            }
        }
    }

    $Normalized = "HTTP_" . strtoupper(str_replace("-", "_", $Name));

    if (isset($_SERVER[$Normalized])) {
        return $_SERVER[$Normalized];
    }

    if (strtolower($Name) === "authorization") {
        if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
            return $_SERVER["HTTP_AUTHORIZATION"];
        }

        if (isset($_SERVER["REDIRECT_HTTP_AUTHORIZATION"])) {
            return $_SERVER["REDIRECT_HTTP_AUTHORIZATION"];
        }
    }

    return null;
}


// Get Authorization Token

function getAuthorizationToken()
{
    $Header = getRequestHeader("Authorization");

    if (!$Header) {
        return null;
    }

    if (preg_match("/Bearer\s+(.+)/i", $Header, $Matches)) {
        return trim($Matches[1]);
    }

    return null;
}


// Generate Random Token

function generateToken($Length = 32)
{
    return bin2hex(random_bytes($Length));
}


// Sanitize String

function sanitizeString($Value)
{
    return htmlspecialchars(
        trim((string)$Value),
        ENT_QUOTES,
        "UTF-8"
    );
}


// Get Current Time

function currentTime()
{
    return date("Y-m-d H:i:s");
}


// Get Client IP

function getClientIp()
{
    return $_SERVER["REMOTE_ADDR"] ?? "unknown";
}


// Audit Logging

function logAudit($UserId, $Action, $EntityType, $EntityId, $Description)
{
    global $pdo;

    if (!$pdo) {
        return false;
    }

    $IpAddress = getClientIp();

    try {
        $Stmt = $pdo->prepare(
            "INSERT INTO audit_logs
            (user_id, action, entity_type, entity_id, description, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $Stmt->execute([
            $UserId !== null ? (int)$UserId : null,
            $Action,
            $EntityType,
            $EntityId !== null ? (int)$EntityId : null,
            $Description,
            $IpAddress
        ]);

        return true;

    } catch (PDOException $e) {
        return false;
    }
}


// Rate Limiting Check
// Returns 0 if allowed, or positive integer seconds to wait if exceeded

function checkRateLimit($Key, $MaxRequests = 10, $WindowSeconds = 900)
{
    global $pdo;

    if (!$pdo) {
        return 0;
    }

    $Now = time();

    try {
        $Stmt = $pdo->prepare(
            "SELECT id, requests, reset_at
             FROM rate_limits
             WHERE rate_key = ?
             LIMIT 1"
        );

        $Stmt->execute([$Key]);

        $Record = $Stmt->fetch();

        if (!$Record) {
            $Stmt = $pdo->prepare(
                "INSERT INTO rate_limits
                (rate_key, requests, reset_at)
                VALUES (?, 1, ?)"
            );

            $Stmt->execute([
                $Key,
                $Now + $WindowSeconds
            ]);

            return 0;
        }

        if ($Now > (int)$Record["reset_at"]) {
            $Stmt = $pdo->prepare(
                "UPDATE rate_limits
                 SET requests = 1,
                     reset_at = ?
                 WHERE id = ?"
            );

            $Stmt->execute([
                $Now + $WindowSeconds,
                $Record["id"]
            ]);

            return 0;
        }

        if ((int)$Record["requests"] >= $MaxRequests) {
            return max(1, (int)$Record["reset_at"] - $Now);
        }

        $Stmt = $pdo->prepare(
            "UPDATE rate_limits
             SET requests = requests + 1
             WHERE id = ?"
        );

        $Stmt->execute([$Record["id"]]);

        return 0;

    } catch (PDOException $e) {
        return 0;
    }
}

?>