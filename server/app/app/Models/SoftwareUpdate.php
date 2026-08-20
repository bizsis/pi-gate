<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoftwareUpdate extends Model
{
    protected $fillable = [
        'version_code',
        'version_name',
        'apk_path',
        'sha256',
        'file_size',
        'mandatory',
        'active',
        'notes',
    ];

    protected $casts = [
        'mandatory' => 'boolean',
        'active' => 'boolean',
    ];
}
