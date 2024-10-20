<?php

namespace App\Service;

class FeeStructureLoader
{
    public function __construct(private string $filePath)
    {
    }

    public function load(): array
    {
        if (!file_exists($this->filePath)) {
            throw new ("Fee structure file not found.");
        }

        $jsonContent = file_get_contents($this->filePath);
        $feeStructure = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON format in fee structure file.");
        }

        return $feeStructure;
    }
}
