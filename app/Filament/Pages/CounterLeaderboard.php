<?php

namespace App\Filament\Pages;

use App\Models\Counter;
use App\Models\Rating;
use Filament\Forms\Components\Select as SelectField;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class CounterLeaderboard extends Page implements HasTable
{
    use InteractsWithTable;

    // ===== Page meta =====
    protected ?string $heading = 'Leaderboard Loket';
    protected string $view = 'filament.pages.counter-leaderboard';

    // v4: pakai properties
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';
    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';
    protected static ?string $navigationLabel = 'Leaderboard Loket';

    /**
     * Query agregasi per loket:
     * - Bayes score: (n*avg + m*C) / (n+m)
     * - % toxic (score <= 2)
     * - Net satisfaction (promoter - detractor)
     *
     * @param  array{from:\DateTimeInterface|string,to:\DateTimeInterface|string}|null  $range
     * @param  int|null $minVotes
     * @param  int $m  strength parameter untuk Bayes prior
     */
    protected function bayesianCounterQuery(?array $range = null, ?int $minVotes = null, int $m = 10)
    {
        // Global mean (C)
        $global = Rating::query();
        if ($range) {
            $global->whereBetween('created_at', [$range['from'], $range['to']]);
        }
        $C = (float) ($global->avg('score') ?? 0);

        // Subquery aggregate
        $agg = Rating::query()
            ->select('counter_id')
            ->when($range, fn ($q) => $q->whereBetween('created_at', [$range['from'], $range['to']]))
            ->selectRaw('COUNT(*) as ratings_count')
            ->selectRaw('ROUND(AVG(score), 4) as ratings_avg_score')
            ->selectRaw('SUM(CASE WHEN score <= 2 THEN 1 ELSE 0 END) as detractors')
            ->selectRaw('SUM(CASE WHEN score >= 4 THEN 1 ELSE 0 END) as promoters')
            ->groupBy('counter_id')
            ->when($minVotes, fn ($q) => $q->havingRaw('COUNT(*) >= ?', [$minVotes]));

        $q = Counter::query()->select('counters.*');

        // INNER JOIN kalau minVotes aktif, LEFT JOIN kalau tidak
        if ($minVotes) {
            $q->joinSub($agg, 'r', 'r.counter_id', '=', 'counters.id')
              ->whereRaw('COALESCE(r.ratings_count, 0) >= ?', [$minVotes]); // guard ekstra
        } else {
            $q->leftJoinSub($agg, 'r', 'r.counter_id', '=', 'counters.id');
        }

        return $q
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

    /** Ambil state filter dari tabel (dipakai di query & view) */
    protected function filters(): array
    {
        $filters = $this->getTableFilterState('table') ?? [];

        $range = null;
        if (($filters['7d']['isActive'] ?? false) === true) {
            $range = ['from' => now()->subDays(7), 'to' => now()];
        } elseif (($filters['30d']['isActive'] ?? false) === true) {
            $range = ['from' => now()->subDays(30), 'to' => now()];
        } elseif (($filters['90d']['isActive'] ?? false) === true) {
            $range = ['from' => now()->subDays(90), 'to' => now()];
        }

        // KUNCI: cek isActive, bukan sekadar !empty
        $minVotes = (($filters['min5']['isActive'] ?? false) === true) ? 5 : null;

        // UI-only Select
        $mode = $filters['mode']['value'] ?? 'best';

        return [$range, $minVotes, $mode];
    }

    /** Data ringkasan ke Blade view (podium: best & toxic) */
    protected function getViewData(): array
    {
        [$range, $minVotes, $mode] = $this->filters();

        $q = $this->bayesianCounterQuery($range, $minVotes);

        $best = (clone $q)
            ->orderByDesc(DB::raw('(COALESCE(ratings_count,0) > 0)'))
            ->orderByDesc('bayes_score')
            ->orderByDesc('ratings_count')
            ->limit(3)
            ->get([
                'id', 'name',
                DB::raw('COALESCE(ratings_avg_score,0) as avg'),
                DB::raw('COALESCE(ratings_count,0) as cnt'),
                DB::raw('bayes_score as bayes'),
                DB::raw('COALESCE(detractor_pct,0) as detractor_pct'),
            ]);

        $minForToxic = $minVotes ?? 5;
        $toxic = (clone $this->bayesianCounterQuery($range, $minForToxic))
            ->orderByDesc(DB::raw('(COALESCE(ratings_count,0) > 0)'))
            ->orderByDesc('detractor_pct')
            ->orderByDesc('ratings_count')
            ->limit(3)
            ->get([
                'id', 'name',
                DB::raw('COALESCE(ratings_avg_score,0) as avg'),
                DB::raw('COALESCE(ratings_count,0) as cnt'),
                DB::raw('COALESCE(detractor_pct,0) as detractor_pct'),
                DB::raw('bayes_score as bayes'),
            ]);

        return compact('best', 'toxic', 'mode');
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
                        ->orderByDesc(DB::raw('(COALESCE(ratings_count,0) > 0)'))
                        ->orderByDesc('detractor_pct')
                        ->orderByDesc('ratings_count');
                }

                return $q
                    ->orderByDesc(DB::raw('(COALESCE(ratings_count,0) > 0)'))
                    ->orderByDesc('bayes_score')
                    ->orderByDesc('ratings_count');
            })
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->rowIndex()
                    ->badge()
                    ->color(fn ($state) => match ((int) $state) {
                        1 => 'warning',
                        2 => 'gray',
                        3 => 'danger',
                        default => 'info',
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('Loket')
                    ->searchable()
                    ->sortable(),

                // Visual bar via Blade view kustom
                Tables\Columns\ViewColumn::make('bayes_score')
                    ->label('Skor Terpercaya')
                    ->view('components.leaderboard-score-bar'),

                // Angka murni (hidden by default)
                Tables\Columns\TextColumn::make('bayes_score_numeric')
                    ->label('Skor Terpercaya')
                    ->state(fn ($record) => number_format((float) ($record->bayes_score ?? 0), 3))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ratings_avg_score')
                    ->label('Avg (raw)')
                    ->formatStateUsing(fn ($s) => number_format((float) $s, 2))
                    ->tooltip(fn ($r) => $r?->ratings_count ? "Votes: {$r->ratings_count}" : null)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ratings_count')
                    ->label('Votes')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('detractor_pct')
                    ->label('% Toxic (≤2)')
                    ->formatStateUsing(fn ($v) => is_null($v) ? '-' : number_format((float) $v, 1) . '%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('net_sat')
                    ->label('Net Sat (Promoter−Detractor)')
                    ->formatStateUsing(fn ($v) => is_null($v) ? '-' : number_format((float) $v, 1) . '%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Toggle sederhana — UI only (no-op queries)
                Tables\Filters\Filter::make('7d')
                    ->label('7 hari')
                    ->query(fn ($q) => $q),

                Tables\Filters\Filter::make('30d')
                    ->label('30 hari')
                    ->query(fn ($q) => $q),

                Tables\Filters\Filter::make('90d')
                    ->label('90 hari')
                    ->query(fn ($q) => $q),

                Tables\Filters\Filter::make('min5')
                    ->label('Min 5 votes')
                    ->query(fn ($q) => $q),

                // Mode: best / toxic (UI-only select di filter panel)
                Tables\Filters\Filter::make('mode')
                    ->form([
                        SelectField::make('value')
                            ->label('Mode')
                            ->options([
                                'best'  => 'Terbaik',
                                'toxic' => 'Toxic',
                            ])
                            ->default('best')
                            ->native(false),
                    ])
                    ->query(fn ($q, array $data) => $q) // no-op, state dibaca manual
                    ->indicateUsing(fn (array $data) => ($data['value'] ?? 'best') === 'toxic'
                        ? 'Mode: Toxic'
                        : 'Mode: Terbaik'),
            ])
            ->persistFiltersInSession()
            ->deferFilters(false) // apply langsung
            ->emptyStateHeading('Belum ada data')
            ->emptyStateDescription('Ubah periode atau longgarkan filter minimum votes.')
            ->paginationPageOptions([10, 20, 50])
            ->defaultPaginationPageOption(20);
    }
}
