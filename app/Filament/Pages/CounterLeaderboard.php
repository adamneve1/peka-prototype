<?php

namespace App\Filament\Pages;

use App\Models\Rating;
use App\Models\Counter;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Facades\DB;

class CounterLeaderboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected ?string $heading = 'Leaderboard Loket';
    // IMPORTANT: non-static
    protected string $view = 'filament.pages.counter-leaderboard';

    // Nav signatures harus match parent (Filament v4)
    public static function getNavigationIcon(): \BackedEnum|string|null
    {
        return 'heroicon-o-flag';
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        return 'Monitoring';
    }

    public static function getNavigationLabel(): string
    {
        return 'Leaderboard Loket';
    }

    /**
     * Aggregasi per-loket:
     * - Bayes score (fairness)
     * - % toxic (score <= 2)
     * - Net satisfaction (promoter - detractor)
     */
    protected function bayesianCounterQuery(?array $range = null, ?int $minVotes = null, int $m = 10)
    {
        // C = mean global (semua loket) dalam periode
        $global = Rating::query();
        if ($range) {
            $global->whereBetween('created_at', [$range['from'], $range['to']]);
        }
        $C = (float) ($global->avg('score') ?? 0);

        // Aggregasi rating per loket
        $agg = Rating::query()
            ->select('counter_id')
            ->when($range, fn ($q) => $q->whereBetween('created_at', [$range['from'], $range['to']]))
            ->selectRaw('COUNT(*) as ratings_count')
            ->selectRaw('ROUND(AVG(score), 4) as ratings_avg_score')
            ->selectRaw('SUM(CASE WHEN score <= 2 THEN 1 ELSE 0 END) as detractors')
            ->selectRaw('SUM(CASE WHEN score >= 4 THEN 1 ELSE 0 END) as promoters')
            ->groupBy('counter_id')
            ->when($minVotes, fn ($q) => $q->havingRaw('COUNT(*) >= ?', [$minVotes]));

        return Counter::query()
            ->leftJoinSub($agg, 'r', 'r.counter_id', '=', 'counters.id')
            ->select('counters.*')
            ->addSelect([
                DB::raw('COALESCE(r.ratings_count, 0) as ratings_count'),
                DB::raw('COALESCE(r.ratings_avg_score, 0) as ratings_avg_score'),
                DB::raw('COALESCE(r.detractors, 0) as detractors'),
                DB::raw('COALESCE(r.promoters, 0) as promoters'),
            ])
            ->selectRaw(
                '( (COALESCE(r.ratings_count,0) * COALESCE(r.ratings_avg_score,0) + ? * ?) / NULLIF(COALESCE(r.ratings_count,0) + ?, 0) ) as bayes_score',
                [$m, $C, $m]
            )
            ->selectRaw('(COALESCE(r.detractors,0) / NULLIF(COALESCE(r.ratings_count,0),0)) * 100 as detractor_pct')
            ->selectRaw('(COALESCE(r.promoters,0) / NULLIF(COALESCE(r.ratings_count,0),0)) * 100 as promoter_pct')
            ->selectRaw('((COALESCE(r.promoters,0) - COALESCE(r.detractors,0)) / NULLIF(COALESCE(r.ratings_count,0),0)) * 100 as net_sat');
    }

    /** Baca filter dari tabel */
   protected function filters(): array
{
    $filters = $this->getTableFilterState('table') ?? [];

    $range = null;
    if (!empty($filters['7d'])) {
        $range = ['from' => now()->subDays(7), 'to' => now()];
    } elseif (!empty($filters['30d'])) {
        $range = ['from' => now()->subDays(30), 'to' => now()];
    } elseif (!empty($filters['90d'])) {
        $range = ['from' => now()->subDays(90), 'to' => now()];
    }

    $minVotes = !empty($filters['min5']) ? 5 : null;

    // ⬇️ sebelumnya: $mode = $filters['mode'] ?? 'best';
    $mode = $filters['mode']['value'] ?? 'best';

    return [$range, $minVotes, $mode];
}


    /** Data ringkas buat podium (top & toxic) */
    protected function getViewData(): array
    {
        [$range, $minVotes, $mode] = $this->filters();

        $q = $this->bayesianCounterQuery($range, $minVotes);

        $best = (clone $q)
            ->orderByDesc(DB::raw('COALESCE(ratings_count,0) > 0'))
            ->orderByDesc('bayes_score')
            ->orderByDesc('ratings_count')
            ->limit(3)
            ->get([
                'id','name',
                DB::raw('COALESCE(ratings_avg_score,0) as avg'),
                DB::raw('COALESCE(ratings_count,0) as cnt'),
                DB::raw('bayes_score as bayes'),
                DB::raw('COALESCE(detractor_pct,0) as detractor_pct'),
            ]);

        $minForToxic = $minVotes ?? 5;
        $toxic = (clone $this->bayesianCounterQuery($range, $minForToxic))
            ->orderByDesc(DB::raw('COALESCE(ratings_count,0) > 0'))
            ->orderByDesc('detractor_pct')
            ->orderByDesc('ratings_count')
            ->limit(3)
            ->get([
                'id','name',
                DB::raw('COALESCE(ratings_avg_score,0) as avg'),
                DB::raw('COALESCE(ratings_count,0) as cnt'),
                DB::raw('COALESCE(detractor_pct,0) as detractor_pct'),
                DB::raw('bayes_score as bayes'),
            ]);

        return compact('best','toxic','mode');
    }

    /** Tabel utama: daftar loket + metrik reputasi */
    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                [$range, $minVotes, $mode] = $this->filters();
                $q = $this->bayesianCounterQuery($range, $minVotes);

                if ($mode === 'toxic') {
                    return $q
                        ->orderByDesc(DB::raw('COALESCE(ratings_count,0) > 0'))
                        ->orderByDesc('detractor_pct')
                        ->orderByDesc('ratings_count');
                }

                return $q
                    ->orderByDesc(DB::raw('COALESCE(ratings_count,0) > 0'))
                    ->orderByDesc('bayes_score')
                    ->orderByDesc('ratings_count');
            })
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')->rowIndex()->badge()
                    ->color(fn ($i) => match ((int)$i) {1=>'warning',2=>'gray',3=>'danger', default=>'info'}),

                Tables\Columns\TextColumn::make('name')
                    ->label('Loket')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\ViewColumn::make('bayes_score')
                    ->label('Skor Terpercaya')
                    ->view('components.leaderboard-score-bar'),

                Tables\Columns\TextColumn::make('bayes_score_numeric')
                    ->label('Skor Terpercaya')
                       ->state(fn ($record) => (float) ($record->bayes_score ?? 0))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ratings_avg_score')
                    ->label('Avg (raw)')
                    ->formatStateUsing(fn ($s) => number_format((float)$s, 2))
        ->tooltip(fn ($record) => $record?->ratings_count ? "Votes: {$record->ratings_count}" : null)

                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ratings_count')
                    ->label('Votes')->badge()->sortable(),

                Tables\Columns\TextColumn::make('detractor_pct')
                    ->label('% Toxic (≤2)')
                    ->formatStateUsing(fn ($v) => is_null($v) ? '-' : number_format((float)$v, 1) . '%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('net_sat')
                    ->label('Net Sat (Promoter−Detractor)')
                    ->formatStateUsing(fn ($v) => is_null($v) ? '-' : number_format((float)$v, 1) . '%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
    Tables\Filters\Filter::make('7d')->label('7 hari'),
    Tables\Filters\Filter::make('30d')->label('30 hari'),
    Tables\Filters\Filter::make('90d')->label('90 hari'),
    Tables\Filters\Filter::make('min5')->label('Min 5 votes'),

    // ⬇️ GANTI SelectFilter mode -> Filter dengan Select form (UI-only)
    Tables\Filters\Filter::make('mode')
        ->form([
            \Filament\Forms\Components\Select::make('value')
                ->label('Mode')
                ->options([
                    'best'  => 'Terbaik',
                    'toxic' => 'Toxic',
                ])
                ->default('best')
                ->native(false),
        ])
        // penting: jangan ubah query apapun
        ->query(fn ($q, array $data) => $q)
        // optional: chip indikator di UI
        ->indicateUsing(fn (array $data) => match ($data['value'] ?? 'best') {
            'toxic' => 'Mode: Toxic',
            default => 'Mode: Terbaik',
        }),
])

            ->emptyStateHeading('Belum ada data')
            ->emptyStateDescription('Ubah periode atau longgarkan filter minimum votes.')
            ->paginationPageOptions([10, 20, 50])
            ->defaultPaginationPageOption(20);
    }
}
