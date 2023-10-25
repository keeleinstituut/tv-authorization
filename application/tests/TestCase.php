<?php

namespace Tests;

use App\Models\Institution;
use App\Models\InstitutionUser;
use Closure;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;
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
        ?Closure $modifyInstitution = null,
        ?Closure $modifyActingInstitutionUser = null
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
        $this->assertNotNull($expectedSubset);
        $this->assertNotNull($actual);

        $sortedDottedExpectedSubset = Arr::dot(Arr::sortRecursive($expectedSubset));
        $sortedDottedActualWholeArray = Arr::dot(Arr::sortRecursive($actual));
        $sortedDottedActualSubset = Arr::only($sortedDottedActualWholeArray, array_keys($sortedDottedExpectedSubset));

        $this->assertArraysEqualIgnoringOrder($sortedDottedExpectedSubset, $sortedDottedActualSubset);
    }

    public function assertArraysEqualIgnoringOrder(?array $expected, ?array $actual): void
    {
        $this->assertNotNull($expected);
        $this->assertNotNull($actual);

        $this->assertEquals(
            Arr::sortRecursive($expected),
            Arr::sortRecursive($actual)
        );
    }
}
