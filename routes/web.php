<?php

use App\Http\Controllers\SocialLoginController;
use App\Http\Middleware\MustVerfiyEmail;
use App\Livewire\Auth;
use App\Livewire\Cart;
use App\Livewire\Client;
use App\Livewire\Dashboard;
use App\Livewire\Invoices;
use App\Livewire\Products;
use App\Livewire\Services;
use App\Livewire\Tickets;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

// Destroy the session and log out the user.
//auth()->logout();
// Authorization routes
Route::group(['middleware' => ['web', 'guest']], function () {
    Route::get('/login', Auth\Login::class)->name('login');
    Route::get('/2fa', Auth\Tfa::class)->name('2fa');
    Route::get('/register', Auth\Register::class)->name('register');
    // Todo
    Route::get('/password/reset')->name('password.reset');

    Route::get('/oauth/{provider}', [SocialLoginController::class, 'redirect'])->name('oauth.redirect');
    Route::get('/oauth/{provider}/callback', [SocialLoginController::class, 'handle'])->name('oauth.handle');
});

Route::group(['middleware' => ['web', 'auth', MustVerfiyEmail::class]], function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/invoices', Invoices\Index::class)->name('invoices');
    Route::get('/invoices/{invoice}', Invoices\Show::class)->name('invoices.show');

    Route::get('/tickets', Tickets\Index::class)->name('tickets');
    Route::get('/tickets/create', Tickets\Create::class)->name('tickets.create');
    Route::get('/tickets/{ticket}', Tickets\Show::class)->name('tickets.show');

    Route::get('/services', Services\Index::class)->name('services');
    Route::get('/services/{service}', Services\Show::class)->name('services.show');
});

Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('account', Client\Account::class)->name('account');
    Route::get('account/security', Client\Security::class)->name('account.security');

    Route::get('/email/verify', Auth\VerifyEmail::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard');
    })->middleware(['signed'])->name('verification.verify');
});

Route::get('cart', Cart::class)->name('cart');

Route::group(['prefix' => 'products'], function () {
    Route::get('/{category:slug}', Products\Index::class)->name('category.show')/*->where('category', '[A-Za-z0-9_/-]+')*/;
    Route::get('/{category:slug}/{product:slug}', Products\Show::class)->name('products.show')/*->where('category', '[A-Za-z0-9_/-]+')*/;
    Route::get('/{category:slug}/{product:slug}/checkout', Products\Checkout::class)->name('products.checkout')/*->where('category', '[A-Za-z0-9_/-]+')*/;
    // Allow for nested categories
});

Route::group([
    'as' => 'passport.',
    'prefix' => config('passport.path', 'oauth'),
    'namespace' => '\Laravel\Passport\Http\Controllers',
], function () {
    Route::get('/oauth/authorize', [
        'uses' => 'Laravel\Passport\Http\Controllers\AuthorizationController@authorize',
        'as' => 'x.authorize',
        'middleware' => 'web',
    ]);
});
