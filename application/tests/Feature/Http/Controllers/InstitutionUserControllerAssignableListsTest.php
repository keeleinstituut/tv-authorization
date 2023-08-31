<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserController;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\AuthHelpers;
use Tests\Feature\InstitutionUserHelpers;
use Tests\TestCase;
use Throwable;

class InstitutionUserControllerAssignableListsTest extends DepartmentControllerTestCase
{
    use InstitutionUserHelpers;

    /**
     * @return array<array{
     *     Closure(InstitutionUser, Collection<InstitutionUser>): array,
     *     Closure(TestCase, TestResponse, array, Collection<InstitutionUser>): void
     * }>
     */
    public static function providePayloadCreatorsAndExtraAssertions(): array
    {
        return [
            'No filtering' => [
                fn () => [],
                function (TestCase $test, TestResponse $response, array $payload, Collection $matchingInstitutionUsers) {
                    $test->assertArraysEqualIgnoringOrder(
                        $matchingInstitutionUsers->pluck('id')->all(),
                        $response->json('data.*.id')
                    );
                },
            ],
            'Filtering by nonexistent name' => [
                function (InstitutionUser $actingUser, Collection $matchingInstitutionUsers) {
                    $nonexistentName = Str::random();
                    throw_if(
                        $matchingInstitutionUsers->some(fn ($client) => static::matchesName($client, $nonexistentName)),
                        'Test case dataset is invalid'
                    );

                    return ['name' => $nonexistentName];
                },
                function (TestCase $test, TestResponse $response) {
                    $test->assertEmpty($response->json('data'));
                },
            ],
            'Filtering by lowercase forename' => [
                function (InstitutionUser $actingUser, Collection $matchingInstitutionUsers) {
                    $forename = Str::lower($matchingInstitutionUsers->firstOrFail()->user->forename);
                    throw_if(
                        $matchingInstitutionUsers->every(fn ($client) => static::matchesName($client, $forename)),
                        'Test case dataset is invalid'
                    );

                    return ['name' => $forename];
                },
                function (TestCase $test, TestResponse $response, array $payload, Collection $matchingInstitutionUsers) {
                    $test->assertNotEmpty($response->json('data'));
                    $test->assertArraysEqualIgnoringOrder(
                        $matchingInstitutionUsers->filter(fn ($client) => static::matchesName($client, $payload['name']))->pluck('id')->all(),
                        $response->json('data.*.id')
                    );
                },
            ],
            'Filtering by lowercase surname' => [
                function (InstitutionUser $actingUser, Collection $matchingInstitutionUsers) {
                    $surname = Str::lower($matchingInstitutionUsers->firstOrFail()->user->surname);
                    throw_if(
                        $matchingInstitutionUsers->every(fn ($client) => static::matchesName($client, $surname)),
                        'Test case dataset is invalid'
                    );

                    return ['name' => $surname];
                },
                function (TestCase $test, TestResponse $response, array $payload, Collection $matchingInstitutionUsers) {
                    $test->assertNotEmpty($response->json('data'));
                    $test->assertArraysEqualIgnoringOrder(
                        $matchingInstitutionUsers->filter(fn ($client) => static::matchesName($client, $payload['name']))->pluck('id')->all(),
                        $response->json('data.*.id')
                    );
                },
            ],
            'Filtering by first characters of uppercase forename' => [
                function (InstitutionUser $actingUser, Collection $matchingInstitutionUsers) {
                    $forenameSubstring = Str::of($matchingInstitutionUsers->firstOrFail()->user->forename)
                        ->upper()
                        ->limit(3, '')
                        ->toString();

                    return ['name' => $forenameSubstring];
                },
                function (TestCase $test, TestResponse $response, array $payload, Collection $matchingInstitutionUsers) {
                    $test->assertNotEmpty($response->json('data'));
                    $test->assertArraysEqualIgnoringOrder(
                        $matchingInstitutionUsers->filter(fn ($client) => static::matchesName($client, $payload['name']))->pluck('id')->all(),
                        $response->json('data.*.id')
                    );
                },
            ],
        ];
    }

    /**
     * @dataProvider providePayloadCreatorsAndExtraAssertions
     *
     * @param  Closure(InstitutionUser, Collection<InstitutionUser>): array  $createPayload
     * @param  Closure(TestCase, TestResponse, array, Collection<InstitutionUser>): void  $performExtraAssertions
     */
    public function test_expected_assignable_clients_listed_with_filtering(Closure $createPayload, Closure $performExtraAssertions): void
    {
        [
            'actingUser' => $actingUser,
            'candidateUsers' => $allInstitutionUsers
        ] = static::createAssignableVariantInstitutionUsers(PrivilegeKey::ChangeClient);
        $assignableClients = $allInstitutionUsers
            ->filter(fn (InstitutionUser $iu) => $iu->hasPrivileges(PrivilegeKey::CreateProject));

        $payload = $createPayload($actingUser, $assignableClients);

        $response = $this
            ->withHeaders(AuthHelpers::createHeadersForInstitutionUser($actingUser))
            ->getJson(action([InstitutionUserController::class, 'indexAssignableClients'], $payload));

        $response->assertOk();
        $response->assertJsonIsArray('data');
        $this->assertLessThan(count($allInstitutionUsers), count($response->json('data')));

        collect($response->json('data.*.id'))->each(function ($id) use ($assignableClients) {
            $this->assertContains($id, $assignableClients->pluck('id'));
        });

        $performExtraAssertions($this, $response, $payload, $assignableClients);
    }

    public static function provideRequiredPrivilegesForAssignableProjectManagerEndpoint(): array
    {
        return [
            PrivilegeKey::CreateProject->value => [PrivilegeKey::CreateProject],
            PrivilegeKey::ManageProject->value => [PrivilegeKey::ManageProject],
            PrivilegeKey::ReceiveAndManageProject->value => [PrivilegeKey::ReceiveAndManageProject],
        ];
    }

    /**
     * @dataProvider provideRequiredPrivilegesForAssignableProjectManagerEndpoint
     *
     * @throws Throwable
     */
    public function test_expected_project_managers_assignable_by_client_listed_having_different_privileges(PrivilegeKey $actingUserPrivilege): void
    {
        [
            'actingUser' => $actingUser,
            'candidateUsers' => $targetUsers
        ] = static::createAssignableVariantInstitutionUsers($actingUserPrivilege);

        $response = $this
            ->withHeaders(AuthHelpers::createHeadersForInstitutionUser($actingUser))
            ->getJson(action([InstitutionUserController::class, 'indexProjectManagersAssignableByClient']));

        $response->assertOk();
        $response->assertJsonIsArray('data');
        $this->assertLessThan(count($targetUsers), count($response->json('data')));
        $this->assertArraysEqualIgnoringOrder(
            $targetUsers
                ->filter(fn (InstitutionUser $iu) => $iu->hasPrivileges(PrivilegeKey::ReceiveAndManageProject))
                ->pluck('id')
                ->all(),
            $response->json('data.*.id')
        );
    }

    /**
     * @dataProvider providePayloadCreatorsAndExtraAssertions
     *
     * @param  Closure(InstitutionUser, Collection<InstitutionUser>): array  $createPayload
     * @param  Closure(TestCase, TestResponse, array, Collection<InstitutionUser>): void  $performExtraAssertions
     */
    public function test_expected_project_managers_assignable_by_client_listed_with_filtering(Closure $createPayload, Closure $performExtraAssertions): void
    {
        [
            'actingUser' => $actingUser,
            'candidateUsers' => $allInstitutionUsers
        ] = static::createAssignableVariantInstitutionUsers(PrivilegeKey::CreateProject);
        $assignableProjectManagers = $allInstitutionUsers
            ->filter(fn (InstitutionUser $iu) => $iu->hasPrivileges(PrivilegeKey::ReceiveAndManageProject));

        $payload = $createPayload($actingUser, $assignableProjectManagers);
        $response = $this
            ->withHeaders(AuthHelpers::createHeadersForInstitutionUser($actingUser))
            ->getJson(action(
                [InstitutionUserController::class, 'indexProjectManagersAssignableByClient'],
                $payload
            ));

        $response->assertOk();
        $response->assertJsonIsArray('data');
        $this->assertLessThan(count($allInstitutionUsers), count($response->json('data')));

        collect($response->json('data.*.id'))->each(function ($id) use ($assignableProjectManagers) {
            $this->assertContains($id, $assignableProjectManagers->pluck('id'));
        });

        $performExtraAssertions($this, $response, $payload, $assignableProjectManagers);
    }

    public static function providerEndpointMethodAndInsufficientPrivileges(): array
    {
        return [
            'assignable clients without CHANGE_CLIENT' => [
                'indexAssignableClients',
                collect(PrivilegeKey::cases())
                    ->map(fn (PrivilegeKey $privilege) => $privilege->value)
                    ->diff([PrivilegeKey::ChangeClient->value])
                    ->map(PrivilegeKey::from(...)),
            ],
            'assignable project managers without CREATE_PROJECT & MANAGE_PROJECT & RECEIVE_MANAGE_PROJECT' => [
                'indexProjectManagersAssignableByClient',
                collect(PrivilegeKey::cases())
                    ->map(fn (PrivilegeKey $privilege) => $privilege->value)
                    ->diff([
                        PrivilegeKey::CreateProject->value,
                        PrivilegeKey::ManageProject->value,
                        PrivilegeKey::ReceiveAndManageProject->value,
                    ])
                    ->map(PrivilegeKey::from(...)),
            ],
        ];
    }

    /**
     * @dataProvider providerEndpointMethodAndInsufficientPrivileges
     */
    public function test_403_when_insufficient_privileges(string $endpointMethod, Collection $actingUserPrivileges): void
    {
        ['actingUser' => $actingUser] = static::createAssignableVariantInstitutionUsers(...$actingUserPrivileges);

        $this
            ->withHeaders(AuthHelpers::createHeadersForInstitutionUser($actingUser))
            ->getJson(action([InstitutionUserController::class, $endpointMethod]))
            ->assertForbidden();
    }

    public function test_401_when_not_authenticated(): void
    {
        $this
            ->getJson(action([InstitutionUserController::class, 'indexAssignableClients']))
            ->assertUnauthorized();

        $this
            ->getJson(action([InstitutionUserController::class, 'indexProjectManagersAssignableByClient']))
            ->assertUnauthorized();
    }

    /**
     * @return array{
     *     institution: InstitutionUser,
     *     actingUser: InstitutionUser,
     *     candidateUsers: Collection<InstitutionUser>
     * }
     */
    private function createAssignableVariantInstitutionUsers(PrivilegeKey ...$actingUserPrivileges): array
    {
        $institution = Institution::factory()->create();

        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivileges($institution, ...$actingUserPrivileges);

        return [
            'institution' => $institution,
            'actingUser' => $actingUser,
            'candidateUsers' => collect([
                $actingUser,
                $this->createUserInGivenInstitutionWithGivenPrivileges($institution),
                $this->createUserInGivenInstitutionWithGivenPrivileges($institution, PrivilegeKey::CreateProject),
                $this->createUserInGivenInstitutionWithGivenPrivileges($institution, PrivilegeKey::ReceiveAndManageProject),
                $this->createUserInGivenInstitutionWithGivenPrivileges($institution, PrivilegeKey::CreateProject, PrivilegeKey::ReceiveAndManageProject),
            ]),
        ];
    }

    public static function matchesName(InstitutionUser $institutionUser, string $name): bool
    {
        return Str::contains(
            $institutionUser->user->forename.' '.$institutionUser->user->surname,
            $name,
            true
        );
    }
}
