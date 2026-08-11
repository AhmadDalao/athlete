<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class OrganizationSetting extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'key', 'value', 'group'];
}
