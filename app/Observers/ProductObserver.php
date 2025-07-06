<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     *
     * @param Product $product
     * @return void
     */
    public function created(Product $product): void
    {
        $this->clearProductsCache();
        Log::info('Product created, cache cleared', ['product_id' => $product->id]);
    }

    /**
     * Handle the Product "updated" event.
     *
     * @param Product $product
     * @return void
     */
    public function updated(Product $product): void
    {
        $this->clearProductsCache();
        Log::info('Product updated, cache cleared', ['product_id' => $product->id]);
    }

    /**
     * Handle the Product "deleted" event.
     *
     * @param Product $product
     * @return void
     */
    public function deleted(Product $product): void
    {
        $this->clearProductsCache();
        Log::info('Product deleted, cache cleared', ['product_id' => $product->id]);
    }

    /**
     * Handle the Product "restored" event.
     *
     * @param Product $product
     * @return void
     */
    public function restored(Product $product): void
    {
        $this->clearProductsCache();
        Log::info('Product restored, cache cleared', ['product_id' => $product->id]);
    }

    /**
     * Handle the Product "force deleted" event.
     *
     * @param Product $product
     * @return void
     */
    public function forceDeleted(Product $product): void
    {
        $this->clearProductsCache();
        Log::info('Product force deleted, cache cleared', ['product_id' => $product->id]);
    }

    /**
     * Clear all products and search related cache.
     *
     * @return void
     */
    private function clearProductsCache(): void
    {
        CacheService::clearCacheByTags(['products', 'search']);
    }
}
