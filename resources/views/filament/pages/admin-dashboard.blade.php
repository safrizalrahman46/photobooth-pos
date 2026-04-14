@php
    $bootstrap = $this->bootstrapData();
@endphp

<x-filament-panels::page>
    <div id="admin-dashboard-app"></div>
    <script id="admin-dashboard-app-props" type="application/json">@json($bootstrap)</script>
</x-filament-panels::page>
