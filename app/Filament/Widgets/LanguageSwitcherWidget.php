<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageSwitcherWidget extends Widget
{
    protected static string $view = 'filament.widgets.language-switcher';

    protected int | string | array $columnSpan = 'full';

    public function switchLanguage(string $locale): void
    {
        Session::put('locale', $locale);
        App::setLocale($locale);
        redirect()->back();
    }

    public function getCurrentLocale(): string
    {
        return App::getLocale();
    }
}

