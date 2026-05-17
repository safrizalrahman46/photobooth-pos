<?php

namespace App\Filament\Resources\DesignCatalogs\Schemas;

use Filament\Schemas\Schema;

class DesignCatalogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'sm' => 2])
            ->components([
                //
            ]);
    }
}
