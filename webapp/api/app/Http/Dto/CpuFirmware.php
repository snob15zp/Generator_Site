<?php

namespace App\Http\Dto;

use DateTime;

class CpuFirmware
{
    const FILE_NAME = 'cpu.bf';


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

    public function __construct(string $version, DateTime $createdAt, string $device)
    {
        $this->version = $version;
        $this->createdAt = $createdAt;
        $this->device = $device;
    }

    public function getFileName(): string
    {
        return self::FILE_NAME;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getFileName(),
            'version' => $this->version,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'device' => $this->device
        ];
    }
}
