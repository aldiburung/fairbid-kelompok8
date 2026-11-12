<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notification';

    protected $primaryKey = 'notification_id';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'auction_id',
        'tipe',
        'kanal',
        'isi',
        'is_read',
        'status_kirim',
        'created_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class, 'auction_id', 'auction_id');
    }
}
