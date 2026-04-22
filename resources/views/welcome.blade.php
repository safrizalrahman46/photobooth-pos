@php
    // Dummy Data untuk visualisasi (nantinya dipindah ke Controller)
    $packages = [
        (object) ['id' => 1, 'name' => 'Basic', 'base_price' => 35000, 'duration_minutes' => 10],
        (object) ['id' => 2, 'name' => 'Mandi Bola / Vintage', 'base_price' => 45000, 'duration_minutes' => 10],
        (object) ['id' => 3, 'name' => 'Minimarket / Sofa', 'base_price' => 50000, 'duration_minutes' => 10],
    ];
    $branches = []; // Jika ingin ditampilkan di bawah
@endphp

@include('web.landing.index', ['packages' => $packages, 'branches' => $branches])