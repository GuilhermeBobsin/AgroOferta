<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\NegotiationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ListingController::class, 'index'])->name('home');

Route::middleware('auth')->group(function () {
    // Anúncios (rotas fixas antes de /anuncios/{listing})
    Route::get('/anuncios/novo', [ListingController::class, 'create'])->name('listings.create');
    Route::post('/anuncios', [ListingController::class, 'store'])->middleware('throttle:10,1')->name('listings.store');
    Route::get('/meus-anuncios', [ListingController::class, 'mine'])->name('listings.mine');
    Route::get('/anuncios/{listing}/editar', [ListingController::class, 'edit'])->name('listings.edit');
    Route::put('/anuncios/{listing}', [ListingController::class, 'update'])->middleware('throttle:30,1')->name('listings.update');
    Route::patch('/anuncios/{listing}/status', [ListingController::class, 'updateStatus'])->name('listings.status');
    Route::delete('/anuncios/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');

    // Negociações e avaliações
    Route::get('/negociacoes', [NegotiationController::class, 'index'])->name('negotiations.index');
    Route::post('/anuncios/{listing}/negociar', [NegotiationController::class, 'store'])->middleware('throttle:20,1')->name('negotiations.store');
    Route::get('/negociacoes/{negotiation}', [NegotiationController::class, 'show'])->name('negotiations.show');
    Route::post('/negociacoes/{negotiation}/mensagens', [NegotiationController::class, 'message'])->middleware('throttle:20,1')->name('negotiations.message');
    Route::post('/negociacoes/{negotiation}/avaliar', [ReviewController::class, 'store'])->middleware('throttle:10,1')->name('reviews.store');

    // Contato / localização
    Route::get('/meu-contato', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('/meu-contato', [AccountController::class, 'update'])->name('account.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/anuncios/{listing}', [ListingController::class, 'show'])->name('listings.show');

Route::get('/dashboard', fn () => redirect()->route('home'))->middleware('auth')->name('dashboard');

require __DIR__.'/auth.php';
