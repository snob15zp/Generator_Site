<?php

namespace App\Http\Controllers;

class ErrorStatusCodes {
    public static $INVALID_CREDENTIALS = 100;
    public static $INVALID_TOKEN = 110;
    public static $RESOURCE_NOT_AVAILABLE = 120;
    public static $RESOURCE_ALREADY_EXISTS = 130;
    public static $RESOURCE_NOT_FOUND = 140;
    public static $USER_NOT_FOUND = 150;
    public static $UPLOAD_PROGRAMS_FAILED = 160;

    public static $FOLDER_NOT_FOUND = 170;
    public static $FOLDER_ALREADY_EXISTS = 180;
    public static $DELETE_FOLDER_FAILED = 190;
}
