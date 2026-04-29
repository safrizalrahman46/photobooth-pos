<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ready to Pict - Studio Foto Mandiri</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            scroll-behavior: smooth;
        }

        .bg-navy-dark {
            background-color: #001a3d;
        }

        .text-navy-dark {
            color: #001a3d;
        }
    </style>
</head>

<body class="bg-white">
    @yield('main-content')

    <footer class="bg-white py-12 border-t border-gray-50">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <span class="text-xl font-bold text-blue-700">Ready to Pict</span>
                <p class="text-xs text-gray-400 mt-2">© 2026 Ready to Pict Studio. All rights reserved.</p>
            </div>
            <div class="flex gap-6 text-xs font-semibold text-gray-500 uppercase tracking-widest">
                <a href="#" class="hover:text-blue-700">Instagram</a>
                <a href="#" class="hover:text-blue-700">Contact</a>
                <a href="#" class="hover:text-blue-700">Privacy Policy</a>
            </div>
        </div>
    </footer>
</body>

</html>