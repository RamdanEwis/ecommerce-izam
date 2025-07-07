# ResponseBuilder Guide

## Overview
The `ResponseBuilder` class provides a centralized way to handle all API responses with consistent formatting across your Laravel application.

## Features
- ✅ **Consistent Response Format**: All responses follow the same structure
- ✅ **Comprehensive Error Handling**: Built-in exception handling with debug info
- ✅ **HTTP Status Codes**: Proper status codes for different scenarios
- ✅ **Resource Support**: Works with Laravel API Resources
- ✅ **Pagination Support**: Built-in pagination response formatting
- ✅ **Timestamps**: Automatic timestamp inclusion
- ✅ **Debug Mode**: Development-friendly error details

## Basic Usage

### Success Responses
```php
// Basic success response
return ResponseBuilder::success($data, 'Data retrieved successfully');

// Success with custom status code
return ResponseBuilder::success($data, 'Custom message', 201);
```

### Error Responses
```php
// Basic error response
return ResponseBuilder::error('Something went wrong');

// Error with custom status code and details
return ResponseBuilder::error('Validation failed', 422, $validationErrors);

// Specific error types
return ResponseBuilder::notFound('User not found');
return ResponseBuilder::unauthorized('Access denied');
return ResponseBuilder::forbidden('Permission denied');
return ResponseBuilder::serverError('Internal server error');
```

### CRUD Operations
```php
// Create response
return ResponseBuilder::created($data, 'Resource created successfully');

// Update response
return ResponseBuilder::updated($data, 'Resource updated successfully');

// Delete response
return ResponseBuilder::deleted('Resource deleted successfully');

// No content response
return ResponseBuilder::noContent();
```

## Advanced Usage

### Pagination
```php
// Basic pagination
return ResponseBuilder::paginated($paginatedData, 'Data retrieved successfully');

// Pagination with resource transformation
return ResponseBuilder::paginated($paginatedData, 'Products retrieved', ProductResource::class);
```

### Laravel Resources
```php
// Single resource
return ResponseBuilder::resource(new UserResource($user), 'User retrieved successfully');

// Resource collection
return ResponseBuilder::collection(UserResource::collection($users), 'Users retrieved successfully');
```

### Exception Handling
```php
try {
    // Your logic here
    return ResponseBuilder::success($data);
} catch (\Exception $exception) {
    report($exception);
    return ResponseBuilder::exception($exception, 'Operation failed');
}
```

### Validation Errors
```php
// Validation error response
return ResponseBuilder::validationError($validator->errors(), 'Validation failed');

// Multiple errors
return ResponseBuilder::multipleErrors($errorArray, 'Multiple issues found');
```

### Custom Responses
```php
// Custom response structure
return ResponseBuilder::custom([
    'success' => true,
    'custom_field' => 'value',
    'data' => $data
], 200);
```

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Data retrieved successfully",
    "data": {
        // Your data here
    },
    "timestamp": "2023-12-07T10:30:00.000Z"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Something went wrong",
    "errors": {
        // Error details (optional)
    },
    "timestamp": "2023-12-07T10:30:00.000Z"
}
```

### Paginated Response
```json
{
    "success": true,
    "message": "Data retrieved successfully",
    "data": [
        // Array of items
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150,
        "from": 1,
        "to": 15,
        "has_more_pages": true
    },
    "timestamp": "2023-12-07T10:30:00.000Z"
}
```

## Available Methods

### Success Methods
- `success($data, $message, $statusCode)`
- `created($data, $message)`
- `updated($data, $message)`
- `deleted($message)`
- `noContent()`

### Error Methods
- `error($message, $statusCode, $errors)`
- `validationError($errors, $message)`
- `notFound($message)`
- `unauthorized($message)`
- `forbidden($message)`
- `serverError($message)`
- `exception($exception, $message, $statusCode)`
- `multipleErrors($errors, $message, $statusCode)`

### Specialized Methods
- `paginated($paginator, $message, $resourceClass)`
- `resource($resource, $message, $statusCode)`
- `collection($collection, $message)`
- `custom($data, $statusCode)`
- `rateLimitExceeded($message, $retryAfter)`

## Integration Examples

### Controller Usage
```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ResponseBuilder;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        try {
            $products = Product::paginate(15);
            return ResponseBuilder::paginated($products, 'Products retrieved successfully', ProductResource::class);
        } catch (\Exception $exception) {
            return ResponseBuilder::exception($exception, 'Failed to retrieve products');
        }
    }

    public function store(Request $request)
    {
        try {
            $product = Product::create($request->validated());
            return ResponseBuilder::created(new ProductResource($product), 'Product created successfully');
        } catch (\Exception $exception) {
            return ResponseBuilder::exception($exception, 'Failed to create product');
        }
    }

    public function show($id)
    {
        try {
            $product = Product::findOrFail($id);
            return ResponseBuilder::success(new ProductResource($product), 'Product retrieved successfully');
        } catch (\Exception $exception) {
            return ResponseBuilder::notFound('Product not found');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->update($request->validated());
            return ResponseBuilder::updated(new ProductResource($product), 'Product updated successfully');
        } catch (\Exception $exception) {
            return ResponseBuilder::exception($exception, 'Failed to update product');
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            return ResponseBuilder::deleted('Product deleted successfully');
        } catch (\Exception $exception) {
            return ResponseBuilder::exception($exception, 'Failed to delete product');
        }
    }
}
```

### Authentication Controller
```php
public function login(LoginRequest $request)
{
    try {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return ResponseBuilder::unauthorized('Invalid credentials');
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ResponseBuilder::success([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    } catch (\Exception $exception) {
        return ResponseBuilder::exception($exception, 'Login failed');
    }
}
```

## Best Practices

### 1. Consistent Error Handling
```php
// Always use try-catch blocks
try {
    // Your logic
} catch (\Exception $exception) {
    report($exception); // Log the exception
    return ResponseBuilder::exception($exception, 'User-friendly message');
}
```

### 2. Meaningful Messages
```php
// Good
return ResponseBuilder::success($data, 'Products retrieved successfully');

// Bad
return ResponseBuilder::success($data, 'Success');
```

### 3. Proper Status Codes
```php
// Use appropriate methods for different scenarios
return ResponseBuilder::created($data);      // 201
return ResponseBuilder::updated($data);      // 200
return ResponseBuilder::deleted();           // 200
return ResponseBuilder::notFound();          // 404
return ResponseBuilder::unauthorized();      // 401
return ResponseBuilder::forbidden();         // 403
```

### 4. Resource Transformation
```php
// Always transform data using resources
return ResponseBuilder::success(new UserResource($user));
return ResponseBuilder::paginated($users, 'Users retrieved', UserResource::class);
```

### 5. Validation Errors
```php
// In form request classes
public function failedValidation(Validator $validator)
{
    throw new HttpResponseException(
        ResponseBuilder::validationError($validator->errors())
    );
}
```

## Debug Mode

In development mode (`APP_DEBUG=true`), exception responses include additional debug information:

```json
{
    "success": false,
    "message": "Operation failed",
    "debug": {
        "exception": "Illuminate\\Database\\QueryException",
        "message": "SQLSTATE[42S02]: Base table or view not found",
        "file": "/path/to/file.php",
        "line": 123,
        "trace": "Stack trace here..."
    },
    "timestamp": "2023-12-07T10:30:00.000Z"
}
```

## Testing

```php
// In your tests
public function test_product_creation()
{
    $response = $this->postJson('/api/products', $productData);
    
    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'price',
                // ... other fields
            ],
            'timestamp'
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Product created successfully'
        ]);
}
```

## Migration Guide

### From Standard Laravel Responses
```php
// Before
return response()->json(['message' => 'Success'], 200);

// After
return ResponseBuilder::success(null, 'Success');
```

### From Custom Response Arrays
```php
// Before
return response()->json([
    'status' => 'success',
    'data' => $data,
    'message' => 'Data retrieved'
], 200);

// After
return ResponseBuilder::success($data, 'Data retrieved');
```

This ResponseBuilder ensures consistent, well-structured API responses throughout your application while providing comprehensive error handling and debugging capabilities. 
