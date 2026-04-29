<?php

namespace App\Filament\Resources\DesignCatalogs\Pages;

use App\Filament\Resources\DesignCatalogs\DesignCatalogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDesignCatalogs extends ListRecords
{
    protected static string $resource = DesignCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
