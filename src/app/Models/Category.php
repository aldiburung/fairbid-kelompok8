<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'category';

    protected $primaryKey = 'category_id';

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'nama_kategori',
        'izinkan_perorangan',
    ];

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class, 'category_id', 'category_id');
    }
}
