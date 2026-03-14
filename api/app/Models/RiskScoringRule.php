<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskScoringRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_key',
        'label',
        'weight',
        'enabled',
        'description',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'weight' => 'integer',
    ];
}
