<?php

namespace App\Models;

use Database\Factories\SalesOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    /** @use HasFactory<SalesOrderFactory> */
    use HasFactory;

    protected $fillable = ['store_id', 'customer_id', 'courier_id', 'reference', 'source', 'customer_name', 'status', 'payment_method', 'fulfillment_status', 'delivery_person', 'delivery_zone', 'delivery_address', 'delivery_note', 'total_amount', 'delivery_fee', 'weight_kg', 'cost_amount', 'paid_amount', 'cod_collected', 'due_at', 'ordered_at'];

    protected function casts(): array
    {
        return ['ordered_at' => 'datetime', 'due_at' => 'date', 'weight_kg' => 'decimal:2'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function creditPayments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function outstandingAmount(): int
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }
}
