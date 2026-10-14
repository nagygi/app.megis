<?php

use App\Enums\LegalDocumentType;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminPasswordSetupController;
use App\Http\Controllers\Admin\ImpersonationController as AdminImpersonationController;
use App\Http\Controllers\Admin\LegalDocumentVersionController;
use App\Http\Controllers\Client\CustomerLoginController;
use App\Http\Controllers\Client\CustomerLogoutController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\ImpersonationController as ClientImpersonationController;
use App\Http\Controllers\Client\InvitationController;
use App\Http\Controllers\Client\LandingController;
use App\Http\Controllers\Client\LegalAcceptanceController;
use App\Http\Controllers\Client\LoginCodeController;
use App\Http\Controllers\Client\LoginLinkController;
use App\Http\Controllers\LegalDocumentController;
use App\Livewire\Admin;
use App\Livewire\Client;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages (15 · 15.1)
|--------------------------------------------------------------------------
*/

Route::view('/', 'public.home')->name('public.home');

Route::get('/{document}', LegalDocumentController::class)
    ->whereIn('document', array_column(LegalDocumentType::cases(), 'value'))
    ->name('legal.show');

/*
|--------------------------------------------------------------------------
| Admin (01, 02, 05, 33)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('belepes', [AdminLoginController::class, 'create'])->name('login');
        Route::post('belepes', [AdminLoginController::class, 'store'])->name('login.store');
    });

    Route::get('jelszo-beallitas/{token}', [AdminPasswordSetupController::class, 'create'])->name('password.setup');
    Route::post('jelszo-beallitas/{token}', [AdminPasswordSetupController::class, 'store'])->name('password.setup.store');

    Route::middleware('admin')->group(function () {
        Route::post('kilepes', [AdminLoginController::class, 'destroy'])->name('logout');
        Route::redirect('/', '/admin/szervezetek')->name('dashboard');

        Route::livewire('profil', Admin\Profile::class)->name('profile');

        Route::livewire('adminok', Admin\AdminUsers\Index::class)->name('admins.index');
        Route::livewire('adminok/meghivas', Admin\AdminUsers\Form::class)->name('admins.create');
        Route::livewire('adminok/{user}/szerkesztes', Admin\AdminUsers\Form::class)->name('admins.edit');

        Route::livewire('termekek', Admin\Products\Index::class)->name('products.index');
        Route::livewire('termekek/uj', Admin\Products\Form::class)->name('products.create');
        Route::livewire('termekek/{product}/szerkesztes', Admin\Products\Form::class)->name('products.edit');

        Route::livewire('szervezetek', Admin\Organizations\Index::class)->name('organizations.index');
        Route::livewire('szervezetek/uj', Admin\Organizations\Form::class)->name('organizations.create');
        Route::livewire('szervezetek/{organization}', Admin\Organizations\Form::class)->name('organizations.edit');
        Route::livewire('szervezetek/{organization}/csapatok', Admin\Organizations\Teams::class)->name('organizations.teams');
        Route::livewire('szervezetek/{organization}/felhasznalok', Admin\Organizations\Members::class)->name('organizations.members');
        Route::livewire('szervezetek/{organization}/felhasznalok/meghivas', Admin\Organizations\MemberForm::class)->name('organizations.members.create');
        Route::livewire('szervezetek/{organization}/felhasznalok/{membership}/szerkesztes', Admin\Organizations\MemberForm::class)
            ->name('organizations.members.edit')
            ->scopeBindings();

        Route::livewire('ugyfelek', Admin\Clients\Index::class)->name('clients.index');
        Route::livewire('ugyfelek/{user}', Admin\Clients\Show::class)->name('clients.show');
        Route::post('ugyfelek/{user}/neveben', [AdminImpersonationController::class, 'store'])->name('impersonate');

        Route::livewire('beallitasok/jogi-dokumentumok', Admin\Settings\LegalDocuments::class)->name('settings.legal');
        Route::get('jogi-dokumentumok/{version}', LegalDocumentVersionController::class)->name('legal.version');

        Route::middleware('superadmin')->group(function () {
            Route::livewire('rendszer', Admin\System\Overview::class)->name('system.index');
            Route::livewire('rendszer/health', Admin\System\Health::class)->name('system.health');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Customer login and registration (14)
|--------------------------------------------------------------------------
*/

Route::middleware('guest:web')->name('client.')->group(function () {
    Route::get('belepes', [CustomerLoginController::class, 'create'])->name('login');
    Route::post('belepes', [CustomerLoginController::class, 'store'])->name('login.store');
    Route::get('belepes/kod', [LoginCodeController::class, 'create'])->name('login.code');
    Route::post('belepes/kod', [LoginCodeController::class, 'store'])->name('login.code.store');
    Route::post('belepes/kod/ujra', [LoginCodeController::class, 'resend'])->name('login.code.resend');
    Route::get('belepes/link/{token}', [LoginLinkController::class, 'show'])->name('login.link');
    Route::post('belepes/link/{token}', [LoginLinkController::class, 'store'])->name('login.link.store');
    Route::post('belepes/link/{token}/uj', [LoginLinkController::class, 'resend'])->name('login.link.resend');
});

Route::name('client.')->group(function () {
    Route::get('meghivo/{token}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('meghivo/{token}', [InvitationController::class, 'store'])->middleware('throttle:public-form')->name('invitation.store');
});

/*
|--------------------------------------------------------------------------
| Customer side (15). /{slug}/… stays last: slugs never take a system path.
|--------------------------------------------------------------------------
*/

Route::middleware('customer')->name('client.')->group(function () {
    Route::post('kilepes', CustomerLogoutController::class)->name('logout');
    Route::post('kilepes-a-nevebol', [ClientImpersonationController::class, 'destroy'])->name('impersonation.end');
    Route::view('szunetel', 'client.paused')->name('paused');

    Route::get('elfogadas', [LegalAcceptanceController::class, 'show'])->name('legal.show');
    Route::post('elfogadas', [LegalAcceptanceController::class, 'store'])->middleware('not-impersonating')->name('legal.store');

    Route::middleware('legal')->group(function () {
        Route::get('tovabb', LandingController::class)->name('landing');

        Route::prefix('{organization:slug}')
            ->where(['organization' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
            ->middleware('organization')
            ->group(function () {
                Route::get('/', HomeController::class)->name('home');
                Route::livewire('profil', Client\Profile::class)->name('profile');
            });
    });
});
