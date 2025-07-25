<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::controller(EventController::class)->group(function () {
    Route::get('/', 'index')->name('events.index');
    Route::post('/', 'store')->name('events.store');
    Route::get('/{event:hash}', 'show')->name('events.show');
    Route::post('{event:hash}/join', 'join')->name('events.join');
});
