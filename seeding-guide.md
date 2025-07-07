# Database Seeding Guide

## Overview
This project includes both quick testing seeders and large dataset seeders for performance testing and development.

## Available Seeders

### 1. Quick Test Data (Default)
- **10 users** with test data
- **50 products** with varied stock levels
- **Multiple orders** per user with realistic relationships

### 2. Large Dataset Seeders
- **500 users** (UsersSeeder)
- **10,000 products** (ProductsSeeder)
- **20 orders** (OrdersSeeder)

## Seeding Commands

### Run All Seeders (Quick Test Data)
```bash
php artisan db:seed
```

### Run Individual Large Dataset Seeders
```bash
# Create 500 users
php artisan db:seed --class=UsersSeeder

# Create 10,000 products (with Scout indexing)
php artisan db:seed --class=ProductsSeeder

# Create 20 orders (requires users and products)
php artisan db:seed --class=OrdersSeeder
```

### Run All Large Dataset Seeders
```bash
# Option 1: Edit DatabaseSeeder.php
# Uncomment the line: $this->seedLargeDataset();
# Comment out the line: $this->seedQuickTestData();
# Then run:
php artisan db:seed

# Option 2: Run individually in order
php artisan db:seed --class=UsersSeeder
php artisan db:seed --class=ProductsSeeder
php artisan db:seed --class=OrdersSeeder
```

## Database Reset and Seed
```bash
# Fresh migration and seeding
php artisan migrate:fresh --seed

# Fresh migration with large dataset
php artisan migrate:fresh
php artisan db:seed --class=UsersSeeder
php artisan db:seed --class=ProductsSeeder
php artisan db:seed --class=OrdersSeeder
```

## Performance Notes

### ProductsSeeder Features
- **Chunked processing** (500 products per batch)
- **Bulk inserts** for better performance
- **Scout indexing disabled** during seeding
- **Automatic re-indexing** after completion
- **Progress indicators** for long-running operations

### OrdersSeeder Features
- **Database transactions** for data integrity
- **Stock management** (reduces product stock)
- **Realistic order totals** calculation
- **Dependency checks** (requires users and products)
- **Summary statistics** after completion

### Expected Execution Times
- **UsersSeeder**: ~30-60 seconds (500 users)
- **ProductsSeeder**: ~2-5 minutes (10,000 products + indexing)
- **OrdersSeeder**: ~10-20 seconds (20 orders)

## Laravel Scout Integration

After seeding products, they are automatically indexed for search:

```bash
# Check search index status
php artisan scout:status

# Manual re-indexing (if needed)
php artisan scout:import "App\Models\Product"

# Clear search index
php artisan scout:flush "App\Models\Product"
```

## Testing the Seeders

### API Testing
```bash
# Test products endpoint
curl -X GET http://localhost:8000/api/products

# Test search functionality
curl -X GET "http://localhost:8000/api/products?search=laptop"

# Test pagination
curl -X GET "http://localhost:8000/api/products?page=2&per_page=20"
```

### Database Verification
```bash
# Check record counts
php artisan tinker
>>> App\Models\User::count()
>>> App\Models\Product::count()
>>> App\Models\Order::count()
>>> App\Models\OrderProduct::count()
```

## Troubleshooting

### Memory Issues
If you encounter memory issues with large datasets:
- Increase PHP memory limit: `php -d memory_limit=1G artisan db:seed`
- Run seeders individually instead of all at once

### Scout Indexing Issues
- Make sure Scout is properly configured in `config/scout.php`
- Check database driver configuration for Scout
- Verify search functionality after seeding

### Stock Management
- OrdersSeeder reduces product stock when creating orders
- Run ProductsSeeder again if you need to restore stock levels
- Check product stock levels: `Product::where('stock', '>', 0)->count()`

## Configuration

### Customizing Seeder Quantities
Edit the seeder files to change quantities:
- `UsersSeeder.php`: Change `$totalUsers = 500`
- `ProductsSeeder.php`: Change `$totalProducts = 10000`
- `OrdersSeeder.php`: Change the loop from `1 <= 20`

### Customizing Chunk Sizes
For performance tuning:
- `UsersSeeder.php`: Change `$chunkSize = 100`
- `ProductsSeeder.php`: Change `$chunkSize = 500` 
