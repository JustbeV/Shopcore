<?php

namespace Modules\CRM\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Product;

class Review extends Model
{
    use BelongsToTenant, HasFactory, HasUlids;

    protected $fillable = ['product_id', 'customer_id', 'order_id', 'rating', 'body', 'status'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isVerifiedPurchase(): bool
    {
        return $this->order_id !== null;
    }
}