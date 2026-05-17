<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Schemas\Schema;

class PaymentForm
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
