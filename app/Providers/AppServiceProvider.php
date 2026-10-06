<?php

namespace App\Providers;

use App\Models\KostProfile;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Batasi percobaan login: 5x per menit per kombinasi email+IP, cegah brute force.
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = strtolower((string) $request->input('email')) . '|' . $request->ip();

            return Limit::perMinute(5)->by($throttleKey);
        });

        // Batasi akses/submit link publik (pendaftaran & pembayaran) per IP, cegah spam/brute force token.
        RateLimiter::for('public-links', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        View::composer(['layouts.app', 'auth.login'], function ($view) {
            $view->with('appBrandName', KostProfile::query()->value('nama_kost') ?: 'mKost');
        });

        // @canMenu('menu.key') or @canMenu('menu.key', 'action') — hides block when user lacks permission
        Blade::directive('canMenu', function (string $expression) {
            return "<?php if(auth()->user()?->hasMenuPermission($expression)): ?>";
        });

        Blade::directive('endCanMenu', function () {
            return '<?php endif; ?>';
        });
    }
}
