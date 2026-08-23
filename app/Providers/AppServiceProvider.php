<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.rumika');
        Paginator::defaultSimpleView('vendor.pagination.rumika');

        Blade::directive('money', function (string $expression) {
            return "<?php echo \\App\\Support\\Money::format({$expression}); ?>";
        });
    }
}
