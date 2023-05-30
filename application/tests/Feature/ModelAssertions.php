<?php

namespace Tests\Feature;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

trait ModelAssertions
{
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
            ->toArray();

        $response = $action();

        $actualStateAfterAction = collect($modelsWithExpectedChanges)
            ->mapSpread(fn (Model $model) => $convertModelToArray($model->refresh()))
            ->toArray();

        $this->assertEquals($expectedStateAfterAction, $actualStateAfterAction);
        $response->assertStatus($expectedStatus);

        return $response;
    }
}
