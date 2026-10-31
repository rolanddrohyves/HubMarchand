<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2bDocumentItem extends Model
{
    use HasFactory;

    protected $fillable = ['b2b_document_id', 'product_id', 'description', 'quantity', 'unit_price', 'discount_percent', 'line_total'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(B2bDocument::class, 'b2b_document_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
