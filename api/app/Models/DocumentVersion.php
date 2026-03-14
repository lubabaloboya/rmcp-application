<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'client_id',
        'document_type_id',
        'version_no',
        'action',
        'file_path',
        'file_hash',
        'replaced_document_id',
        'uploaded_by',
        'immutable_payload',
        'created_at',
    ];

    protected $casts = [
        'immutable_payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
