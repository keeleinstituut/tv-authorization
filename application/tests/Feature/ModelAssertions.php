<?php

namespace Tests\Feature;

use App\Enums\InstitutionUserStatus;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

trait ModelAssertions
{
    public function assertInstitutionUserIsIncludedAndActive(string $id): void
    {
        $institutionUser = InstitutionUser::find($id);
        $this->assertNotNull($institutionUser);
        $this->assertTrue($institutionUser->exists());
        $this->assertFalse($institutionUser->isDeactivated());
        $this->assertEquals(InstitutionUserStatus::Active, $institutionUser->getStatus());
    }

    public function assertInstitutionUserRolePivotsAreSoftDeleted(Collection $pivots): void
    {
        $pivots->each(
            fn ($pivot) => $this->assertSoftDeleted(
                InstitutionUserRole::class,
                ['id' => $pivot->id]
            )
        );
    }

    public function assertInstitutionUserRolePivotsExist(Collection $pivots): void
    {
        $pivots
            ->each($this->assertNotNull(...))
            ->map(fn (InstitutionUserRole $pivot) => $pivot->exists())
            ->each($this->assertTrue(...));
    }

    /**
     * @param $action Closure(): TestResponse
     * @param $convertModelToArray Closure(Model): array
     * @param $modelsWithExpectedChanges iterable<array{Model, array}>
     * @param  Model|iterable<Model>  $expectedResponse
     *
     * @noinspection PhpDocSignatureInspection
     */
    public function assertModelsInExpectedStateAfterActionAndCheckResponseData(Closure $action,
        Closure $convertModelToArray,
        iterable $modelsWithExpectedChanges,
        Model|iterable $expectedResponse): void
    {
        $testResponse = $this->assertModelsInExpectedStateAfterAction(
            $action,
            $convertModelToArray,
            $modelsWithExpectedChanges,
            Response::HTTP_OK
        );

        $expectedResponseData = is_iterable($expectedResponse)
            ? collect($expectedResponse)
                ->map(fn (Model $model) => $model->refresh())
                ->map($convertModelToArray)
                ->all()
            : $convertModelToArray($expectedResponse->refresh());

        $testResponse->assertJsonFragment(['data' => $expectedResponseData]);
    }

    /**
     * @param $action Closure(): TestResponse
     * @param $convertModelToArray Closure(Model): array
     * @param $models iterable<Model>
     * @param $expectedStatus int
     */
    public function assertModelsWithoutChangeAfterAction(Closure $action,
        Closure $convertModelToArray,
        iterable $models,
        int $expectedStatus = 200): TestResponse
    {
        return self::assertModelsInExpectedStateAfterAction(
            $action,
            $convertModelToArray,
            collect($models)->map(fn ($model) => [$model, []]),
            $expectedStatus
        );
    }

    /**
     * @param $action Closure(): TestResponse
     * @param $convertModelToArray Closure(Model): array
     * @param $modelsWithExpectedChanges iterable<array{Model, array}>
     * @param $expectedStatus int
     */
    public function assertModelsInExpectedStateAfterAction(Closure $action,
        Closure $convertModelToArray,
        iterable $modelsWithExpectedChanges,
        int $expectedStatus = 200): TestResponse
    {
        $expectedStateAfterAction = collect($modelsWithExpectedChanges)
            ->mapSpread(fn (Model $model, array $expectedChange) => [
                ...$convertModelToArray($model->refresh()),
                ...$expectedChange,
            ])
            ->all();

        $response = $action();

        $actualStateAfterAction = collect($modelsWithExpectedChanges)
            ->mapSpread(fn (Model $model) => $convertModelToArray($model->refresh()))
            ->all();

        $this->assertEqualAsJsonIgnoringOrderRecursively($expectedStateAfterAction, $actualStateAfterAction);
        $response->assertStatus($expectedStatus);

        return $response;
    }
}
