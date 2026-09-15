<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Tenant-scoped user. Every user belongs to exactly one tenant.
 *
 * @property int $id
 * @property string $tenant_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property bool $is_active
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class User extends Authenticatable
{
    use BelongsToTenant;
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use MustVerifyEmail;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'password',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
