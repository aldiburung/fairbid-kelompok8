<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'jenis_dokumen',
    'file_url',
    'status',
    'alasan_penolakan',
    'reviewed_by',
    'submitted_at',
    'reviewed_at',
])]
class Verification extends Model
{
    protected $table = 'verification';

    protected $primaryKey = 'verification_id';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
