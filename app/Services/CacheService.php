<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Generate cache key for products
     *
     * @param array $params
     * @return string
     */
    public static function generateProductsCacheKey(array $params): string
    {
        return config('cache.custom.products.prefix') . md5(serialize($params));
    }

    /**
     * Generate cache key for orders
     *
     * @param int $userId
     * @param array $params
     * @return string
     */
    public static function generateOrdersCacheKey(int $userId, array $params = []): string
    {
        return config('cache.custom.orders.prefix') . $userId . ':' . md5(serialize($params));
    }

    /**
     * Clear cache by tags
     *
     * @param array $tags
     * @return void
     */
    public static function clearCacheByTags(array $tags): void
    {
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\TaggedCache) {
                Cache::tags($tags)->flush();
            } else {
                // Fallback for stores that don't support tags
                self::clearCacheByPattern($tags);
            }
        } catch (\Exception $e) {
            Log::error('Failed to clear cache by tags: ' . $e->getMessage(), [
                'tags' => $tags
            ]);
        }
    }

    /**
     * Clear cache by pattern (for Redis)
     *
     * @param array $patterns
     * @return void
     */
    public static function clearCacheByPattern(array $patterns): void
    {
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getRedis();

                foreach ($patterns as $pattern) {
                    // Convert tags to patterns
                    if (in_array($pattern, ['products', 'search', 'orders'])) {
                        $pattern = config("cache.custom.{$pattern}.prefix") . '*';
                    }

                    $keys = $redis->keys($pattern);
                    if (!empty($keys)) {
                        $redis->del($keys);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to clear cache by pattern: ' . $e->getMessage(), [
                'patterns' => $patterns
            ]);
        }
    }

    /**
     * Remember cache with tags
     *
     * @param string $key
     * @param int $ttl
     * @param callable $callback
     * @param array $tags
     * @return mixed
     */
    public static function remember(string $key, int $ttl, callable $callback, array $tags = [])
    {
        try {
            if (!empty($tags) && Cache::getStore() instanceof \Illuminate\Cache\TaggedCache) {
                return Cache::tags($tags)->remember($key, $ttl, $callback);
            }

            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error('Failed to cache data: ' . $e->getMessage(), [
                'key' => $key,
                'ttl' => $ttl,
                'tags' => $tags
            ]);

            // Return data without caching
            return $callback();
        }
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public static function getCacheStats(): array
    {
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getRedis();

                return [
                    'products_keys' => count($redis->keys(config('cache.custom.products.prefix') . '*')),
                    'search_keys' => count($redis->keys(config('cache.custom.search.prefix') . '*')),
                    'orders_keys' => count($redis->keys(config('cache.custom.orders.prefix') . '*')),
                    'total_keys' => count($redis->keys('*')),
                ];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to get cache stats: ' . $e->getMessage());
            return [];
        }
    }
}
