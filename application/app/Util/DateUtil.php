<?php

namespace App\Util;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;

class DateUtil
{
    public final const ESTONIAN_TIMEZONE = 'Europe/Tallinn';

    public static function convertDateTimeObjectToEstonianMidnight(DateTimeInterface $dateTime): CarbonImmutable
    {
        return CarbonImmutable::parse($dateTime)
            ->timezone(self::ESTONIAN_TIMEZONE)
            ->setTime(0, 0)
            ->toImmutable();
    }

    public static function convertStringToEstonianMidnight(string $dateString): CarbonImmutable
    {
        return Date::parse($dateString, DateUtil::ESTONIAN_TIMEZONE)
            ->setTime(0, 0)
            ->toImmutable();
    }

    public static function currentEstonianDateAtMidnight(): CarbonImmutable
    {
        return self::convertDateTimeObjectToEstonianMidnight(Date::now());
    }

    public static function estonianNow(): CarbonImmutable
    {
        return CarbonImmutable::now(self::ESTONIAN_TIMEZONE);
    }
}
