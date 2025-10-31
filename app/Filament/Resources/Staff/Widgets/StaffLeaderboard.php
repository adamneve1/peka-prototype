<?php

namespace App\Filament\Widgets;

use App\Models\Rating;
use App\Models\Staff;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class StaffLeaderboard extends ChartWidget
{
    protected ?string $heading = 'Leaderboard Petugas (Top 5)';
    protected string $color = 'primary';

    /**
     * Keep this in sync with the Page filters if you want:
     * - null  => overall (no date filter)
     * - 7     => last 7 days
     * - 30    => last 30 days
     */
    protected ?int $rangeDays = null; // set to 30 if you want the old behavior

    /**
     * Minimum votes (null to disable). Page uses a toggle "min5".
     */
    protected ?int $minVotes = null; // set to 5 if you want to mirror page toggle

    /**
     * Bayesian prior strength (m). Same default as page.
     */
    protected int $m = 10;

    protected function getData(): array
    {
        // Timeframe range like the page does
        $range = null;
        if (!is_null($this->rangeDays) && $this->rangeDays > 0) {
            $range = ['from' => now()->subDays($this->rangeDays), 'to' => now()];
        }

        // Global mean C for the selected timeframe (same as page)
        $global = Rating::query();
        if ($range) {
            $global->whereBetween('created_at', [$range['from'], $range['to']]);
        }
        $C = (float) ($global->avg('score') ?? 0);

        // Subquery aggregate per staff (mirror page logic; keep ROUND if your page still rounds)
        $agg = Rating::query()
            ->select('staff_id')
            ->when($range, fn ($q) => $q->whereBetween('created_at', [$range['from'], $range['to']]))
            ->selectRaw('COUNT(*) as ratings_count')
            ->selectRaw('ROUND(AVG(score), 2) as ratings_avg_score') // match page
            ->groupBy('staff_id')
            ->when($this->minVotes, fn ($q) => $q->havingRaw('COUNT(*) >= ?', [$this->minVotes]));

        // Build main query exactly like the page ordering
        $leaders = Staff::query()
            ->leftJoinSub($agg, 'r', 'r.staff_id', '=', 'staff.id')
            ->select('staff.id', 'staff.name')
            ->addSelect([
                DB::raw('COALESCE(r.ratings_count, 0) as ratings_count'),
                DB::raw('COALESCE(r.ratings_avg_score, 0) as ratings_avg_score'),
                DB::raw('CASE WHEN COALESCE(r.ratings_count,0) > 0 THEN 1 ELSE 0 END as has_ratings'),
            ])
            ->selectRaw(
                '( (COALESCE(r.ratings_count,0) * COALESCE(r.ratings_avg_score,0) + ? * ?) / NULLIF(COALESCE(r.ratings_count,0) + ?, 0) ) as bayes_score',
                [$this->m, $C, $this->m]
            )
            // same ordering as page: rated first, then bayes, then volume
            ->orderByDesc('has_ratings')
            ->orderByDesc('bayes_score')
            ->orderByDesc('ratings_count')
            ->limit(5)
            ->get();

        if ($leaders->isEmpty()) {
            return [
                'labels' => [],
                'datasets' => [[
                    'label' => 'Bayes Score',
                    'data' => [],
                    'backgroundColor' => [],
                    'borderWidth' => 0,
                ]],
            ];
        }

        // === Perubahan utama: warna per-bar ===
        $labels  = $leaders->pluck('name')->all();
        $data    = $leaders->pluck('bayes_score')->map(fn ($v) => round((float) $v, 2))->all();

        // Palet ala Bottom 5 (solid, bukan gradient)
        $palette = ['#EF4444','#F97316','#F59E0B','#FB923C','#FDBA74'];
        $colors  = array_slice($palette, 0, count($data));

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Bayes Score',
                'data'  => $data,
                'backgroundColor' => $colors,
                'borderWidth' => 0,
            ]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        // Horizontal bars, 0..5 like your page’s 5-point scale
        return [
            'indexAxis' => 'y',
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'suggestedMax' => 5,
                ],
            ],
            'plugins' => [
                'legend' => ['display' => true],
                'tooltip' => ['enabled' => true],
            ],
        ];
    }

    protected function getFooter(): ?string
    {
        // If you have a Filament page route helper:
        $url = \App\Filament\Pages\StaffOverallLeaderboard::getUrl();

        return <<<HTML
        <div class="px-4 pb-4">
            <a href="{$url}" class="fi-btn fi-btn-size-sm fi-color-primary">Lihat semua</a>
        </div>
        HTML;
    }
}
