<?php

namespace Tests\Unit\Util;

use App\Util\DateUtil;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\TestCase;

class DateUtilTest extends TestCase
{
    /** @dataProvider provideDates */
    public function test_current_estonian_date_at_midnight_satisfies_expectations(CarbonInterface $testDate)
    {
        Date::setTestNow($testDate);
        $actual = DateUtil::currentEstonianDateAtMidnight();

        $this->assertDateTimePartEqualsMidnight($actual);
        $this->assertDateHasEstonianTimezone($actual);
    }

    /** @dataProvider provideDates */
    public function test_estonian_now_satisfies_expectations(CarbonInterface $testDate)
    {
        Date::setTestNow($testDate);

        $this->assertDateHasEstonianTimezone(DateUtil::estonianNow());
        $this->assertDatesHaveSameAbsoluteValue(Date::now(), DateUtil::estonianNow());
    }

    /** @dataProvider provideDates */
    public function test_convert_to_estonian_midnight_satisfies_expectations(CarbonInterface $testDate)
    {
        $actual = DateUtil::convertDateTimeObjectToEstonianMidnight($testDate);

        $this->assertDateTimePartEqualsMidnight($actual);
        $this->assertDateHasEstonianTimezone($actual);
    }

    public function test_current_estonian_date_at_midnight_changes_date_when_required()
    {
        Date::setTestNow(Date::create(1999, 12, 31, 23, 0, 0, 'UTC'));
        $this->assertDateNonTimePartEquals(2000, 1, 1, DateUtil::currentEstonianDateAtMidnight());

        Date::setTestNow(Date::create(1999, 12, 31, 23, 0, 0, 'UTC'));
        $this->assertDateNonTimePartEquals(2000, 1, 1, DateUtil::currentEstonianDateAtMidnight());
    }

    public function test_convert_to_estonian_midnight_changes_date_when_required()
    {
        Date::setTestNow(Date::create(1999, 12, 31, 23, 0, 0, 'UTC'));
        $this->assertDateNonTimePartEquals(2000, 1, 1, DateUtil::estonianNow());

        Date::setTestNow(Date::create(2000, 01, 01, tz: 'UTC'));
        $this->assertDateNonTimePartEquals(2000, 1, 1, DateUtil::estonianNow());
    }

    public function assertDateTimePartEqualsMidnight(CarbonInterface $actual): void
    {
        $this->assertEquals(0, $actual->hour);
        $this->assertEquals(0, $actual->minute);
        $this->assertEquals(0, $actual->millisecond);
    }

    public function assertDateNonTimePartEquals(string $expectedYear, string $expectedMonth, string $expectedDay, CarbonInterface $actualDate): void
    {
        $this->assertEquals($expectedYear, $actualDate->year);
        $this->assertEquals($expectedMonth, $actualDate->month);
        $this->assertEquals($expectedDay, $actualDate->day);
    }

    public function assertDateHasEstonianTimezone(CarbonInterface $actual): void
    {
        $this->assertEquals(
            (new CarbonTimeZone('Europe/Tallinn'))->toOffsetName(),
            $actual->timezone->toOffsetName()
        );
    }

    public function assertDatesHaveSameAbsoluteValue(CarbonInterface $expected, CarbonInterface $actual): void
    {
        $this->assertEquals(
            $expected->toISOString(),
            $actual->toISOString()
        );
    }

    /** @return array<array<CarbonImmutable>> */
    public static function provideDates(): array
    {
        return collect([
            '1955-06-04T14:56:13Z',
            '2034-10-23T04:25:47-0500',
            '2023-05-19T19:34:55+0300',
            '2010-02-27T22:12:07+0200',
            '1999-12-31T23:00:00Z',
            '1999-12-31T23:00:00+0300',
            '2000-01-01T01:00:00Z',
            '2000-01-01T01:00:00+0300',
        ])
            ->mapWithKeys(fn ($value) => [$value => [CarbonImmutable::parse($value)]])
            ->all();
    }
}
