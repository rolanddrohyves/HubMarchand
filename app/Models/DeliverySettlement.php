<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverySettlement extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'courier_id', 'amount', 'settled_at', 'note'];

    protected function casts(): array
    {
        return ['settled_at' => 'date'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }
}
