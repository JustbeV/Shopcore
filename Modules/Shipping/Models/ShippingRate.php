<?php

namespace Modules\Shipping\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use BelongsToTenant, HasFactory, HasUlids;

    protected $fillable = ['name', 'country_code', 'price_cents', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}