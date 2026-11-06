<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Auction extends Model
{
    protected $table = 'auction';

    protected $primaryKey = 'auction_id';

    public const UPDATED_AT = null;

    protected $fillable = [
        'seller_id',
        'category_id',
        'judul',
        'deskripsi',
        'model_transaksi',
        'harga_awal',
        'harga_limit',
        'kelipatan_bid',
        'nilai_ujl',
        'start_time',
        'end_time',
        'anti_sniping_window_menit',
        'anti_sniping_extend_menit',
        'status',
        'winning_bid_id',
        'created_at',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'harga_awal' => 'decimal:2',
        'harga_limit' => 'decimal:2',
        'kelipatan_bid' => 'decimal:2',
        'nilai_ujl' => 'decimal:2',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function auctionMedia(): HasMany
    {
        return $this->hasMany(AuctionMedia::class, 'auction_id', 'auction_id');
    }
}
