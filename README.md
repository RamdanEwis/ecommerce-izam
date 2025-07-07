# E-commerce API Documentation

## Table of Contents
- [Overview](#overview)
- [Setup Instructions](#setup-instructions)
- [Authentication](#authentication)
- [API Endpoints](#api-endpoints)
- [Running the Application](#running-the-application)
- [Security Considerations](#security-considerations)

## Overview

This is a Laravel-based e-commerce API that provides endpoints for managing products, orders, and user authentication. The API uses Laravel Sanctum for authentication and includes features like product management, order processing, and statistical analysis.

## Setup Instructions

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Node.js and npm (for frontend)
- Redis (optional, for caching)

### Backend Setup

1. Clone the repository:
```bash
git clone [repository-url]
cd ecommerce-izam
```

2. Install PHP dependencies:
```bash
composer install
```

3. Set up environment variables:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure your database in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. Run migrations and seeders:
```bash
php artisan migrate
php artisan db:seed
```

6. Start the Laravel server:
```bash
php artisan serve
```

### Frontend Setup

1. Install JavaScript dependencies:
```bash
npm install
```

2. Build frontend assets:
```bash
npm run dev
```

For production:
```bash
npm run build
```

## Authentication

The API uses Laravel Sanctum for token-based authentication. Here's how it works:

1. **Registration**: Users can register using the `/api/register` endpoint
   - Required fields: name, email, password, password_confirmation

2. **Login**: Users can obtain an authentication token via `/api/login`
   - Required fields: email, password
   - Returns: Bearer token for API access

3. **Using Authentication**:
   - Include the token in your requests:
   ```
   Authorization: Bearer your-token-here
   ```

4. **Logout**: Use `/api/logout` to invalidate the current token

## API Endpoints

### Authentication Endpoints
- `POST /api/register` - Register a new user
- `POST /api/login` - Login and get token
- `POST /api/logout` - Logout (invalidate token)
- `GET /api/user` - Get authenticated user details

### Product Endpoints

#### Public Routes
- `GET /api/products` - List all products
- `GET /api/products/{id}` - Get single product
- `GET /api/products/search` - Search products
- `GET /api/products/low-stock` - Get low stock products
- `GET /api/products/popular` - Get popular products
- `GET /api/products/featured` - Get featured products
- `GET /api/products/recent` - Get recently added products
- `GET /api/products/in-stock` - Get in-stock products
- `GET /api/products/out-of-stock` - Get out-of-stock products

#### Protected Routes (requires authentication)
- `POST /api/products` - Create new product
- `PUT /api/products/{id}` - Update product
- `DELETE /api/products/{id}` - Delete product
- `GET /api/products/statistics` - Get product statistics
- `PUT /api/products/{id}/stock` - Update product stock
- `PUT /api/products/bulk-update` - Bulk update products

### Order Endpoints (all require authentication)
- `GET /api/orders` - List all orders
- `POST /api/orders` - Create new order
- `GET /api/orders/{id}` - Get single order
- `PUT /api/orders/{id}` - Update order
- `DELETE /api/orders/{id}` - Delete order
- `GET /api/orders/my-orders` - Get user's orders
- `GET /api/orders/by-status` - Filter orders by status
- `GET /api/orders/by-date-range` - Filter orders by date
- `GET /api/orders/by-amount-range` - Filter orders by amount
- `GET /api/orders/recent` - Get recent orders
- `GET /api/orders/statistics` - Get order statistics
- `PUT /api/orders/{id}/cancel` - Cancel order
- `PUT /api/orders/{id}/complete` - Complete order
- `PUT /api/orders/{id}/status` - Update order status

## Running the Application

### Development Mode

1. Start the backend server:
```bash
php artisan serve
```

2. Start the frontend development server:
```bash
npm run dev
```

3. Access the application:
- Backend API: http://localhost:8000

### Production Mode

1. npm run build

2. Set up SSL certificates for HTTPS

3. Configure environment variables for production:
```
APP_ENV=production
APP_DEBUG=false
```

## Security Considerations

1. **Authentication**:
   - All sensitive endpoints are protected with authentication
   - Tokens expire after 24 hours of inactivity
   - Maximum of 5 active tokens per user

2. **Input Validation**:
   - All inputs are validated and sanitized
   - Maximum limits set for quantities and prices
   - Text inputs are sanitized to prevent XSS
   - File uploads are restricted by size and type

3. **Rate Limiting**:
   - API endpoints are rate-limited to prevent abuse
   - Login attempts are limited to prevent brute force

4. **Data Protection**:
   - Sensitive data is encrypted at rest
   - HTTPS required in production
   - CORS policies are configured for frontend access

## API Documentation

### Interactive Documentation
- **SwaggerHub**: [E-commerce API Documentation](https://app.swaggerhub.com/apis/DEVRAMDANEWIS/e-commerce_api_documentation/1.0.0)

### Local Documentation Files
- [Swagger JSON](./documentation/swagger-api.json) - OpenAPI specification file
- [Postman Collection](./documentation/postman.json) - Ready-to-use Postman collection
- [cURL Examples](./documentation/curl.txt) - Command-line examples

## Error Handling

The API uses standard HTTP status codes and returns errors in the following format:
```json
{
    "error": {
        "message": "Error message here",
        "code": "ERROR_CODE",
        "details": {}
    }
}
```

Common status codes:
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 429: Too Many Requests
- 500: Server Error 
