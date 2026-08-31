<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Hasnayeen\Themes\Filament\Pages\Themes;

class CustomThemePage extends Themes
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.themes';

    protected static ?string $title = 'Themes';

    protected static ?string $slug = 'themes';

    public function getThemes()
    {
        $themes = parent::getThemes();
        
        return collect($themes)->reject(function ($theme, $key) {
            return $key === 'nord';
        });
    }
}
