<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionMedia extends Model
{
    protected $table = 'auction_media';

    protected $primaryKey = 'media_id';

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'auction_id',
        'tipe',
        'url',
        'urutan',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class, 'auction_id', 'auction_id');
    }
}
