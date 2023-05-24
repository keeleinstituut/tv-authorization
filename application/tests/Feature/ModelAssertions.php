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
     * @param $modelsWithExpectedChanges array<array{Model, array}>
     * @param $expectedResponseDataModel Model
     */
    public function assertModelsInExpectedStateAfterActionAndCheckResponseContent(Closure $action,
        Closure $convertModelToArray,
        array $modelsWithExpectedChanges,
        Model $expectedResponseDataModel): void
    {
        $this->assertModelsInExpectedStateAfterAction(
            $action,
            $convertModelToArray,
            $modelsWithExpectedChanges,
            Response::HTTP_OK
        )->assertJsonFragment([
            'data' => $convertModelToArray($expectedResponseDataModel->refresh()),
        ]);
    }

    /** @param $action Closure(): TestResponse
     * @param $convertModelToArray Closure(Model): array
     * @param $modelsWithExpectedChanges array<array{Model, array}>
     * @param $expectedStatus int
     */
    public function assertModelsInExpectedStateAfterAction(Closure $action,
        Closure $convertModelToArray,
        array $modelsWithExpectedChanges,
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
