<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreeningCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'check_type',
        'provider',
        'status',
        'matched',
        'score',
        'metadata',
        'monitoring_cycle',
        'checked_at',
    ];

    protected $casts = [
        'matched' => 'boolean',
        'score' => 'integer',
        'metadata' => 'array',
        'checked_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
