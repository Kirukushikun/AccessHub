<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A row in the append-only audit log. Written via App\Support\Audit; never
 * updated or deleted.
 */
class AuditEntry extends Model
{
    protected $table = 'audit_log';

    public $timestamps = false;

    protected $fillable = [
        'actor_type', 'actor_id', 'actor_label', 'action',
        'subject_type', 'subject_id', 'summary', 'meta', 'ip', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** Top-level action group, e.g. "person" from "person.role_changed". */
    public function group(): string
    {
        return explode('.', $this->action)[0];
    }
}
