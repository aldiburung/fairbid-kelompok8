<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    protected $table = 'bid';

    protected $primaryKey = 'bid_id';

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'auction_id',
        'bidder_id',
        'deposit_id',
        'amount',
        'sequence_no',
        'server_timestamp',
        'status',
        'is_flagged',
        'extended_auction',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'server_timestamp' => 'datetime',
        'is_flagged' => 'boolean',
        'extended_auction' => 'boolean',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class, 'auction_id', 'auction_id');
    }

    public function bidder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bidder_id', 'user_id');
    }

    public function auctionDeposit(): BelongsTo
    {
        return $this->belongsTo(AuctionDeposit::class, 'deposit_id', 'deposit_id');
    }
}
