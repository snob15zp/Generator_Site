<?php

namespace App\Http\Dto;

use DateTime;

class Firmware
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
     * @var CpuFirmware
     */
    public $cpu;

    /**
     * @var FpgaFirmware
     */
    public $fpga;

    public function getPath(): string
    {
        return env('FIRMWARE_PATH') . '/' . $this->version;
    }

    public function getFiles(): array {
        return [
            CpuFirmware::FILE_NAME => storage_path('app/' . $this->getPath() . '/' . CpuFirmware::FILE_NAME),
            FpgaFirmware::FILE_NAME => storage_path('app/' . $this->getPath() . '/' . FpgaFirmware::FILE_NAME)
        ];
    }

    public function toArray(): array {
        return [
            'version' => $this->version,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'cpu' => $this->cpu->toArray(),
            'fgpa' => $this->fpga->toArray()
        ];
    }
}
