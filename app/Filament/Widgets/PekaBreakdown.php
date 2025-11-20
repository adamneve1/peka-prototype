<?php

namespace App\Filament\Widgets;

use App\Models\Rating;
use App\Models\Service;
use App\Models\Counter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class PekaBreakdown extends ChartWidget
{
    // ↓↓ JANGAN static
    protected ?string $heading = 'Breakdown Penilaian (Donut)';

    // ↓↓ Ini juga non-static di v4
    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 4,
    ];

    // Default form/filter state
    protected function getFormModel(): string
    {
        return static::class;
    }

    public ?array $filters = [
        'group_by' => 'layanan',   // layanan | loket
        'days'     => 30,          // periode
        'top_n'    => 8,           // ambil top 8, sisanya "Lainnya"
    ];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Radio::make('filters.group_by')
                    ->label('Group by')
                    ->options([
                        'layanan' => 'Layanan',
                        'loket'   => 'Loket',
                    ])
                    ->inline()
                    ->default('layanan'),

                Forms\Components\TextInput::make('filters.days')
                    ->label('Periode (hari)')
                    ->numeric()->minValue(1)->maxValue(365)
                    ->default(30)
                    ->helperText('Hitung dari hari ini mundur ke belakang'),

                Forms\Components\TextInput::make('filters.top_n')
                    ->label('Top N kategori')
                    ->numeric()->minValue(3)->maxValue(20)
                    ->default(8)
                    ->helperText('Sisanya akan digabung sebagai “Lainnya”'),
            ])
            ->statePath('filters');
    }

    protected function getData(): array
{
    $groupBy = $this->filters['group_by'] ?? 'layanan';
    $days    = (int)($this->filters['days'] ?? 30);
    $topN    = (int)($this->filters['top_n'] ?? 8);

    if ($groupBy === 'loket') {
        // Per loket
        $rows = Rating::query()
            ->where('ratings.created_at', '>=', now()->subDays($days)) // ← qualify
            ->join('counters', 'counters.id', '=', 'ratings.counter_id')
            ->selectRaw('counters.name as name, COUNT(*) as total')
            ->whereNotNull('ratings.counter_id') // optional: skip null
            ->groupBy('name')                    // SQLite suka strict – group by alias oke
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['name' => $r->name ?? '—', 'total' => (int) $r->total]);
    } else {
        // Per layanan
        $rows = Rating::query()
            ->where('ratings.created_at', '>=', now()->subDays($days)) // ← qualify
            ->join('services', 'services.id', '=', 'ratings.service_id')
            ->selectRaw('services.name as name, COUNT(*) as total')
            ->whereNotNull('ratings.service_id') // optional: skip null
            ->groupBy('name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['name' => $r->name ?? '—', 'total' => (int) $r->total]);
    }

    $rows = collect($rows)->sortByDesc('total')->values();
    [$labels, $data] = $this->collapseTailToOthers($rows, $topN);

    return [
    'datasets' => [[
        'label'           => 'Jumlah Penilaian',
        'data'            => $data,
        'backgroundColor' => $this->palette(count($labels)), // kasih array warna
        'borderWidth'     => 0,
        'hoverOffset'     => 4,
    ]],
    'labels' => $labels,
];

}


    protected function getType(): string
    {
        return 'doughnut';
    }

    private function collapseTailToOthers(Collection $rows, int $topN): array
    {
        if ($rows->count() <= $topN) {
            return [
                $rows->pluck('name')->all(),
                $rows->pluck('total')->all(),
            ];
        }

        $head = $rows->take($topN);
        $tail = $rows->slice($topN);

        $labels = $head->pluck('name')->all();
        $data   = $head->pluck('total')->all();

        $others = $tail->pluck('total')->sum();
        if ($others > 0) {
            $labels[] = 'Lainnya';
            $data[]   = $others;
        }

        return [$labels, $data];
    }
    private function palette(int $n): array
{
    // palet dasar pakai warna brand + kontras
    $base = [
        '#1E40AF', // biru
        '#F59E0B', // amber
        '#F97316', // orange
        '#10B981', // hijau emerald
        '#EF4444', // merah
        '#06B6D4', // cyan
        '#8B5CF6', // violet
        '#84CC16', // lime
        '#FB7185', // rose
        '#14B8A6', // teal
        '#A855F7', // purple
        '#F43F5E', // pink
    ];

    if ($n <= count($base)) {
        return array_slice($base, 0, $n);
    }

    // kalau kategori lebih banyak dari base, generate HSL secara evenly spaced
    $colors = $base;
    for ($i = count($base); $i < $n; $i++) {
        $h = intval(($i * (360 / $n)) % 360);
        $s = 70; $l = 50;
        $colors[] = "hsl($h, {$s}%, {$l}%)";
    }

    return $colors;
}


}
