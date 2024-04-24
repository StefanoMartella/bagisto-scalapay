<?php

use Illuminate\Support\Facades\Route;
use Webkul\Scalapay\Http\Controllers\PaymentController;

Route::group(['middleware' => ['web', 'theme', 'locale', 'currency']], function () {

    /**
     * Scalapay payment routes
     */
    Route::get('/scalapay-redirect', [PaymentController::class, 'redirect'])->name('scalapay.process');

    Route::get('/scalapay-success', [PaymentController::class, 'success'])->name('scalapay.success');

    Route::post('/scalapay-cancel', [PaymentController::class, 'failure'])->name('scalapay.cancel');

});
