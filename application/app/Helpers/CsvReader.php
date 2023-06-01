<?php

namespace App\Helpers;

use Generator;

class CsvReader
{
    private $file;

    public function __construct(readonly string $filename, private readonly string $separator = ',')
    {
        $this->file = fopen($filename, 'r');
    }

    public function headers(): array
    {
        rewind($this->file);

        return fgetcsv($this->file, 0, $this->separator);
    }

    public function rows(): Generator
    {
        while (! feof($this->file)) {
            $row = fgetcsv($this->file, 0, $this->separator);
            is_array($row) && yield $row;
        }
    }
}
