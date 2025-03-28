<?php

use Illuminate\Support\Facades\Route;
use Jakyeru\Larascord\Http\Controllers\DiscordController;

Route::redirect('/login', 'https://discord.com/oauth2/authorize?client_id=' . config('larascord.client_id')
    . '&redirect_uri=' . config('larascord.redirect_uri')
    . '&response_type=code&scope=' . implode('%20', explode('&', config('larascord.scopes')))
    . '&prompt=' . config('larascord.prompt', 'none'))
    ->middleware(['web', 'guest'])
    ->name('login');

Route::post('logout', App\Http\Actions\Logout::class)
    ->middleware(['web', 'auth'])
    ->name('logout');

Route::group(['prefix' => config('larascord.route_prefix', 'larascord'), 'middleware' => ['web']], function() {
    Route::get('/callback', [DiscordController::class, 'handle'])
        ->name('larascord.login');

    Route::redirect('/refresh-token', 'https://discord.com/oauth2/authorize?client_id=' . config('larascord.client_id')
        . '&redirect_uri=' . config('larascord.redirect_uri')
        . '&response_type=code&scope=' . implode('%20', explode('&', config('larascord.scopes')))
        . '&prompt=' . config('larascord.prompt', 'none'))
        ->middleware(['web', 'auth'])
        ->name('larascord.refresh_token');
});

Route::delete('/profile', [DiscordController::class, 'destroy'])->middleware(['web', 'auth', 'password.confirm'])->name('profile.destroy');