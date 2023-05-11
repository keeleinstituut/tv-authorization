<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetInstitutionUserRequest;
use App\Http\Requests\UpdateInstitutionUserRequest;
use App\Http\Resources\InstitutionUserResource;
use App\Models\InstitutionUser;
use App\Policies\InstitutionUserPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class InstitutionUserController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function show(GetInstitutionUserRequest $request): InstitutionUserResource
    {
        $institutionUser = $this->getBaseQuery()->findOrFail($request->getInstitutionUserId());

        $this->authorize('view', $institutionUser);

        return new InstitutionUserResource($institutionUser);
    }

    /**
     * @throws AuthorizationException|Throwable
     */
    public function update(UpdateInstitutionUserRequest $request): InstitutionUserResource
    {
        return DB::transaction(function () use ($request) {
            $institutionUser = $this->getBaseQuery()->findOrFail($request->getInstitutionUserId());

            $this->authorize('update', $institutionUser);

            $institutionUser->fill($request->safe(['email', 'phone']));

            if ($request->has('user')) {
                $institutionUser->user->update($request->validated('user'));
            }
            if ($request->has('roles')) {
                $institutionUser->roles()->sync($request->validated('roles'));
            }
            if ($request->has('department_id')) {
                $institutionUser->department()->associate($request->validated('department_id'));
            }

            $institutionUser->save();
            // TODO: audit log

            return new InstitutionUserResource($institutionUser->refresh());
        });
    }

    /**
     * @throws AuthorizationException|CannotInsertRecord|Exception
     */
    public function exportCsv(): StreamedResponse
    {
        $this->authorize('export', InstitutionUser::class);

        $csvDocument = Writer::createFromString();

        $csvDocument->insertOne([
            'Isikukood', 'Nimi', 'Meiliaadress', 'Telefoninumber', 'Üksus', 'Roll',
        ]);
        $csvDocument->insertAll(
            $this->getBaseQuery()
                ->with(['user', 'department', 'roles'])
                ->get()
                ->map(fn (InstitutionUser $institutionUser) => [
                    $institutionUser->user->personal_identification_code,
                    "{$institutionUser->user->forename} {$institutionUser->user->surname}",
                    $institutionUser->email,
                    $institutionUser->phone,
                    $institutionUser->department?->name,
                    $institutionUser->roles->map->name->join(', '),
                ])
        );

        // TODO: audit log

        return response()->streamDownload(
            $csvDocument->output(...),
            'exported_users.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    public function getBaseQuery(): Builder
    {
        return InstitutionUser::getModel()->withGlobalScope('policy', InstitutionUserPolicy::scope());
    }
}
