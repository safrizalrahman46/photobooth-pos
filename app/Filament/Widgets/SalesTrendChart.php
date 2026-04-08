<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class SalesTrendChart extends ChartWidget
{
    protected ?string $heading = 'Sales Trend Chart';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
