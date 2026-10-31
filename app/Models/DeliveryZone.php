<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'name', 'base_fee', 'free_above', 'price_per_kg'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
