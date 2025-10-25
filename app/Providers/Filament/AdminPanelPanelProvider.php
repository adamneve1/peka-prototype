<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\StaffLeaderboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\StaffTop5Chart;

class AdminPanelPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
        ->brandLogo(asset('images/pemkot-batam.png')) // path ke logo lo
        ->brandLogoHeight('3rem') // bisa adjust ukuran
        ->brandName('Nama Aplikasi')
            ->default()
            ->id('adminPanel')
            ->path('adminPanel')
            ->login()
        
            

            // theme color, taruh di luar widgets
            ->colors([
                'primary' => Color::Emerald,
            ])

            // resources & pages
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])

            // widgets
           // ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                StaffLeaderboard::class,
                \App\Filament\Widgets\StaffBottom5Chart::class,
              //  AccountWidget::class,
               // FilamentInfoWidget::class,
                \App\Filament\Widgets\PekaBreakdown::class,
            
               
              
                
            ])

            // middleware
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
