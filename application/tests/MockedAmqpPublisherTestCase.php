<?php

namespace Tests;

use App\Models\InstitutionUser;
use AuditLogClient\Enums\AuditLogEventFailureType;
use AuditLogClient\Enums\AuditLogEventType;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Mockery\MockInterface;
use SyncTools\AmqpPublisher;

abstract class MockedAmqpPublisherTestCase extends TestCase
{
    const TRACE_ID = '123-ABC';

    protected AmqpPublisher&MockInterface $amqpPublisher;

    protected Dispatcher $modelEventDispatcher;

    public function setUp(): void
    {
        parent::setup();
        $this->amqpPublisher = $this->spy(AmqpPublisher::class);
        $this->modelEventDispatcher = Model::getEventDispatcher();
        Model::unsetEventDispatcher();
    }

    protected function assertSuccessfulAuditLogMessageWasPublished(
        AuditLogEventType $eventType,
        InstitutionUser $actingUser,
        ?string $traceId,
        ?array $eventParameters,
        null|string|CarbonInterface $happenedAt = null,
    ): void {
        $this->assertAuditLogMessageWasPublishedWithParams(
            $eventType,
            $actingUser->institution_id,
            $actingUser->id,
            $actingUser->user->personal_identification_code,
            $actingUser->user->forename,
            $actingUser->user->surname,
            null,
            $traceId,
            $eventParameters,
            $happenedAt
        );
    }

    protected function assertAuditLogMessageWasPublished(
        AuditLogEventType $eventType,
        InstitutionUser $actingUser,
        ?AuditLogEventFailureType $failureType,
        ?string $traceId,
        ?array $eventParameters,
        null|string|CarbonInterface $happenedAt = null,
    ): void {
        $this->assertAuditLogMessageWasPublishedWithParams(
            $eventType,
            $actingUser->institution_id,
            $actingUser->id,
            $actingUser->user->personal_identification_code,
            $actingUser->user->forename,
            $actingUser->user->surname,
            $failureType,
            $traceId,
            $eventParameters,
            $happenedAt
        );
    }

    protected function assertAuditLogMessageWasPublishedWithParams(
        AuditLogEventType $eventType,
        string $institutionId,
        string $institutionUserId,
        string $personalIdentificationCode,
        string $forename,
        string $surname,
        ?AuditLogEventFailureType $failureType,
        ?string $traceId,
        ?array $eventParameters,
        null|string|CarbonInterface $happenedAt = null,
    ): void {
        $this->amqpPublisher->shouldHaveReceived('publish')->withArgs(
            function (mixed $actualMessage, string $exchange, string $routingKey, array $headers) use ($eventParameters, $personalIdentificationCode, $eventType, $failureType, $traceId, $surname, $forename, $institutionUserId, $institutionId, $happenedAt): true {
                $this->assertIsArray($headers);
                $this->assertIsString(data_get($headers, 'jwt'));

                $this->assertIsArray($actualMessage);

                $expectedMessageSubset = [
                    'trace_id' => $traceId,
                    'event_type' => $eventType->value,
                    'failure_type' => $failureType?->value,
                    'context_institution_id' => $institutionId,
                    'context_department_id' => null,
                    'acting_institution_user_id' => $institutionUserId,
                    'acting_user_pic' => $personalIdentificationCode,
                    'acting_user_forename' => $forename,
                    'acting_user_surname' => $surname,
                    'event_parameters' => $eventParameters,
                ];
                $this->assertArraysEqualIgnoringOrder(
                    [...array_keys($expectedMessageSubset), 'happened_at'],
                    array_keys($actualMessage)
                );

                if (! empty($happenedAt)) {
                    $this->assertEquals(
                        is_string($happenedAt) ? $happenedAt : $happenedAt->toISOString(),
                        data_get($actualMessage, 'happened_at')
                    );
                }

                $this->assertArrayHasSubsetIgnoringOrder($expectedMessageSubset, $actualMessage);

                return true;
            }
        );
    }
}
