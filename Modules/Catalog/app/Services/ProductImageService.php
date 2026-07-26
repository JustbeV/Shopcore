<?php

declare(strict_types=1);

namespace Modules\Catalog\app\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\app\Models\Product;
use Modules\Catalog\app\Models\ProductImage;

/**
 * Storage disk defaults to `public` for local/dev; production sets
 * FILESYSTEM_DISK=s3 (architecture §13: uploads live outside the web
 * root, served via signed URLs in production — the `public` disk here
 * is a local-dev convenience, not the production posture).
 *
 * Virus scanning (ClamAV queued job, per §13) is NOT implemented in
 * this task — noted, not silently skipped.
 */
final class ProductImageService
{
    public function upload(Product $product, UploadedFile $file, ?string $altText = null): ProductImage
    {
        $disk = config('filesystems.default', 'public');
        $path = $file->store("stores/{$product->store_id}/products/{$product->id}", $disk);

        return ProductImage::create([
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'disk' => $disk,
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'position' => $product->images()->max('position') + 1,
            'alt_text' => $altText,
        ]);
    }

    public function delete(ProductImage $image): void
    {
        Storage::disk($image->disk)->delete($image->path);
        $image->delete();
    }

    /**
     * @param  array<int, string>  $orderedImageIds
     */
    public function reorder(Product $product, array $orderedImageIds): void
    {
        foreach ($orderedImageIds as $position => $imageId) {
            $product->images()->where('id', $imageId)->update(['position' => $position]);
        }
    }
}