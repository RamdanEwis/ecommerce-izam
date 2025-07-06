# Laravel Scout Commands Guide

## Basic Scout Commands

### Import/Re-index Models
```bash
# Import all products to search index
php artisan scout:import "App\Models\Product"

# Import specific model with custom batch size
php artisan scout:import "App\Models\Product" --chunk=100
```

### Clear Search Index
```bash
# Clear all products from search index
php artisan scout:flush "App\Models\Product"

# Clear all indexes
php artisan scout:flush-all
```

### Check Scout Status
```bash
# Check if Scout is working properly
php artisan scout:status

# Check specific model status
php artisan scout:status "App\Models\Product"
```

## Search API Examples

### Basic Search
```http
GET /api/products?search=laptop
```

### Search with Filters
```http
GET /api/products?search=gaming laptop&min_price=1000&max_price=2000
```

### Non-search Filters (uses Eloquent)
```http
GET /api/products?name=Samsung&price_range=500-1500
GET /api/products?in_stock=true&sort_by=price&sort_direction=asc
```

## Development Tips

1. **Re-index after model changes**: Run `scout:import` after modifying `toSearchableArray()`
2. **Clear cache**: Scout results are cached, clear cache after re-indexing
3. **Database driver**: We're using database driver for simplicity (no external services needed)
4. **Performance**: For production, consider using Meilisearch or Algolia

## Configuration

Scout configuration is in `config/scout.php`:
- Driver: `database` (can be changed to algolia, meilisearch, etc.)
- Queue: `false` (set to true for background indexing)
- Index prefix: Can be set for multi-tenant applications 
