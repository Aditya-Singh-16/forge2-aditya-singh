<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'priority',
        'response_hours',
        'resolution_hours',
    ];
}
