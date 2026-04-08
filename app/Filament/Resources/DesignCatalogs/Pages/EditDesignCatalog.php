<?php

namespace App\Filament\Resources\DesignCatalogs\Pages;

use App\Filament\Resources\DesignCatalogs\DesignCatalogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDesignCatalog extends EditRecord
{
    protected static string $resource = DesignCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
