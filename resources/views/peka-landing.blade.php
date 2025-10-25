<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PEKA</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>[x-cloak]{display:none !important}</style>
</head>
<body class="min-h-screen bg-no-repeat bg-cover bg-center"
      style="background-image:
        linear-gradient(to top, #F97316 0%, #1E40AF 100%),
        url('/images/barelang.jpg');">

  <!-- NAVBAR -->
  <header class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="max-w-6xl mx-auto flex items-center gap-4 px-6 py-3">
      <!-- Logo instansi -->
      <img src="/images/logo-instansi.png" alt="Logo Instansi" class="h-12 w-auto">

      <!-- Judul + Tagline -->
      <div class="leading-tight">
        <h1 class="text-xl font-bold text-slate-900">Disdukcapil Prima</h1>
        <p class="text-sm text-slate-600">Indonesia Maju</p>
      </div>
    </div>
  </header>

  <div x-data="{ showForm: false }" class="min-h-screen">
    {{-- LANDING --}}
    <section x-show="!showForm" x-transition.opacity.duration.300ms class="min-h-[calc(100vh-64px)] flex items-center">
      <div class="max-w-6xl mx-auto px-6 grid lg:grid-cols-12 gap-8 items-center">
        <div class="lg:col-span-7 text-white drop-shadow">
          <h2 class="text-5xl font-extrabold">PEKA — Penilaian Layanan</h2>
          <p class="mt-4 text-white/90 text-lg max-w-xl">Kasih rating cepat 5 langkah.</p>

          <div class="mt-8 flex gap-3">
            <button
              class="px-6 py-4 rounded-2xl font-semibold text-white bg-gradient-to-r from-[#F59E0B] to-[#1E40AF] hover:brightness-110"
              @click="
                showForm = true;
                $nextTick(() => {
                  document.querySelector('#pekaRoot')?.scrollIntoView({ behavior: 'smooth' });
                })
              ">
              Mulai Penilaian
            </button>
          </div>
        </div>

        <aside class="lg:col-span-5">
          <div class="rounded-2xl border border-white/30 bg-white/85 backdrop-blur p-5 shadow">
            <h3 class="font-semibold text-neutral-900">Scan QR buat mulai</h3>
            <img src="/images/qr-peka.png" alt="QR" class="mt-4 w-full rounded-lg bg-white object-contain">
          </div>
        </aside>
      </div>
    </section>

    {{-- FORM (Livewire) --}}
    <section id="pekaRoot" x-cloak x-show="showForm" x-transition.opacity.duration.300ms class="min-h-screen py-10">
      @livewire('peka') {{-- App\Livewire\Peka --}}
    </section>
  </div>
</body>
</html>
