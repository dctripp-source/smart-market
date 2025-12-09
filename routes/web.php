<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FrontendController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Frontend Routes
Route::get('/', [FrontendController::class, 'index'])->name('home');
Route::get('/shop', [FrontendController::class, 'shop'])->name('shop');
Route::get('/shop/{category}', [FrontendController::class, 'category'])->name('shop.category');
Route::get('/product/{slug}', [FrontendController::class, 'product'])->name('product.show');
Route::get('/o-nama', [FrontendController::class, 'about'])->name('about');
Route::get('/kontakt', [FrontendController::class, 'contact'])->name('contact');

// Admin Routes
Route::prefix('admin')->group(function () {
    // Login routes (no auth required)
    Route::get('/login', [AdminController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminController::class, 'login'])->name('admin.login.post');
    
    // Protected admin routes
    Route::middleware(['admin.auth'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');
        
        // Suppliers
        Route::get('/suppliers', [AdminController::class, 'suppliers'])->name('admin.suppliers');
        Route::post('/suppliers', [AdminController::class, 'storeSupplier'])->name('admin.suppliers.store');
        Route::post('/suppliers/{id}/toggle', [AdminController::class, 'toggleSupplier'])->name('admin.suppliers.toggle');
        Route::post('/suppliers/{id}/sync', [AdminController::class, 'syncSupplier'])->name('admin.suppliers.sync');
        Route::post('/suppliers/sync-all', [AdminController::class, 'syncAll'])->name('admin.suppliers.sync-all');
        
        // Products
        Route::get('/products', [AdminController::class, 'products'])->name('admin.products');
        Route::post('/products/{id}/toggle', [AdminController::class, 'toggleProduct'])->name('admin.products.toggle'); // NEW
        
        // Categories
        Route::get('/categories', [AdminController::class, 'categories'])->name('admin.categories');
        Route::post('/categories', [AdminController::class, 'storeCategory'])->name('admin.categories.store');
        
        // Settings
        Route::get('/settings', [AdminController::class, 'settings'])->name('admin.settings');
        Route::post('/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
    });
});