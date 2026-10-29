<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AdminActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'admin_activity_logs';

    protected $fillable = [
        'user_id',
        'admin_name',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'details',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'details'    => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Record an administrative audit log entry safely.
     */
    public static function record(
        string $action,
        ?string $description = null,
        mixed $entity = null,
        ?array $details = null
    ): self {
        $admin = Auth::guard('admin')->user() ?? Auth::user();

        // Sanitize details to never record passwords, auth tokens, or private secrets
        if ($details) {
            $details = self::sanitizeDetails($details);
        }

        return self::create([
            'user_id'     => $admin?->id,
            'admin_name'  => $admin?->name ?? 'System',
            'action'      => $action,
            'entity_type' => is_object($entity) ? get_class($entity) : (is_string($entity) ? $entity : null),
            'entity_id'   => is_object($entity) && isset($entity->id) ? $entity->id : (is_numeric($entity) ? (int)$entity : null),
            'description' => $description,
            'details'     => $details,
            'ip_address'  => request()->ip(),
            'created_at'  => now(),
        ]);
    }

    /**
     * Remove sensitive fields recursively from audit payload.
     */
    protected static function sanitizeDetails(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'secret', 'remember_token', 'api_key'];

        foreach ($data as $key => $val) {
            if (in_array(strtolower($key), $sensitiveKeys, true)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($val)) {
                $data[$key] = self::sanitizeDetails($val);
            }
        }

        return $data;
    }
}

