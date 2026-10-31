<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class B2bDocument extends Model
{
    use HasFactory;

    protected $table = 'b2b_documents';

    protected $fillable = ['store_id', 'customer_id', 'source_document_id', 'number', 'type', 'status', 'issued_at', 'valid_until', 'due_at', 'subtotal', 'discount_amount', 'total_amount', 'paid_amount', 'discount_percent', 'notes'];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'valid_until' => 'date', 'due_at' => 'date'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_document_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(B2bDocumentItem::class);
    }

    public function outstandingAmount(): int
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }
}
