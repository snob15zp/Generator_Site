<?php

namespace App\Http\Dto;

use DateTime;

class Firmware
{
    public $name;
    public $version;
    public $createdAt;
    public $device;
    public $hash;

    public function __construct(string $name, string $version, DateTime $createdAt, string $device, string $hash)
    {
        $this->name = $name;
        $this->version = $version;
        $this->createdAt = $createdAt;
        $this->device = $device;
        $this->hash = $hash;
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
