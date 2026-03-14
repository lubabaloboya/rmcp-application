<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = ['document_name', 'category'];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
