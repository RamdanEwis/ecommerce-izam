<?php

namespace App\Providers;

use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\BaseRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Base Repository
        $this->app->bind(BaseRepositoryInterface::class, BaseRepository::class);

        // Product Repository
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);

        // Order Repository
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);

        // User Repository
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
