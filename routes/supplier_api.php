<?php

use App\Http\Controllers\GygSupplierApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'gyg.supplier.basic'])->group(function (): void {
    Route::get('/1/get-availabilities/', [GygSupplierApiController::class, 'getAvailabilities']);
    Route::post('/1/reserve/', [GygSupplierApiController::class, 'reserve']);
    Route::post('/1/cancel-reservation/', [GygSupplierApiController::class, 'cancelReservation']);
    Route::post('/1/book/', [GygSupplierApiController::class, 'book']);
    Route::post('/1/cancel-booking/', [GygSupplierApiController::class, 'cancelBooking']);
    Route::post('/1/notify/', [GygSupplierApiController::class, 'notify']);
    Route::get('/1/products/{productId}/pricing-categories/', [GygSupplierApiController::class, 'pricingCategories']);
    Route::get('/1/suppliers/{supplierId}/products/', [GygSupplierApiController::class, 'supplierProducts']);
    Route::get('/1/products/{productId}/addons/', [GygSupplierApiController::class, 'addons']);
    Route::get('/1/products/{productId}', [GygSupplierApiController::class, 'productDetails']);
});
