<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuctionDeposit extends Model
{
    protected $table = 'auction_deposit';

    protected $primaryKey = 'deposit_id';

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'auction_id',
        'user_id',
        'nominal_ujl',
        'status',
        'terms_accepted_at',
        'paid_at',
        'settled_at',
    ];

    protected $casts = [
        'nominal_ujl' => 'decimal:2',
        'terms_accepted_at' => 'datetime',
        'paid_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class, 'auction_id', 'auction_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'deposit_id', 'deposit_id');
    }
}
