<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $table = 'transaction';

    protected $primaryKey = 'transaction_id';

    public $timestamps = false;

    protected $fillable = [
        'auction_id',
        'seller_id',
        'buyer_id',
        'harga_akhir',
        'fee_persen',
        'fee_amount',
        'net_seller',
        'status',
        'batas_pelunasan',
        'batas_konfirmasi',
        'buyer_confirmed_at',
        'seller_confirmed_at',
        'released_at',
    ];

    protected $casts = [
        'harga_akhir' => 'decimal:2',
        'fee_persen' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'net_seller' => 'decimal:2',
        'batas_pelunasan' => 'datetime',
        'batas_konfirmasi' => 'datetime',
        'buyer_confirmed_at' => 'datetime',
        'seller_confirmed_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class, 'auction_id', 'auction_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id', 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'transaction_id', 'transaction_id');
    }
}
