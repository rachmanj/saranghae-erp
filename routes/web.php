<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\VendorPaymentController;

Route::get('/', function () {
    return view('welcome');
});

// Purchase Orders
Route::resource('purchase-orders', PurchaseOrderController::class);
Route::post('purchase-orders/{purchaseOrder}/send', [PurchaseOrderController::class, 'sendPurchaseOrder'])->name('purchase-orders.send');
Route::get('purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'generatePdf'])->name('purchase-orders.pdf');

// Goods Receipts
Route::resource('goods-receipts', GoodsReceiptController::class)->except(['edit', 'update', 'destroy']);
Route::get('goods-receipts/{goodsReceipt}/pdf', [GoodsReceiptController::class, 'generatePdf'])->name('goods-receipts.pdf');

// Vendor Payments
Route::resource('vendor-payments', VendorPaymentController::class)->except(['edit', 'update', 'destroy']);
Route::get('vendor-payments/{vendorPayment}/pdf', [VendorPaymentController::class, 'generatePdf'])->name('vendor-payments.pdf');
