<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PrivilegeKey;
use App\Models\Institution;
use App\Models\InstitutionUser;
use AuditLogClient\Enums\AuditLogEventFailureType;
use AuditLogClient\Enums\AuditLogEventObjectType;
use AuditLogClient\Enums\AuditLogEventType;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\TestResponse;
use Tests\Assertions;
use Tests\AuditLogTestCase;
use Tests\AuthHelpers;
use Tests\Feature\InstitutionUserHelpers;
use Tests\Feature\ModelAssertions;
use Tests\Feature\RepresentationHelpers;
use Throwable;

class InstitutionUserControllerUpdateCurrentTest extends AuditLogTestCase
{
    use InstitutionUserHelpers, ModelAssertions, RefreshDatabase;

    public function setUp(): void
    {
        parent::setup();
        Carbon::setTestNow(Carbon::now());
    }

    /**
     * @throws Throwable
     */
    public function test_fields_are_updated(): void
    {
        // GIVEN the following data is in database
        $createdInstitution = Institution::factory()->create();
        $actingInstitutionUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::ViewUser);

        $institutionUserBeforeRequest = $actingInstitutionUser->getAuditLogRepresentation();
        $institutionUserIdentityBeforeRequest = $actingInstitutionUser->getIdentitySubset();

        // WHEN request sent to endpoint
        $response = $this->sendPutRequestWithTokenFor(
            $payload = [
                'user' => [
                    'forename' => $expectedForename = 'Forename',
                    'surname' => $expectedSurname = 'Surname',
                ],
                'phone' => $expectedPhoneNumber = '+372 5678901',
                'email' => $expectedEmail = 'new@email.com',
            ],
            $actingInstitutionUser
        );

        // THEN the database state should be what is expected after update
        $actualState = RepresentationHelpers::createInstitutionUserNestedRepresentation(
            InstitutionUser::findOrFail($actingInstitutionUser->id)
        );

        $expectedFragment = [
            'phone' => $expectedPhoneNumber,
            'email' => $expectedEmail,
            'user' => [
                ...RepresentationHelpers::createUserFlatRepresentation($actingInstitutionUser->user),
                'forename' => $expectedForename,
                'surname' => $expectedSurname,
            ],
        ];
        $this->assertArrayHasSubsetIgnoringOrder($expectedFragment, $actualState);

        // And request response should correspond to the actual state
        $this->assertResponseJsonDataEqualsIgnoringOrder($actualState, $response);

        $this->assertMessageRepresentsInstitutionUserModification(
            $this->retrieveLatestAuditLogMessageBody(),
            $actingInstitutionUser,
            $institutionUserBeforeRequest,
            function (array $actualEventParameters) use ($institutionUserBeforeRequest, $payload, $institutionUserIdentityBeforeRequest) {
                $expectedEventParameters = [
                    'object_type' => AuditLogEventObjectType::InstitutionUser->value,
                    'object_identity_subset' => $institutionUserIdentityBeforeRequest,
                    'pre_modification_subset' => [
                        ...Arr::only($institutionUserBeforeRequest, ['phone', 'email']),
                        'user' => Arr::only($institutionUserBeforeRequest['user'], ['forename', 'surname']),
                    ],
                    'post_modification_subset' => $payload,
                ];

                $this->assertArraysEqualIgnoringOrder($expectedEventParameters, $actualEventParameters);
            }
        );
    }

    /**
     * @dataProvider provideInvalidRequestPayloads
     *
     * @throws Throwable
     */
    public function test_request_validation(array $invalidPayload): void
    {
        // GIVEN the following data is in database
        $createdInstitution = Institution::factory()->create();
        $actingUser = $this->createUserInGivenInstitutionWithGivenPrivilege($createdInstitution, PrivilegeKey::EditUser);
        $expectedEmail = $actingUser->email;
        $expectedPhone = $actingUser->phone;
        // WHEN invalid payload is sent to endpoint
        $response = $this->sendPutRequestWithTokenFor(
            $payload = [
                'email' => 'someother@email.com',
                'phone' => '+372 45678901',
                ...$invalidPayload,
            ],
            $actingUser
        );

        // THEN the database state should not change
        $this->assertEquals($expectedEmail, InstitutionUser::findOrFail($actingUser->id)->email);
        $this->assertEquals($expectedPhone, InstitutionUser::findOrFail($actingUser->id)->phone);

        // And response should indicate validation errors
        $response->assertUnprocessable();

        $actualMessageBody = $this->retrieveLatestAuditLogMessageBody();

        $expectedMessageBodySubset = [
            'event_type' => AuditLogEventType::ModifyObject->value,
            'happened_at' => Date::getTestNow()->toISOString(),
            'trace_id' => static::TRACE_ID,
            'failure_type' => AuditLogEventFailureType::UNPROCESSABLE_ENTITY->value,
            'context_institution_id' => $actingUser->institution_id,
            'context_department_id' => $actingUser->department_id,
            'acting_institution_user_id' => $actingUser->id,
            'acting_user_pic' => $actingUser->user->personal_identification_code,
            'acting_user_forename' => $actingUser->user->forename,
            'acting_user_surname' => $actingUser->user->surname,
        ];

        Assertions::assertArraysEqualIgnoringOrder(
            $expectedMessageBodySubset,
            collect($actualMessageBody)->intersectByKeys($expectedMessageBodySubset)->all(),
        );

        $eventParameters = data_get($actualMessageBody, 'event_parameters');
        $this->assertIsArray($eventParameters);

        $expectedEventParameters = [
            'object_type' => AuditLogEventObjectType::InstitutionUser->value,
            'object_identity_subset' => $actingUser->getIdentitySubset(),
            'input' => static::convertTrimWhiteSpaceToNullRecursively($payload),
        ];
        Assertions::assertArraysEqualIgnoringOrder(
            $expectedEventParameters,
            $eventParameters
        );
    }

    /**
     * @throws Throwable
     */
    public function test_updating_user_without_access_token(): void
    {

        // WHEN request sent without access token in header
        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->putJson(
                '/api/institution-users',
                ['email' => 'someother@email.com']
            );

        // And response should indicate that the request failed authentication
        $response->assertUnauthorized();
    }

    private function sendPutRequestWithTokenFor(
        array $requestPayload,
        InstitutionUser $actingUser,
        array $tolkevaravClaimsOverride = []
    ): TestResponse {
        return $this->sendPutRequestWithCustomToken(
            $requestPayload,
            AuthHelpers::generateAccessTokenForInstitutionUser($actingUser, $tolkevaravClaimsOverride)
        );
    }

    private function sendPutRequestWithCustomToken(
        array $requestPayload,
        string $accessToken
    ): TestResponse {
        return $this
            ->withHeaders([
                'Authorization' => "Bearer $accessToken",
                'X-Request-Id' => static::TRACE_ID,
            ])
            ->putJson('/api/institution-users', $requestPayload);
    }

    /**
     * @return array<array<array>>
     */
    public static function provideInvalidRequestPayloads(): array
    {
        return collect([
            ['email' => null],
            ['email' => ''],
            ['email' => 'not-email'],
            ['phone' => null],
            ['phone' => ''],
            ['phone' => '-1'],
            ['phone' => 'abc'],
            ['phone' => '5123456'],
            ['phone' => '5123 456'],
            ['phone' => '51234567'],
            ['phone' => '5123 4567'],
            ['phone' => '37251234567'],
            ['phone' => '372 5123 4567'],
            ['phone' => '372 5123 456'],
            ['phone' => '0037251234567'],
            ['phone' => '003725123456'],
            ['phone' => '00 372 5123 4567'],
            ['phone' => '00 372 5123 456'],
            ['phone' => '123'],
            ['phone' => '1234567'],
            ['phone' => '1234 567'],
            ['phone' => '12345678'],
            ['phone' => '1234 5678'],
            ['phone' => '+37201234567'],
            ['phone' => '+372 0123 4567'],
            ['phone' => '+37212345678'],
            ['phone' => '+372 1234 5678'],
            ['phone' => '+37223456789'],
            ['phone' => '+372 2345 6789'],
            ['phone' => '+37289012345'],
            ['phone' => '+372 8901 2345'],
            ['phone' => '+37290123456'],
            ['phone' => '+372 9012 3456'],
            ['phone' => '+372567890'],
            ['phone' => '+372 5678 90'],
            ['phone' => '+372 5678 901'],
            ['phone' => '+372 5678 9012'],
            ['phone' => '+372567890123'],
            ['phone' => '372 5678 9012 3'],
            ['phone' => '+372 5 6 7 8 9 0'],
            ['phone' => '+3 7 2 5 6 7 8 9 0'],
            ['phone' => '+ 372 567890'],
            ['phone' => ' +372 567890'],
            ['user' => null],
            ['user' => ''],
            ['user' => []],
            ['user.forename' => ''],
            ['user.forename' => null],
            ['user.surname' => ''],
            ['user.surname' => null],
        ])
            ->mapWithKeys(fn ($payload) => [json_encode($payload) => $payload]) // for test reports - otherwise only param index is reported
            ->map(Arr::undot(...))
            ->map(fn ($payload) => [$payload])
            ->toArray();
    }

    /**
     * @return array<array<array>>
     */
    public static function provideValidPhoneNumbers(): array
    {
        return collect([
            '+37234567890',
            '+372 34567890',
            '+37245678901',
            '+372 45678901',
            '+37256789012',
            '+372 56789012',
            '+37267890123',
            '+372 67890123',
            '+37278901234',
            '+372 78901234',
            '+3723456789',
            '+372 3456789',
            '+3724567890',
            '+372 4567890',
            '+3725678901',
            '+372 5678901',
            '+3726789012',
            '+372 6789012',
            '+3727890123',
            '+372 7890123',
        ])
            ->mapWithKeys(fn ($phone) => [$phone => $phone]) // for test reports - otherwise only param index is reported
            ->map(fn ($phone) => [$phone])
            ->toArray();
    }

    /**
     * @param  Closure(array): void  $assertOnEventParameters
     */
    private function assertMessageRepresentsInstitutionUserModification(array $actualMessageBody, InstitutionUser $institutionUser, array $actingUserBeforeModification, Closure $assertOnEventParameters): void
    {
        $expectedMessageBodySubset = [
            'event_type' => AuditLogEventType::ModifyObject->value,
            'happened_at' => Date::getTestNow()->toISOString(),
            'trace_id' => static::TRACE_ID,
            'failure_type' => null,
            'context_institution_id' => $actingUserBeforeModification['institution_id'],
            'context_department_id' => data_get($actingUserBeforeModification, 'department_id'),
            'acting_institution_user_id' => $actingUserBeforeModification['id'],
            'acting_user_pic' => $actingUserBeforeModification['user']['personal_identification_code'],
            'acting_user_forename' => $actingUserBeforeModification['user']['forename'],
            'acting_user_surname' => $actingUserBeforeModification['user']['surname'],
        ];

        Assertions::assertArrayHasSubsetIgnoringOrder(
            $expectedMessageBodySubset,
            collect($actualMessageBody)->intersectByKeys($expectedMessageBodySubset)->all(),
        );

        $eventParameters = data_get($actualMessageBody, 'event_parameters');
        $this->assertIsArray($eventParameters);

        $expectedEventParametersSubset = [
            'object_type' => AuditLogEventObjectType::InstitutionUser->value,
            'object_identity_subset' => $institutionUser->getIdentitySubset(),
        ];
        Assertions::assertArraysEqualIgnoringOrder(
            $expectedEventParametersSubset,
            collect($eventParameters)->intersectByKeys($expectedEventParametersSubset)->all(),
        );

        $assertOnEventParameters($eventParameters);
    }
}
