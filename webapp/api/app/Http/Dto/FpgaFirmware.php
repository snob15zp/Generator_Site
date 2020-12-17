<?php

namespace App\Http\Dto;

class FpgaFirmware
{

    const FILE_NAME = 'fpga.bin';

    public function getFileName(): string
    {
        return self::FILE_NAME;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getFileName()
        ];
    }
}
