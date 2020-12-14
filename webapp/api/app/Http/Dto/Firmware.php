<?php

namespace App\Http\Dto;

use DateTime;

class Firmware
{
    public $version;
    public $createdAt;
    public $device;
    public $hash;

    public function __construct(string $version, DateTime $createdAt, string $device, string $hash)
    {
        $this->version = $version;
        $this->createdAt = $createdAt;
        $this->device = $device;
        $this->hash = $hash;
    }

    public function getFileName()
    {
        return $this->version . '-' . $this->createdAt->getTimestamp() . '.bf';
    }

    public function toArray() {
        return [
            'version' => $this->version,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'device' => $this->device,
            'hash' => $this->hash
        ];
    }
}
