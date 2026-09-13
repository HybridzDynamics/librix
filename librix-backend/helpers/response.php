<?php

// Success Response

function SuccessResponse($Data = [], $Message = null, $StatusCode = 200)
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


// Paginated Response

function PaginatedResponse($Data, $Page, $Limit, $Total, $Message = null, $StatusCode = 200)
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


// Error Response

function ErrorResponse($Message, $StatusCode = 400, $Errors = [])
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


// Not Found Response

function NotFoundResponse($Message = "Resource not found")
{
    ErrorResponse($Message, 404);
}


// Method Not Allowed Response

function MethodNotAllowedResponse($AllowedMethods = [])
{
    if (!empty($AllowedMethods)) {
        header("Allow: " . implode(", ", $AllowedMethods));
    }

    ErrorResponse(
        "Method not allowed",
        405,
        [
            "allowed_methods" => $AllowedMethods
        ]
    );
}


// Unauthorized Response

function UnauthorizedResponse($Message = "Authentication required")
{
    ErrorResponse($Message, 401);
}


// Forbidden Response

function ForbiddenResponse($Message = "Access denied")
{
    ErrorResponse($Message, 403);
}


// Validation Error Response

function ValidationErrorResponse($Errors)
{
    ErrorResponse(
        "Validation failed",
        422,
        $Errors
    );
}


// Too Many Requests Response

function TooManyRequestsResponse($Message = "Too many requests. Please try again later.", $RetryAfter = 60)
{
    header("Retry-After: " . (int)$RetryAfter);

    ErrorResponse(
        $Message,
        429,
        [
            "retry_after_seconds" => (int)$RetryAfter
        ]
    );
}

?>