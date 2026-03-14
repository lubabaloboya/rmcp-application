<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeneficialOwner extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'id_number', 'ownership_percentage'];
}
