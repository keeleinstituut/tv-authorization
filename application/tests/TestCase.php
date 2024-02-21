<?php

namespace Tests;

use App\Models\Institution;
use App\Models\InstitutionUser;
use Closure;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setUp(): void
    {
        parent::setup();
        AuthHelpers::fakeServiceAccountJWTResponse();
    }

    /**
     * @param  Closure(Institution):void|null  $modifyInstitution
     * @param  Closure(InstitutionUser):void|null  $modifyActingInstitutionUser
     * @return array{
     *     institution: Institution,
     *     actingInstitutionUser: InstitutionUser,
     * }
     *
     * @throws Throwable
     */
    public static function createInstitutionAndActingUser(
        Closure $modifyInstitution = null,
        Closure $modifyActingInstitutionUser = null
    ): array {
        $institution = Institution::factory()->create();
        $actingInstitutionUser = InstitutionUser::factory()->for($institution)->create();

        if (filled($modifyInstitution)) {
            $modifyInstitution($institution);
            $institution->saveOrFail();
        }

        if (filled($modifyActingInstitutionUser)) {
            $modifyActingInstitutionUser($actingInstitutionUser);
            $actingInstitutionUser->saveOrFail();
        }

        return [
            'institution' => $institution->refresh(),
            'actingInstitutionUser' => $actingInstitutionUser->refresh(),
        ];
    }

    public function assertResponseJsonDataEqualsIgnoringOrder(array $expectedData, TestResponse $actualResponse): void
    {
        $this->assertArraysEqualIgnoringOrder($expectedData, $actualResponse->json('data'));
    }

    public function assertArrayHasSubsetIgnoringOrder(?array $expectedSubset, ?array $actual): void
    {
        Assertions::assertArrayHasSubsetIgnoringOrder($expectedSubset, $actual);
    }

    public function assertArraysEqualIgnoringOrder(?array $expected, ?array $actual): void
    {
        Assertions::assertArraysEqualIgnoringOrder($expected, $actual);
    }

    public static function convertTrimWhiteSpaceToNullRecursively(array $array): array
    {
        return collect($array)
            ->map(function (mixed $value) {
                return match (gettype($value)) {
                    'string' => Str::of($value)->trim()->toString() ?: null,
                    'array' => static::convertTrimWhiteSpaceToNullRecursively($value),
                    default => $value
                };
            })
            ->all();
    }
}
