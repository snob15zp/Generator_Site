<?php

namespace App\Exceptions;

use App\Http\Controllers\ErrorStatusCodes;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UploadFilesException extends HttpException
{
    public $exists;
    public $failed;

    public function __construct(array $exists, array $failed)
    {
        $this->exists = $exists;
        $this->failed = $failed;
        parent::__construct(500, "Failed to upload files", null, [], ErrorStatusCodes::$UPLOAD_PROGRAMS_FAILED);
    }
}
