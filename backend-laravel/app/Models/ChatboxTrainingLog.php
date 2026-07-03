<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatboxTrainingLog extends Model
{
    protected $table = 'chatbox_training_logs';

    protected $fillable = [
        'user_id',
        'major_id',
        'role',
        'message',
        'normalized_message',
        'analysis',
        'source',
        'reply',
        'products',
        'products_count',
        'needs_training',
        'reviewed',
        'reviewed_at',
        'admin_note',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'analysis' => 'array',
            'products' => 'array',
            'products_count' => 'integer',
            'needs_training' => 'boolean',
            'reviewed' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class, 'major_id', 'major_id');
    }
}
