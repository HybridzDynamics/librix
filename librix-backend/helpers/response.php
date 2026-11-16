<?php

function successResponse($Data = [], $Message = null, $StatusCode = 200)
{
    http_response_code($StatusCode);

    $Response = [
        "success" => true,
        "data" => $Data
    ];

    if ($Message !== null) {
        $Response["message"] = $Message;
    }

    echo json_encode(
        $Response,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function paginatedResponse($Data, $Page, $Limit, $Total, $Message = null, $StatusCode = 200)
{
    http_response_code($StatusCode);

    $TotalPages = $Limit > 0 ? (int)ceil($Total / $Limit) : 1;

    $Response = [
        "success" => true,
        "data" => $Data,
        "pagination" => [
            "page" => (int)$Page,
            "limit" => (int)$Limit,
            "total" => (int)$Total,
            "total_pages" => $TotalPages
        ]
    ];

    if ($Message !== null) {
        $Response["message"] = $Message;
    }

    echo json_encode(
        $Response,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function errorResponse($Message, $StatusCode = 400, $Errors = [])
{
    http_response_code($StatusCode);

    $Response = [
        "success" => false,
        "error" => $Message
    ];

    if (!empty($Errors)) {
        $Response["errors"] = $Errors;
    }

    echo json_encode(
        $Response,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function notFoundResponse($Message = "Resource not found")
{
    errorResponse($Message, 404);
}

function methodNotAllowedResponse($AllowedMethods = [])
{
    if (!empty($AllowedMethods)) {
        header("Allow: " . implode(", ", $AllowedMethods));
    }

    errorResponse(
        "Method not allowed",
        405,
        [
            "allowed_methods" => $AllowedMethods
        ]
    );
}

function unauthorizedResponse($Message = "Authentication required")
{
    errorResponse($Message, 401);
}

function forbiddenResponse($Message = "Access denied")
{
    errorResponse($Message, 403);
}

function validationerrorResponse($Errors)
{
    errorResponse(
        "Validation failed",
        422,
        $Errors
    );
}

function tooManyRequestsResponse($Message = "Too many requests. Please try again later.", $RetryAfter = 60)
{
    header("Retry-After: " . (int)$RetryAfter);

    errorResponse(
        $Message,
        429,
        [
            "retry_after_seconds" => (int)$RetryAfter
        ]
    );
}

?>
