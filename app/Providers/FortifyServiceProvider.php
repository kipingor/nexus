<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

/**
 * Configures Laravel Fortify to use Inertia.js views
 * instead of Blade components.
 *
 * Fortify handles the POST /login, POST /register, POST /logout routes.
 * Inertia handles the view rendering (GET /login → render 'Auth/Login').
 */
class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Disable Fortify's built-in view routes — Inertia handles all views.
        Fortify::loginView(fn () => inertia('Auth/Login', [
            'canResetPassword' => true,
            'status' => session('status'),
        ]));
        Fortify::registerView(fn () => inertia('Auth/Register'));
        Fortify::requestPasswordResetLinkView(fn () => inertia('Auth/ForgotPassword', [
            'status' => session('status'),
        ]));
        Fortify::resetPasswordView(
            fn (Request $request) => inertia('Auth/ResetPassword', [
                'token' => $request->route('token'),
                'email' => $request->email,
            ])
        );

        RateLimiter::for('login', function (Request $request): Limit {
            $throttleKey = mb_strtolower($request->input(Fortify::username(), '')).'|'.$request->ip();
            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->session()->getId());
        });
    }
}
