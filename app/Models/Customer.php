<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = ['store_id', 'name', 'phone', 'email', 'tax_id', 'address', 'segment', 'payment_terms', 'deposit_percent'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function creditPayments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(CreditReminder::class);
    }

    public function b2bDocuments(): HasMany
    {
        return $this->hasMany(B2bDocument::class);
    }
}
