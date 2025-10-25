<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>App</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  @livewireStyles
</head>
<body class="min-h-screen bg-neutral-900 text-white antialiased selection:bg-neutral-700 selection:text-white">
  <main class="mx-auto max-w-6xl p-6">
    {{ $slot }}
  </main>
  @livewireScripts
</body>
</html>
