<?php

namespace App\Models;

use Database\Factories\UserToImportFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\UserToImport
 *
 * @property string $id
 * @property string $institution_user_id
 * @property string|null $name
 * @property string|null $role
 * @property string|null $personal_identification_code
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $department
 * @property bool $is_vendor
 * @property int $file_row_idx
 * @property int $errors_count
 * @property array $errors
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static UserToImportFactory factory($count = null, $state = [])
 * @method static Builder|UserToImport newModelQuery()
 * @method static Builder|UserToImport newQuery()
 * @method static Builder|UserToImport query()
 * @method static Builder|UserToImport whereCreatedAt($value)
 * @method static Builder|UserToImport whereDepartment($value)
 * @method static Builder|UserToImport whereEmail($value)
 * @method static Builder|UserToImport whereErrors($value)
 * @method static Builder|UserToImport whereErrorsCount($value)
 * @method static Builder|UserToImport whereFileRowIdx($value)
 * @method static Builder|UserToImport whereId($value)
 * @method static Builder|UserToImport whereInstitutionUserId($value)
 * @method static Builder|UserToImport whereIsVendor($value)
 * @method static Builder|UserToImport whereName($value)
 * @method static Builder|UserToImport wherePersonalIdentificationCode($value)
 * @method static Builder|UserToImport wherePhone($value)
 * @method static Builder|UserToImport whereRole($value)
 * @method static Builder|UserToImport whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class UserToImport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'institution_user_id',
        'name',
        'role',
        'personal_identification_code',
        'email',
        'phone',
        'department',
        'is_vendor',
        'file_row_idx',
        'errors_count',
        'errors',
    ];

    protected $casts = [
        'errors' => 'array',
    ];

    protected $table = 'users_to_import';
}
