<?php

namespace App\Models;

use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Utms extends Model
{
    use SoftDeletes, Uuid;

    protected $fillable = [
        'client_id',
        'source',
        'medium',
        'campaign',
        'utm_template_id',
        'capture_date',
        'entity_id',
        'entity_name'
    ];
}
