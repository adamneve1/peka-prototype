<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  {{-- Dynamic title --}}
  <title>{{ $title ?? config('app.name', 'PEKA – Penilaian Layanan') }}</title>

  {{-- Favicon --}}
  <link rel="icon" href="{{ asset('images/favicon-96x96.png') }}" type="image/png">
  <link rel="shortcut icon" href="{{ asset('images/pemkot-batam.png') }}" type="image/png">

  {{-- Styles & Livewire --}}
  @vite(['resources/css/app.css','resources/js/app.js'])
  @livewireStyles
</head>

<body class="min-h-screen bg-neutral-900 text-white antialiased selection:bg-neutral-700 selection:text-white">
  <main class="mx-auto max-w-6xl p-6">
    {{ $slot }}
  </main>
  @livewireScripts

@stack('scripts')

</body>
</html>
