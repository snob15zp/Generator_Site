<?php

namespace App\Exceptions;


use App\Http\Controllers\ErrorStatusCodes;
use Symfony\Component\HttpKernel\Exception\HttpException;

const ERROR_DUPLICATE_PROGRAM = 800;
const ERROR_SQL_EXCEPTION = 1000;

class ApiException extends HttpException
{
    public function __construct($code, $statusCode = null)
    {
        switch ($code) {
            case ErrorStatusCodes::$FOLDER_NOT_FOUND:
                $message = "Folder not found";
                $statusCode = 404;
                break;
            case ErrorStatusCodes::$FOLDER_ALREADY_EXISTS:
                $message = "Folder already exists";
                break;
            case ErrorStatusCodes::$USER_NOT_FOUND:
                $message = "User not found";
                $statusCode = 404;
                break;
            case ErrorStatusCodes::$RESOURCE_ALREADY_EXISTS:
                $message = "Resource already present";
                break;
            case ErrorStatusCodes::$RESOURCE_NOT_FOUND:
                $message = "Resource not found";
                $statusCode = 404;
                break;
            case ErrorStatusCodes::$RESOURCE_NOT_AVAILABLE:
                $message = "Operation is forbidden";
                $statusCode = 403;
                break;
            case ErrorStatusCodes::$INVALID_CREDENTIALS:
                $message = "Invalid credentials";
                break;
            case ERROR_DUPLICATE_PROGRAM:
                $message = "Duplicate program";
                break;
            case ErrorStatusCodes::$DELETE_FOLDER_FAILED:
                $message = "Unable to delete folder";
                break;
            default:
                $message = "Unknown server error";
        }

        parent::__construct($statusCode, $message, null, [], $code);
    }
}
