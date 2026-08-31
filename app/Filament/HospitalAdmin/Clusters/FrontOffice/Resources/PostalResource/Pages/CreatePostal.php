<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\PostalResource\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\PostalResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePostal extends CreateRecord
{
    protected static string $resource = PostalResource::class;

    protected static bool $canCreateAnother = false;
}
