<?php

declare(strict_types=1);

namespace Modules\Catalog\app\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * This is the payoff for Phase 0's tenancy scaffolding: adding
 * BelongsToTenant here is what makes `Product::query()` automatically
 * filter to the current tenant (via TenantScope) and auto-fill
 * `store_id` on create — no manual `where('store_id', ...)` anywhere
 * in Catalog's controllers/services.
 *
 * Relies on TenantContext being populated, which on merchant-dashboard
 * routes now comes from BindTenantFromRouteStore (added this phase to
 * close a gap Phase 2 left open).
 */
final class Product extends Model
{
    use BelongsToTenant, HasFactory, HasUlids, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'store_id',
        'title',
        'slug',
        'description',
        'status',
        'base_price_cents',
        'currency',
        'seo',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'seo' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_product')->withPivot('position');
    }

    public function isPublishable(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->variants()->exists();
    }
}