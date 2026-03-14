<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_type',
        'document_type_id',
        'required',
    ];

    protected $casts = [
        'required' => 'boolean',
    ];

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }
}
