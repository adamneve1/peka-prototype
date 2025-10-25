<?php

namespace App\Livewire\Kiosk;

use Livewire\Component;
use App\Models\{Counter, Service, Staff, StaffAssignment, Rating, VisitToken};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Rate extends Component
{
    /** UI flow */
    public int $step = 1;

    /** Form state (pakai int nullable biar validasi & casting bersih) */
    public ?int $counterId = null;
    public ?int $serviceId = null;
    public ?int $score     = null;
    public string $comment = '';

    /** UX: filter loket */
    public string $counterSearch = '';

    /** Data untuk tampilan */
    public array $counters = [];
    public array $services = [];
    public ?Staff $activeStaff = null;

    /** Prefill dari token kunjungan (opsional) */
    protected ?VisitToken $visitToken = null;

    /** Rules + messages konsisten */
    protected function rules(): array
    {
        return [
            'counterId' => ['required','integer','exists:counters,id'],
            'serviceId' => ['required','integer','exists:services,id'],
            'score'     => ['required','integer','min:1','max:5'],
            'comment'   => ['nullable','string','max:280'], // samain ke 280
        ];
    }

    protected array $messages = [
        'counterId.required' => 'Pilih loket dulu.',
        'serviceId.required' => 'Pilih layanan dulu.',
        'score.required'     => 'Pilih nilai 1–5.',
    ];

    public function mount(): void
    {
        // Ambil list untuk UI (urut nama biar enak discan)
        $this->counters = Counter::orderBy('name')->get(['id','name'])->toArray();
        $this->services = Service::orderBy('name')->get(['id','name'])->toArray();

        // Prefill dari token kalau route/middleware nyelipin ke request attribute
        $vt = request()->attributes->get('visit_token');
        if ($vt instanceof VisitToken) {
            $this->visitToken = $vt;
            $this->counterId = $vt->counter_id;
            $this->resolveActiveStaff();
            if ($vt->service_id) $this->serviceId = $vt->service_id;
            // staff_id dari token opsional; aktifStaff tetep di-resolve by assignment
            $this->step = $this->counterId ? 2 : 1;
        }
    }

    public function selectCounter(int $id): void
    {
        $this->counterId = $id;
        $this->serviceId = null;
        $this->score     = null;
        $this->comment   = '';
        $this->resolveActiveStaff();
        $this->step = 2;
    }

    public function backToCounters(): void
    {
        $this->reset(['counterId','serviceId','score','comment','activeStaff','counterSearch']);
        $this->step = 1;
    }

    public function updatedCounterId(): void
    {
        if (!$this->counterId) { // kalau dikosongin manual
            $this->backToCounters();
            return;
        }
        $this->serviceId = null;
        $this->score     = null;
        $this->comment   = '';
        $this->resolveActiveStaff();
    }

    public function updatedServiceId($val): void
    {
        $this->score   = null;
        $this->comment = '';
    }

    private function resolveActiveStaff(): void
    {
        if (!$this->counterId) { $this->activeStaff = null; return; }

        $now = Carbon::now('Asia/Jakarta');

        // Pilih assignment yang lagi aktif; kalau ada beberapa, prioritaskan yang is_primary terbaru
        $staffId = StaffAssignment::where('counter_id', $this->counterId)
            ->where('starts_at', '<=', $now)
            ->where(fn($q)=>$q->whereNull('ends_at')->orWhere('ends_at','>=',$now))
            ->orderByDesc('is_primary')   // butuh kolom is_primary? kalau belum ada bisa dihapus
            ->orderByDesc('starts_at')
            ->value('staff_id');

        $this->activeStaff = $staffId ? Staff::find($staffId) : null;
    }

    public function submit(): void
    {
        // Rate limit: 1 submit / 3 detik per IP+session biar anti spam/double-click
        $key = sprintf('kiosk:%s:%s', sha1((string)request()->ip()), substr(session()->getId(), -8));
        if (! RateLimiter::attempt($key, $perMinute = 20, fn() => null, $decay = 3)) {
            throw ValidationException::withMessages([
                'score' => 'Terlalu cepat. Coba lagi sebentar.',
            ]);
        }

        $this->validate();

        // Lock singkat buat cegah double-click overlapping request
        $lock = Cache::lock('kiosk:lock:'.$key, 5);
        if (! $lock->get()) {
            throw ValidationException::withMessages([
                'score' => 'Sedang diproses. Jangan klik berkali-kali.',
            ]);
        }

        try {
            DB::transaction(function () {
                $rating = Rating::create([
                    'counter_id' => (int) $this->counterId,
                    'service_id' => (int) $this->serviceId,
                    'staff_id'   => optional($this->activeStaff)->id,
                    'score'      => (int) $this->score,
                    'comment'    => trim($this->comment) ?: null,
                ]);

                // Kalau pakai visit token, tandai sekali pakai
                if ($this->visitToken) {
                    $this->visitToken->update(['used_at' => now()]);
                }
            });
        } finally {
            optional($lock)->release();
        }

        // Reset ke layar awal
        $this->reset(['counterId','serviceId','score','comment','activeStaff','counterSearch']);
        $this->step = 1;
        session()->flash('ok','Terima kasih! Penilaian Anda tercatat.');
    }

    public function render()
    {
        if ($this->step === 2 && ! $this->counterId) {
            $this->step = 1;
        }

        return view('livewire.kiosk.rate');
    }
}
