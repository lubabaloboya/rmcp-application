<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDirector extends Model
{
    use HasFactory;

    protected $table = 'company_directors';

    protected $fillable = ['company_id', 'first_name', 'last_name', 'id_number', 'position'];
}
