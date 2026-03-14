<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RmcpCase extends Model
{
    use HasFactory;

    protected $table = 'rmcp_cases';

    protected $fillable = [
        'client_id',
        'case_number',
        'title',
        'description',
        'stage',
        'status',
        'maker_id',
        'checker_id',
        'submitted_at',
        'approved_at',
        'closed_at',
        'sla_due_at',
        'escalated_at',
        'review_notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function maker()
    {
        return $this->belongsTo(User::class, 'maker_id');
    }

    public function checker()
    {
        return $this->belongsTo(User::class, 'checker_id');
    }
}
