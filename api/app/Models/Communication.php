<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Communication extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_subject',
        'email_body',
        'sender',
        'receiver',
        'linked_client_id',
        'linked_task_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'linked_client_id');
    }

    public function task()
    {
        return $this->belongsTo(TaskItem::class, 'linked_task_id');
    }
}
