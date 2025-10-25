<?php

namespace App\Filament\Widgets;

use App\Models\Rating;
use App\Models\Staff;
use App\Filament\Pages\StaffOverallLeaderboard;
use Filament\Widgets\ChartWidget;

class StaffLeaderboard extends ChartWidget
{
    // NON-STATIC, jangan pakai "static" di sini
    protected ?string $heading = 'Leaderboard Petugas (Top 5)';
    protected string $color = 'primary'; // juga non-static

    protected function getData(): array
{
    $from = now()->subDays(30);

    $leaders = \App\Models\Rating::query()
        ->whereNotNull('staff_id')
        ->where('created_at', '>=', $from)
        ->selectRaw('staff_id, AVG(score) as avg_score, COUNT(*) as total')
        ->groupBy('staff_id')
        ->having('total', '>=', 3)          // minimal 3 vote biar fair
        ->orderByDesc('avg_score')
        ->orderByDesc('total')
        ->limit(5)
        ->get();

    if ($leaders->isEmpty()) {
        return ['labels' => [], 'datasets' => [['label' => 'Avg Score', 'data' => []]]];
    }

    $names = \App\Models\Staff::whereIn('id', $leaders->pluck('staff_id'))->pluck('name', 'id');

    return [
        'labels' => $leaders->map(fn($r) => $names[$r->staff_id] ?? ('ID '.$r->staff_id))->all(),
        'datasets' => [[
            'label' => 'Avg Score',
            'data'  => $leaders->pluck('avg_score')->map(fn($v) => round((float)$v, 2))->all(),
        ]],
    ];
}

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        // Biar gaya leaderboard (horizontal bar)
        return [
            'indexAxis' => 'y',
            'scales' => [
                'x' => ['beginAtZero' => true, 'suggestedMax' => 5],
            ],
        ];
    }
    protected function getFooter(): ?string
{
    $url = StaffOverallLeaderboard::getUrl(); // link ke page sidebar
    return <<<HTML
    <div class="px-4 pb-4">
        <a href="{$url}" class="fi-btn fi-btn-size-sm fi-color-primary">Lihat semua</a>
    </div>
    HTML;
}
}
