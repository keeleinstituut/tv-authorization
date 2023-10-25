<?php

namespace App\Helpers;

use Generator;
use RuntimeException;

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

        $headers = fgetcsv($this->file, 0, $this->separator);
        if ($headers === false) {
            throw new RuntimeException('File has incorrect format');
        }

        return $this->getHeadersWithoutUtf8Bom($headers);
    }

    public function rows(): Generator
    {
        while (! feof($this->file)) {
            $row = fgetcsv($this->file, 0, $this->separator);
            is_array($row) && yield $row;
        }
    }

    private function getHeadersWithoutUtf8Bom(array $row): array
    {
        $firstHeaderColumn = $row[0] ?? '';
        if (substr($firstHeaderColumn, 0, 3) == chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF'))) {
            $firstHeaderColumn = substr($firstHeaderColumn, 3);
            $row[0] = $firstHeaderColumn;
        }

        return $row;
    }
}
