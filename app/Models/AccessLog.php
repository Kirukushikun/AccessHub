<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    protected $fillable = ['email', 'successful', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['successful' => 'boolean'];
    }
}
