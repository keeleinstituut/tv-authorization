<?php

namespace Tests\Feature\Models\Database;

use App\Models\Institution;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving(): void
    {
        $createdModel = Institution::factory()->create();
        $this->assertModelExists($createdModel);

        $retrievedModel = Institution::findOrFail($createdModel->id);
        $this->assertEquals($createdModel->name, $retrievedModel->name);
    }

    /**
     * @return array<array{
     *     validInitialState: array<string, string>,
     *     invalidatingChange: array<string, string>
     * }>
     */
    public static function provideValidInitialWorktimesAndInvalidatingChanges(): array
    {
        return collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ->flatMap(fn (string $day) => [
                "[$day] Setting start and end without worktime_timezone being set" => [
                    'validInitialState' => [],
                    'invalidatingChange' => ["{$day}_worktime_start" => '08:00:00', "{$day}_worktime_start" => '16:00:00'],
                ],
                "[$day] Setting worktime_timezone to a non-IANA value: gibberish" => [
                    'validInitialState' => [],
                    'invalidatingChange' => ['worktime_timezone' => 'Westeros/Winterfell'],
                ],
                "[$day] Setting worktime_timezone to a non-IANA value: timezone abbreviation" => [
                    'validInitialState' => [],
                    'invalidatingChange' => ['worktime_timezone' => 'PST'],
                ],
                "[$day] Setting worktime_timezone to a non-IANA value: numerical offset (+2)" => [
                    'validInitialState' => [],
                    'invalidatingChange' => ['worktime_timezone' => '+2'],
                ],
                "[$day] Setting worktime_timezone to a non-IANA value: numerical offset (+3:00)" => [
                    'validInitialState' => [],
                    'invalidatingChange' => ['worktime_timezone' => '+3:00'],
                ],
                "[$day] Initially without worktime, setting start without end" => [
                    'validInitialState' => [],
                    'invalidatingChange' => [
                        'worktime_timezone' => 'Europe/Tallinn',
                        "{$day}_worktime_start" => '08:00:00',
                    ],
                ],
                "[$day] Initially without worktime, setting end without start" => [
                    'validInitialState' => [],
                    'invalidatingChange' => [
                        'worktime_timezone' => 'Europe/Tallinn',
                        "{$day}_worktime_end" => '16:00:00',
                    ],
                ],
                "[$day] Initially without worktime, setting start and end, such that end < start" => [
                    'validInitialState' => [],
                    'invalidatingChange' => [
                        'worktime_timezone' => 'Europe/Tallinn',
                        "{$day}_worktime_start" => '16:00:00',
                        "{$day}_worktime_end" => '08:00:00',
                    ],
                ],
                "[$day] Initially without worktime, setting start and end, such that end = start" => [
                    'validInitialState' => [],
                    'invalidatingChange' => [
                        'worktime_timezone' => 'Europe/Tallinn',
                        "{$day}_worktime_start" => '08:00:00',
                        "{$day}_worktime_end" => '08:00:00',
                    ],
                ],
                "[$day] Initially with worktime, changing worktime_timezone to null" => [
                    'validInitialState' => [
                        'worktime_timezone' => 'Europe/Tallinn',
                        "{$day}_worktime_start" => '08:00:00',
                        "{$day}_worktime_end" => '16:00:00',
                    ],
                    'invalidatingChange' => ['worktime_timezone' => null],
                ],
                "[$day] Initially with worktime, changing start to null" => [
                    'validInitialState' => [
                        'worktime_timezone' => 'Europe/Tallinn',
                        "{$day}_worktime_start" => '08:00:00',
                        "{$day}_worktime_end" => '16:00:00',
                    ],
                    'invalidatingChange' => ["{$day}_worktime_start" => null],
                ],
                "[$day] Initially with worktime, changing end to null" => [
                    'validInitialState' => [
                        'worktime_timezone' => 'Europe/Tallinn',
                        "{$day}_worktime_start" => '08:00:00',
                        "{$day}_worktime_end" => '16:00:00',
                    ],
                    'invalidatingChange' => ["{$day}_worktime_end" => null],
                ],
                "[$day] Initially with worktime, changing end, such that end < start" => [
                    'validInitialState' => [
                        'worktime_timezone' => 'Europe/Tallinn',
                        "{$day}_worktime_start" => '08:00:00',
                        "{$day}_worktime_end" => '16:00:00',
                    ],
                    'invalidatingChange' => ["{$day}_worktime_end" => '02:00:00'],
                ],
                "[$day] Initially with worktime, changing end, such that end = start" => [
                    'validInitialState' => [
                        'worktime_timezone' => 'Europe/Tallinn',
                        "{$day}_worktime_start" => '08:00:00',
                        "{$day}_worktime_end" => '16:00:00',
                    ],
                    'invalidatingChange' => ["{$day}_worktime_end" => '08:00:00'],
                ],
            ])
            ->all();
    }

    /** @dataProvider provideValidInitialWorktimesAndInvalidatingChanges */
    public function test_saving_invalid_working_times_result_in_database_error(array $validInitialWorktimes, array $invalidatingChange): void
    {
        $institution = Institution::factory()->create($validInitialWorktimes)->refresh();
        $institution->fill($invalidatingChange);

        $this->expectException(QueryException::class);
        $institution->saveOrFail();
    }
}
