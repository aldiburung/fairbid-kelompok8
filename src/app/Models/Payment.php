<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payment';

    protected $primaryKey = 'payment_id';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'deposit_id',
        'transaction_id',
        'jenis',
        'amount',
        'gateway_ref',
        'idempotency_key',
        'status',
        'webhook_at',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'webhook_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(AuctionDeposit::class, 'deposit_id', 'deposit_id');
    }
}
