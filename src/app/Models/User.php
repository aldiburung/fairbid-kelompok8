<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['email', 'password_hash', 'no_hp', 'tipe_akun', 'peran', 'status_verifikasi', 'status_akun', 'created_at'])]
#[Hidden(['password_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'user';

    protected $primaryKey = 'user_id';

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function personalProfile(): HasOne
    {
        return $this->hasOne(PersonalProfile::class, 'user_id', 'user_id');
    }

    public function businessProfile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class, 'user_id', 'user_id');
    }

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class, 'seller_id', 'user_id');
    }

    public function auctionDeposits(): HasMany
    {
        return $this->hasMany(AuctionDeposit::class, 'user_id', 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'user_id', 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }
}
