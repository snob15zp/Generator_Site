<?php

namespace App\Http\Dto;

use DateTime;

class CpuFirmware
{
    /**
     * @var string
     */
    public $version;

    /**
     * @var DateTime
     */
    public $createdAt;

    /**
     * @var string
     */
    public $device;

    /**
     * @var string
     */
    public $name;

    public function __construct(string $name, string $version, DateTime $createdAt, string $device)
    {
        $this->version = $version;
        $this->createdAt = $createdAt;
        $this->device = $device;
        $this->name = $name;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'device' => $this->device
        ];
    }
}
