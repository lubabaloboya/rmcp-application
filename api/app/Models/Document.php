<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'company_id',
        'document_type_id',
        'file_path',
        'expiry_date',
        'uploaded_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class);
    }
}