<?php

namespace App\Faker;

use Faker\Provider\Base;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EstonianPicFaker extends Base
{
    /** @noinspection PhpUnused */
    public function estonianPIC(): string
    {
        $dateOfBirth = Carbon::parse($this->generator->dateTimeBetween('1900-01-01', 'yesterday'));
        $gender = $this->generator->numberBetween(0, 1);
        $genderCenturyNumber = ($dateOfBirth->century - 18) * 2 - $gender;
        $birthIndex = $this->generator->numberBetween(0, 999);

        $codeWithoutChecksum = Str::of($genderCenturyNumber)
            ->append($dateOfBirth->format('ymd'))
            ->append(Str::padLeft($birthIndex, 3, '0'));

        $firstChecksumVariant = collect(str_split($codeWithoutChecksum))
            ->zip([1, 2, 3, 4, 5, 6, 7, 8, 9, 1])
            ->mapSpread(fn (string $digit, int $weight) => (int) $digit * $weight)
            ->sum() % 11;

        $secondChecksumVariant = collect(str_split($codeWithoutChecksum))
            ->zip([3, 4, 5, 6, 7, 8, 9, 1, 2, 3])
            ->mapSpread(fn (string $digit, int $weight) => (int) $digit * $weight)
            ->sum() % 11;

        $checksum = $firstChecksumVariant < 10 ? $firstChecksumVariant : $secondChecksumVariant;

        return $codeWithoutChecksum.$checksum;
    }
}
