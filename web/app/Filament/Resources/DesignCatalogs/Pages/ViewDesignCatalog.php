<?php

namespace App\Filament\Resources\DesignCatalogs\Pages;

use App\Filament\Resources\DesignCatalogs\DesignCatalogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDesignCatalog extends ViewRecord
{
    protected static string $resource = DesignCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
