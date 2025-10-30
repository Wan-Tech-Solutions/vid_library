<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name', 'Video Library') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.lordicon.com/lordicon.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100">
    <main class="py-8">
        <div class="container mx-auto px-4">
            @yield('content')
        </div>
    </main>

    @stack('scripts')
</body>

</html>
