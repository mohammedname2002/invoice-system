<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Catch N+1 queries, silently discarded attributes and typos early.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Financial summaries are for the people who manage the books.
        Gate::define('view-reports', fn (User $user): bool => $user->canManageRecords());
    }
}
