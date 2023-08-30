<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserController;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Illuminate\Support\Collection;
use Tests\AuthHelpers;
use Tests\Feature\InstitutionUserHelpers;
use Throwable;

class InstitutionUserControllerAssignableListsTest extends DepartmentControllerTestCase
{
    use InstitutionUserHelpers;

    /** @throws Throwable */
    public function test_expected_assignable_clients_listed(): void
    {
        [
            'actingUser' => $actingUser,
            'candidateUsers' => $targetUsers
        ] = static::createAssignableVariantInstitutionUsers(PrivilegeKey::ChangeClient);

        $response = $this
            ->withHeaders(AuthHelpers::createHeadersForInstitutionUser($actingUser))
            ->getJson(action([InstitutionUserController::class, 'indexAssignableClients']));

        $response->assertOk();
        $response->assertJsonIsArray('data');
        $this->assertLessThan(count($targetUsers), count($response->json('data')));
        $this->assertArraysEqualIgnoringOrder(
            $targetUsers
                ->filter(fn (InstitutionUser $iu) => $iu->hasPrivileges(PrivilegeKey::CreateProject))
                ->pluck('id')
                ->all(),
            $response->json('data.*.id')
        );
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
    public function test_expected_project_managers_assignable_by_client_listed(PrivilegeKey $actingUserPrivilege): void
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
}
