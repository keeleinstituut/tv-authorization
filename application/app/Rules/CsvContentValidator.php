<?php

namespace App\Rules;

use App\Helpers\CsvReader;
use Generator;
use RuntimeException;
use UnexpectedValueException;
use Validator;

class CsvContentValidator
{
    private array $rules;

    private array $attributesNames;

    private array $headers;

    private CsvReader $reader;

    public function __construct(string $csvPath, string $separator = ';')
    {
        $this->reader = new CsvReader($csvPath, $separator);
    }

    public function setRules(array $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    public function setExpectedHeaders(array $headers): static
    {
        $this->headers = $headers;

        return $this;
    }

    public function setAttributesNames(array $attributesNames): static
    {
        $this->attributesNames = $attributesNames;

        return $this;
    }

    public function validatedRows(): Generator
    {
        if (empty($this->rules)) {
            throw new RuntimeException('Rules are not set');
        }

        if (empty($this->attributesNames)) {
            throw new RuntimeException('Attributes names are not set');
        }

        if (! empty($this->headers) && $this->reader->headers() !== $this->headers) {
            throw new UnexpectedValueException('The file has incorrect headers');
        }

        $columnsCount = count($this->attributesNames);
        foreach ($this->reader->rows() as $row) {
            if (count($row) !== $columnsCount) {
                throw new UnexpectedValueException("Amount of columns doesn't match with expected");
            }

            $attributes = array_combine($this->attributesNames, $row);
            $attributes['errors'] = $this->getValidationErrors($attributes);

            yield $attributes;
        }
    }

    private function getValidationErrors(array $attributes): array
    {
        $validator = Validator::make($attributes, $this->rules);

        return $validator->errors()->all();
    }
}
