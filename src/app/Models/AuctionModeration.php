<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionModeration extends Model
{
    protected $table = 'auction_moderation';

    protected $primaryKey = 'moderation_id';

    public const UPDATED_AT = null;

    protected $fillable = [
        'auction_id',
        'admin_id',
        'aksi',
        'alasan',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class, 'auction_id', 'auction_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id', 'user_id');
    }
}
