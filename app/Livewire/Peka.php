<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\{Counter, Service, StaffAssignment, Rating};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class Peka extends Component
{
    /** Step/page state (legacy flag lo biarin) */
    public int $step = 2; // 1=legacy; 2=multi-stage

    /** landing vs form */
    public string $mode = 'landing'; // 'landing' | 'form'

    /** Form state */
    public ?int $serviceId = null;   // 1) layanan
    public ?int $counterId = null;   // 2) loket
    public ?int $staffId   = null;   // 3) petugas
    public ?int $score     = null;   // 4) nilai
    public string $comment = '';     // 5) komentar (opsional)

    /** Data for UI */
    public array $services = [];
    public array $counters = [];     // hasil filter berdasar service terpilih
    public array $staffOptions = [];


        // === Multi-flow (auto nilai banyak petugas berurutan) ===
    public array $staffQueue = [];
    public int $staffIdx = 0;
    public ?int $multiFlowTotal = null;


    protected function rules(): array
    {
        $allowedCounterIds = array_column($this->counters, 'id');

        return [
            'serviceId' => ['required','integer','exists:services,id'],
            'counterId' => ['required','integer', Rule::in($allowedCounterIds)],
            'staffId'   => ['required','integer','exists:staff,id'],
            'score'     => ['required','integer','min:1','max:5'],
            'comment'   => ['nullable','string','max:200'],
        ];
    }

    protected array $messages = [
        'serviceId.required' => 'Pilih layanan dulu.',
        'counterId.required' => 'Pilih loket dulu.',
        'counterId.in'       => 'Loket tidak melayani layanan yang dipilih.',
        'staffId.required'   => 'Pilih petugas dulu.',
        'score.required'     => 'Pilih nilai 1–5.',
    ];

    /** Stage computed: 1 layanan, 2 loket, 3 staff, 4 rate, 5 comment */
    public function getStageProperty(): int
    {
        if ($this->mode !== 'form') return 0;
        if (!$this->serviceId) return 1;
        if (!$this->counterId) return 2;
        if (!$this->staffId)   return 3;
        if (!$this->score)     return 4;
        return 5;
    }

    public function mount(): void
    {
        $this->services = Service::orderBy('name')->get(['id','name'])->toArray();
        $this->counters = []; // baru diisi setelah pilih layanan
    }

    /** ===== Navigation (landing <-> form) ===== */

    public function start(): void
    {
        $this->mode = 'form';
        $this->resetProgress(true);
        $this->dispatch('scroll-top');
    }

    public function startAtStaff(): void
    {
        $this->mode = 'form';
        $this->dispatch('scroll-top');
    }

    public function backToLanding(): void
    {
        $this->resetProgress(true);
        $this->mode = 'landing';
        $this->dispatch('scroll-top');
    }

       protected function resetProgress(bool $clearAll = true): void
    {
        if ($clearAll) {
            $this->reset([
                'serviceId','counterId','staffId','score','comment',
                'staffOptions','counters',
                // tambahan:
                'staffQueue','staffIdx','multiFlowTotal',
            ]);
            $this->comment = '';
        } else {
            $this->score   = null;
            $this->comment = '';
            $this->staffOptions = [];
            // tambahan:
            $this->staffQueue = [];
            $this->staffIdx = 0;
            $this->multiFlowTotal = null;
        }
    }
        // === Kapan auto dua-form aktif? Sesuaikan rule di sini biar fleksibel.
    protected function shouldMultiFlow(): bool
    {
        if (!$this->serviceId || !$this->counterId) return false;
        if (!is_array($this->staffOptions) || count($this->staffOptions) < 2) return false;

        // Rule layanan (pakai nama; idealnya pakai ID/slug kalau ada)
        $targetServiceNames = [
            'Akta Perkawinan/Perceraian-KK-KTP',
        ];

        $svc = collect($this->services)->firstWhere('id', (int)$this->serviceId);
        $serviceOk = $svc && in_array(trim($svc['name'] ?? ''), $targetServiceNames, true);

        // Loket 2
        $counterOk = ((int)$this->counterId === 2);

        return $serviceOk && $counterOk;
    }



    /** ===== Actions per stage ===== */

    public function selectService(int $id): void
    {
        $this->serviceId = $id;
        $this->reloadCounters(); // kunci: filter loket sesuai layanan
    }

       public function selectCounter(int $id): void
    {
        $this->counterId = $id;
        // reset downstream
        $this->staffId = null;
        $this->score   = null;
        $this->comment = '';
        $this->loadStaffOptions();

        // === Tambahan: kalau shouldMultiFlow → rakit antrean & langsung mulai
        if ($this->shouldMultiFlow()) {
            // antrean sesuai urutan staffOptions yang udah lo sort di loadStaffOptions (by primary, starts_at)
            $this->staffQueue = array_values(array_map(fn($s) => (int)$s['id'], $this->staffOptions));
            $this->multiFlowTotal = count($this->staffQueue);

            $this->staffIdx = 0;
            $this->staffId  = $this->staffQueue[0];

            // ga perlu set stage, computed property bakal otomatis: staffId ada, score null → stage 4
        }
    }

        protected function persistRating(): void
    {
        // throttle
        $key = sprintf('peka:%s:%s', sha1((string)request()->ip()), substr(session()->getId(), -8));
        if (RateLimiter::tooManyAttempts($key, 1)) {
            throw ValidationException::withMessages(['score' => 'Terlalu cepat. Coba lagi.']);
        }
        RateLimiter::hit($key, 3);

        $this->validate();

        // hardening: pastikan counter melayani service yang dipilih
        $served = Counter::whereKey($this->counterId)
            ->whereHas('services', fn($q)=>$q->whereKey($this->serviceId))
            ->exists();

        if (! $served) {
            throw ValidationException::withMessages([
                'counterId' => 'Loket tidak melayani layanan ini.',
            ]);
        }

        // lock sederhana anti double-click
        $lockKey = sprintf('peka:lock:%s', $key);
        $lock = Cache::lock($lockKey, 3);
        if (!$lock->get()) {
            throw ValidationException::withMessages(['score' => 'Sedang diproses.']);
        }

        try {
            DB::transaction(function () {
                Rating::create([
                    'service_id' => (int)$this->serviceId,
                    'counter_id' => (int)$this->counterId,
                    'staff_id'   => (int)$this->staffId,
                    'score'      => (int)$this->score,
                    'comment'    => trim($this->comment) ?: null,
                ]);
            });
        } finally {
            optional($lock)->release();
        }
    }


    public function selectStaff(int $id): void
    {
        $this->staffId = $id;
        $this->score   = null;
        $this->comment = '';
    }

    public function updatedServiceId(): void
    {
        $this->selectService((int) $this->serviceId);
    }

    public function updatedCounterId(): void
    {
        $this->selectCounter((int) $this->counterId);
    }

    public function updatedStaffId(): void
    {
        $this->selectStaff((int) $this->staffId);
    }

    private function reloadCounters(): void
    {
        if (!$this->serviceId) { $this->counters = []; return; }

        $this->counters = Counter::query()
            ->whereHas('services', fn($q) => $q->whereKey($this->serviceId))
            ->orderBy('name')
            ->get(['id','name'])
            ->toArray();

        // reset downstream
        $this->counterId = null;
        $this->staffId   = null;
        $this->score     = null;
        $this->comment   = '';
        $this->staffOptions = [];

        // UX: auto-select kalau cuma 1 loket; lanjut auto kalau cuma 1 staff
        if (count($this->counters) === 1) {
            $this->selectCounter((int) $this->counters[0]['id']);
            if (count($this->staffOptions) === 1) {
                $this->selectStaff((int) $this->staffOptions[0]['id']);
            }
        }
    }

    private function loadStaffOptions(): void
    {
        if (!$this->counterId) { $this->staffOptions = []; return; }

        $now = now('Asia/Jakarta');
        $assignments = StaffAssignment::with('staff')
            ->where('counter_id', $this->counterId)
            ->where('starts_at','<=',$now)
            ->where(fn($q)=>$q->whereNull('ends_at')->orWhere('ends_at','>=',$now))
            ->orderByDesc('is_primary')
            ->orderByDesc('starts_at')
            ->get();

        $this->staffOptions = $assignments->pluck('staff')
            ->unique('id')
            ->map(fn($s)=> [
                'id'        => $s->id,
                'name'      => $s->name,
                'photo_url' => $s->photo_url,
            ])
            ->values()->toArray();

        if (count($this->staffOptions) === 1) {
            $this->staffId = $this->staffOptions[0]['id'];
        }
    }

    public function backToStart(): void
    {
        $this->reset(['serviceId','counterId','staffId','score','comment','staffOptions']);
        $this->counters = [];
    }

        public function submit(): void
    {
        // Simpan penilaian untuk staff saat ini
        $this->persistRating();

        $isMulti = !empty($this->staffQueue) && $this->multiFlowTotal && $this->multiFlowTotal >= 2;

        if ($isMulti && ($this->staffIdx + 1) < count($this->staffQueue)) {
            // Masih ada petugas berikutnya → lanjut tanpa balik landing
            $this->staffIdx++;
            $this->staffId = $this->staffQueue[$this->staffIdx];

            // reset input untuk form berikutnya
            $this->score = null;
            $this->comment = '';

            // Tetap di mode form; stage otomatis balik ke 4
            // Optional: kasih flash kecil biar user ngerti lagi lanjut
            // session()->flash('ok', 'Lanjut menilai petugas berikutnya.');
            $this->dispatch('scroll-top');
            return;
        }

        // Udah terakhir atau bukan multi-flow → akhiri seperti biasa
        $this->backToStart();
        $this->mode = 'landing';
        session()->flash('ok','Terima kasih! Penilaian Anda tercatat.');
        $this->dispatch('scroll-top');
    }


    public function render()
    {
        return view('livewire.peka');
    }
}
