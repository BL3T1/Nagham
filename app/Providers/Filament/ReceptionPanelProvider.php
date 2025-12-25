<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ReceptionPanelProvider extends PanelProvider
{
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
            ->id('reception')
            ->path('reception')
            ->login()
            ->brandLogo(asset('logo.png'))
            ->brandLogoHeight('4rem')
            ->renderHook(
                \Filament\View\PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.custom-logo-styles'),
            )
            ->colors([
                'primary' => '#e1caaa',
                'secondary' => '#e1caaa',
                'gray' => '#26533e',
            ])
            ->darkMode(true)
            ->discoverResources(in: app_path('Filament/Reception/Resources'), for: 'App\Filament\Reception\Resources')
            ->discoverPages(in: app_path('Filament/Reception/Pages'), for: 'App\Filament\Reception\Pages')
            ->discoverWidgets(in: app_path('Filament/Reception/Widgets'), for: 'App\Filament\Reception\Widgets')
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
            ])
            ->authGuard('web')
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn () => App::getLocale() === 'ar' ? 'English' : 'العربية')
                    ->icon('heroicon-o-language')
                    ->url(fn () => $this->getLanguageSwitchUrl())
                    ->sort(100),
            ]);
    }
}
