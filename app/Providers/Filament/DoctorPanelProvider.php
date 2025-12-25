<?php

namespace App\Providers\Filament;

use App\Filament\Doctor\Pages\SessionManagementPage;
use App\Filament\Doctor\Pages\TodayPatientsPage;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\App;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DoctorPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        Filament::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn (): View => view('filament.doctor.custom-styles'),
        );
    }

    protected function getLanguageSwitchUrl(): string
    {
        $currentLocale = App::getLocale();
        $newLocale = $currentLocale === 'ar' ? 'en' : 'ar';
        $currentUrl = request()->url();
        return url("/language/{$newLocale}?redirect=" . urlencode($currentUrl));
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('doctor')
            ->path('doctor')
            ->login()
            ->brandLogo(asset('logo.png'))
            ->brandLogoHeight('4rem')
            ->colors([
                'primary' => '#e1caaa',
                'secondary' => '#e1caaa',
                'gray' => '#26533e',
            ])
            ->darkMode(true)
            ->discoverResources(in: app_path('Filament/Doctor/Resources'), for: 'App\\Filament\\Doctor\\Resources')
            ->pages([
                SessionManagementPage::class,
                TodayPatientsPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Doctor/Widgets'), for: 'App\\Filament\\Doctor\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

