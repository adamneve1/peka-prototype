<?php

namespace App\Filament\Widgets;

use App\Models\Rating;
use App\Models\Staff;
use App\Filament\Pages\StaffOverallLeaderboard;
use Filament\Widgets\ChartWidget;

class StaffBottom5Chart extends ChartWidget
{
    protected ?string $heading = 'Leaderboard Petugas (Bottom 5)';
    protected string $color = 'danger';
    protected ?string $maxHeight = null;

  //  protected int|string|array $columnSpan = ['md' => 6, 'xl' => 4];
   // protected ?string $maxHeight = '240px'; // non-static

    protected function getData(): array
    {
        $from = now()->subDays(30);

        $rows = Rating::query()
            ->whereNotNull('staff_id')
            ->where('created_at', '>=', $from)
            ->selectRaw('staff_id, AVG(score) as avg_score, COUNT(*) as total')
            ->groupBy('staff_id')
            ->having('total', '>=', 3)
            ->orderBy('avg_score', 'asc')      // paling rendah dulu
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'labels' => [],
                'datasets' => [[ 'label' => 'Avg Score', 'data' => [] ]],
            ];
        }

        $names = Staff::whereIn('id', $rows->pluck('staff_id'))->pluck('name', 'id');

        return [
            'labels' => $rows->map(fn($r) => $names[$r->staff_id] ?? ('ID '.$r->staff_id))->all(),
            'datasets' => [[
                'label' => 'Avg Score',
                'data'  => $rows->pluck('avg_score')->map(fn($v) => round((float)$v, 2))->all(),
                'backgroundColor' => ['#EF4444','#F97316','#F59E0B','#FB923C','#FDBA74'],
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
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => ['beginAtZero' => true, 'suggestedMax' => 5],
            ],
        ];
    }

    protected function getFooter(): ?string
    {
        $url = StaffOverallLeaderboard::getUrl();
        return <<<HTML
        <div class="px-4 pb-4">
            <a href="{$url}" class="fi-btn fi-btn-size-sm fi-color-danger">Lihat semua</a>
        </div>
        HTML;
    }
}
