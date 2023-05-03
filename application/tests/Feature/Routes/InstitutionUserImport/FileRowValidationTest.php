<?php

namespace Feature\Routes\InstitutionUserImport;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\InstitutionUserImportController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\EntityHelpers;
use Tests\TestCase;

class FileRowValidationTest extends TestCase
{
    use RefreshDatabase, EntityHelpers;

    public function test_valid_row_returned_200(): void
    {
        $institution = $this->createInstitution();
        $role = $this->createRoleWithPrivileges($institution, [
            PrivilegeKey::DeactivateUser,
            PrivilegeKey::ActivateUser,
        ]);

        $row = [
            'personal_identification_code' => '39511267470',
            'name' => 'user name',
            'email' => 'some@email.com',
            'phone' => '+372 56789566',
            'department' => '',
            'role' => $role->name,
            'is_vendor' => 'false',
        ];

        $this->sendValidationRequest($row, $this->getAccessToken([PrivilegeKey::AddUser], $institution->id))
            ->assertStatus(Response::HTTP_OK);
    }

    public function test_invalid_row_returned_422(): void
    {
        $row = [
            'personal_identification_code' => '39511267471',
            'name' => 'user name 234',
            'email' => 'someemail.com',
            'phone' => '+372 567895',
            'department' => '',
            'role' => 'wrong_role',
            'is_vendor' => false,
        ];

        $this->sendValidationRequest($row, $this->getAccessToken([PrivilegeKey::AddUser]))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'errors' => [
                    'personal_identification_code' => [],
                    'name' => [],
                    'phone' => [],
                    'email' => [],
                    'is_vendor' => [],
                ],
            ]);
    }

    public function test_unauthorized_request_returned_403(): void
    {
        $this->sendValidationRequest([])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    private function sendValidationRequest(array $row, string $accessToken = ''): TestResponse
    {
        if (! empty($accessToken)) {
            $this->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'Accept' => 'application/json',
            ]);
        }

        return $this->postJson(
            action([InstitutionUserImportController::class, 'validateRow']),
            $row
        );
    }

    private function getAccessToken(array $privileges, ?string $institutionId = null): string
    {
        $institutionId = $institutionId ?: Str::orderedUuid();

        return $this->generateAccessToken([
            'selectedInstitution' => [
                'id' => $institutionId,
            ],
            'privileges' => array_map(fn (PrivilegeKey $key) => $key->value, $privileges),
        ]);
    }
}
