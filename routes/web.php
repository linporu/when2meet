<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\ParticipantController;
use Illuminate\Support\Facades\Route;

Route::controller(EventController::class)->group(function () {
    Route::get('/', 'index')->name('events.index');
    Route::post('/', 'store')->name('events.store');
    Route::get('/{event:hash}', 'show')->name('events.show');
});

Route::controller(ParticipantController::class)->group(function () {
    Route::post('/{event:hash}/participants', 'setName')->name('participants.setName');
    Route::match(['GET', 'POST'], '/{event:hash}/participants/{participant}/availability/edit', 'editAvailability')->name('participants.availability.edit');
});
