<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class ArrayExport implements FromArray
{
    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function array(): array
    {
        return $this->rows;
    }
}
