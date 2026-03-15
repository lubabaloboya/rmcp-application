<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_type',
        'first_name',
        'last_name',
        'id_number',
        'passport_number',
        'email',
        'phone',
        'address',
        'source_of_wealth',
        'source_of_funds',
        'annual_income_band',
        'net_worth_band',
        'investment_objective',
        'wealth_profile_status',
        'risk_level',
    ];

    protected $casts = [
        'wealth_profile_status' => 'string',
    ];

    public function riskAssessment()
    {
        return $this->hasOne(RiskAssessment::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function screeningChecks()
    {
        return $this->hasMany(ScreeningCheck::class);
    }
}
