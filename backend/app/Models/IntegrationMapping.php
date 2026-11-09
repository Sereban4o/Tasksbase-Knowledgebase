<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationMapping extends Model
{
    protected $fillable = [
        'integration', 'entity_type', 'entity_id', 'external_id', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
