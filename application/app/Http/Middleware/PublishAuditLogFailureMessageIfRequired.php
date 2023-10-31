<?php

namespace App\Http\Middleware;

use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\InstitutionUserController;
use App\Http\Controllers\InstitutionUserImportController;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Role;
use AuditLogClient\Enums\AuditLogEventFailureType;
use AuditLogClient\Enums\AuditLogEventObjectType;
use AuditLogClient\Enums\AuditLogEventType;
use AuditLogClient\Services\AuditLogMessageBuilder;
use AuditLogClient\Services\AuditLogPublisher;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PublishAuditLogFailureMessageIfRequired
{
    public function __construct(protected AuditLogPublisher $publisher)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(\Illuminate\Http\Request): Response  $next
     *
     * @throws ValidationException
     * @throws Throwable
     */
    public function handle(\Illuminate\Http\Request $request, Closure $next): Response
    {
        $response = $next($request);

        $eventTypeAndParameters = static::resolveEventTypeAndParameters();
        $failureType = match ($response->getStatusCode()) {
            Response::HTTP_UNPROCESSABLE_ENTITY,
            Response::HTTP_BAD_REQUEST => AuditLogEventFailureType::UNPROCESSABLE_ENTITY,
            Response::HTTP_FORBIDDEN => AuditLogEventFailureType::FORBIDDEN,
            default => null
        };

        if (filled($failureType) && filled($eventTypeAndParameters)) {
            [$eventType, $eventParameters] = $eventTypeAndParameters;
            $auditLogMessage = AuditLogMessageBuilder::makeUsingJWT($failureType)->toMessageEvent($eventType, $eventParameters);
            $this->publisher->publish($auditLogMessage);
        }

        return $response;
    }

    /**
     * @return null|array{ AuditLogEventType, ?array }
     */
    private static function resolveEventTypeAndParameters(): ?array
    {
        $currentAction = explode('@', Route::currentRouteAction());

        return match ($currentAction) {
            [RoleController::class, 'store'] => [AuditLogEventType::CreateObject, [
                'object_type' => AuditLogEventObjectType::Role->value,
                'input' => Request::input(),
            ]],
            [RoleController::class, 'update'] => [AuditLogEventType::ModifyObject, [
                'object_type' => AuditLogEventObjectType::Role->value,
                'input' => Request::input(),
                'object_identity_subset' => Role::find(Route::current()->parameter('role_id'))?->getIdentitySubset(),
            ]],
            [RoleController::class, 'destroy'] => [AuditLogEventType::RemoveObject, [
                'object_type' => AuditLogEventObjectType::Role->value,
                'object_identity_subset' => Role::find(Route::current()->parameter('role_id'))?->getIdentitySubset(),
            ]],
            [InstitutionController::class, 'update'] => [AuditLogEventType::ModifyObject, [
                'object_type' => AuditLogEventObjectType::Institution->value,
                'input' => Request::input(),
                'object_identity_subset' => Institution::find(Route::current()->parameter('institution_id'))?->getIdentitySubset(),
            ]],
            [InstitutionUserImportController::class, 'importCsv'] => [AuditLogEventType::CreateObject, [
                'object_type' => AuditLogEventObjectType::InstitutionUser->value,
                'input' => collect(Request::allFiles())->map(fn (UploadedFile $file) => $file->getContent())->all(),
            ]],
            [InstitutionUserController::class, 'exportCsv'] => [AuditLogEventType::ExportInstitutionUsers, null],
            [InstitutionUserController::class, 'updateCurrentInstitutionUser'] => [AuditLogEventType::ModifyObject, [
                'object_type' => AuditLogEventObjectType::InstitutionUser->value,
                'input' => Request::input(),
                'object_identity_subset' => InstitutionUser::find(Auth::user()?->institutionUserId)?->getIdentitySubset(),
            ]],
            [InstitutionUserController::class, 'update'],
            [InstitutionUserController::class, 'activate'],
            [InstitutionUserController::class, 'archive'],
            [InstitutionUserController::class, 'deactivate'], => [AuditLogEventType::ModifyObject, [
                'object_type' => AuditLogEventObjectType::InstitutionUser->value,
                'input' => Request::input(),
                'object_identity_subset' => InstitutionUser::find(Route::current()->parameter('institution_user_id'))?->getIdentitySubset(),
            ]],
            default => null,
        };
    }
}
