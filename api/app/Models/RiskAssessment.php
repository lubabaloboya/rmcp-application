<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'pep_status',
        'country_risk',
        'industry_risk',
        'sanctions_check',
        'risk_score',
        'risk_level',
        'explanation_json',
        'trigger_reason',
        'last_screened_at',
        'assessed_by',
    ];

    protected $casts = [
        'pep_status' => 'boolean',
        'country_risk' => 'boolean',
        'industry_risk' => 'boolean',
        'sanctions_check' => 'boolean',
        'risk_score' => 'integer',
        'explanation_json' => 'array',
        'last_screened_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
