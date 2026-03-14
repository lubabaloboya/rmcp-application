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
        'risk_level',
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
