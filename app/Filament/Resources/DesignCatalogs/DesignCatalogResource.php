<?php

namespace App\Filament\Resources\DesignCatalogs;

use App\Filament\Resources\DesignCatalogs\Pages\CreateDesignCatalog;
use App\Filament\Resources\DesignCatalogs\Pages\EditDesignCatalog;
use App\Filament\Resources\DesignCatalogs\Pages\ListDesignCatalogs;
use App\Filament\Resources\DesignCatalogs\Pages\ViewDesignCatalog;
use App\Filament\Resources\DesignCatalogs\Schemas\DesignCatalogForm;
use App\Filament\Resources\DesignCatalogs\Schemas\DesignCatalogInfolist;
use App\Filament\Resources\DesignCatalogs\Tables\DesignCatalogsTable;
use App\Models\DesignCatalog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DesignCatalogResource extends Resource
{
    protected static ?string $model = DesignCatalog::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return DesignCatalogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DesignCatalogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DesignCatalogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDesignCatalogs::route('/'),
            'create' => CreateDesignCatalog::route('/create'),
            'view' => ViewDesignCatalog::route('/{record}'),
            'edit' => EditDesignCatalog::route('/{record}/edit'),
        ];
    }
}
