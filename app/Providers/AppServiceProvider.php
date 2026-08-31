<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Event;
use App\Models\User;
use App\Models\Reservation;
use App\Policies\EventPolicy;
use App\Policies\UserPolicy;
use App\Policies\ReservationPolicy;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Reservation::class, ReservationPolicy::class);
                if (app()->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
