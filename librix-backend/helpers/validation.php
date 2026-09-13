<?php

// required Field

function required($Value, $FieldName)
{
    if ($Value === null || (is_string($Value) && trim($Value) === "")) {
        return "$FieldName is required";
    }

    return null;
}


// Email Validation

function validateEmail($Email)
{
    if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email address";
    }

    return null;
}


// Minimum Length

function minLength($Value, $Length, $FieldName)
{
    if (strlen((string)$Value) < $Length) {
        return "$FieldName must be at least $Length characters";
    }

    return null;
}


// Maximum Length

function maxLength($Value, $Length, $FieldName)
{
    if (strlen((string)$Value) > $Length) {
        return "$FieldName must not exceed $Length characters";
    }

    return null;
}


// Integer Validation

function validateInteger($Value, $FieldName)
{
    if (filter_var($Value, FILTER_VALIDATE_INT) === false) {
        return "$FieldName must be a valid integer";
    }

    return null;
}


// Positive Integer

function validatePositiveInteger($Value, $FieldName)
{
    if (!filter_var($Value, FILTER_VALIDATE_INT) || (int)$Value <= 0) {
        return "$FieldName must be a positive integer";
    }

    return null;
}


// Page Number Validation

function validatePageNumber($Page)
{
    if ($Page === null || $Page === "") {
        return 1;
    }

    if (!filter_var($Page, FILTER_VALIDATE_INT) || (int)$Page < 1) {
        return 1;
    }

    return (int)$Page;
}


// Page Limit Validation

function validatePageLimit($Limit, $Default = 20, $Max = 100)
{
    if ($Limit === null || $Limit === "") {
        return $Default;
    }

    if (!filter_var($Limit, FILTER_VALIDATE_INT) || (int)$Limit < 1) {
        return $Default;
    }

    $Parsed = (int)$Limit;

    if ($Parsed > $Max) {
        return $Max;
    }

    return $Parsed;
}


// Validation Errors Check

function hasValidationErrors($Errors)
{
    return !empty($Errors);
}

?>