<?php

namespace App\Filament\Pages;

use App\Models\Rating;
use App\Models\Staff;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter as TableFilter;
use Illuminate\Support\Carbon;

class StaffOverallLeaderboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected ?string $heading = 'Staff Leaderboard (Overall)';

    public static function getNavigationIcon(): string { return 'heroicon-o-trophy'; }
    public static function getNavigationGroup(): ?string { return 'Monitoring'; }
    public static function getNavigationLabel(): string { return 'Leaderboard Staff'; }

    public function mount(): void
    {
        // clear any stale leaderboard filter session when page mounts fresh
        session()->forget('lb_filters');
    }

    public function getView(): string
    {
        return 'filament.pages.staff-overall-leaderboard';
    }

    /**
     * Build Bayesian query.
     *
     * $range = ['from' => Carbon, 'to' => Carbon] or null
     * $minVotes = integer or null
     */
    protected function bayesianStaffQuery(?array $range = null, ?int $minVotes = null, int $m = 10)
    {
        // Global mean C within range (if provided)
        $global = Rating::query();
        if ($range) {
            $global->whereBetween('created_at', [$range['from'], $range['to']]);
        }
        $C = (float) ($global->avg('score') ?? 0);

        // Aggregation per staff, filtered by range and minVotes
        $agg = Rating::query()
            ->select('staff_id')
            ->when($range, fn ($q) => $q->whereBetween('created_at', [$range['from'], $range['to']]))
            ->selectRaw('COUNT(*) as ratings_count')
            ->selectRaw('ROUND(AVG(score), 2) as ratings_avg_score')
            ->groupBy('staff_id')
            ->when($minVotes, fn ($q) => $q->havingRaw('COUNT(*) >= ?', [$minVotes]));

        // Debug: log the subquery SQL
        logger()->debug('LB AGG SQL', ['sql' => $agg->toSql(), 'bindings' => $agg->getBindings()]);

        $staffQ = Staff::query()
            ->leftJoinSub($agg, 'r', 'r.staff_id', '=', 'staff.id')
            ->select('staff.*')
            ->addSelect([
                DB::raw('COALESCE(r.ratings_count, 0) as ratings_count'),
                DB::raw('COALESCE(r.ratings_avg_score, 0) as ratings_avg_score'),
                DB::raw('CASE WHEN COALESCE(r.ratings_count,0) > 0 THEN 1 ELSE 0 END as has_ratings'),
            ])
            ->selectRaw(
                '( (COALESCE(r.ratings_count,0) * COALESCE(r.ratings_avg_score,0) + ? * ?) / NULLIF(COALESCE(r.ratings_count,0) + ?, 0) ) as bayes_score',
                [$m, $C, $m]
            )
            ->orderByDesc('has_ratings')
            ->orderByDesc('bayes_score')
            ->orderByDesc('ratings_count');

        // Debug: final staff sql
        logger()->debug('LB STAFF SQL', ['sql' => $staffQ->toSql(), 'bindings' => $staffQ->getBindings()]);

        return $staffQ;
    }

    /** Data podium top-3 (respects filter state) */
    protected function getViewData(): array
    {
        // prefer table filter state; fallback to session snapshot
        $filters = $this->getTableFilterState('table') ?? session('lb_filters') ?? [];

        $range = $this->resolveRangeFromFilters($filters);
        $minVotes = !empty($filters['min5']) ? 5 : null;

        $podium = $this->bayesianStaffQuery($range, $minVotes)
            ->limit(3)
            ->get(['id', 'name', 'photo_path', 'ratings_avg_score', 'ratings_count', 'bayes_score'])
            ->map(fn ($s) => [
                'id'    => $s->id,
                'name'  => $s->name,
                'photo' => $s->photo_url,
                'avg'   => (float) ($s->ratings_avg_score ?? 0),
                'cnt'   => (int) ($s->ratings_count ?? 0),
                'bayes' => (float) ($s->bayes_score ?? 0),
            ])
            ->values();

        return compact('podium');
    }

    /**
     * Resolve range (from/to) from filter state.
     * Accepts the same shaped array as Filament's getTableFilterState OR our session snapshot.
     */
    protected function resolveRangeFromFilters(?array $filters): ?array
    {
        $filters = $filters ?? [];

        // explicit date_range priority
        if (!empty($filters['date_range']['from']) || !empty($filters['date_range']['to'])) {
            $from = !empty($filters['date_range']['from'])
                ? Carbon::parse($filters['date_range']['from'])->startOfDay()
                : null;
            $to = !empty($filters['date_range']['to'])
                ? Carbon::parse($filters['date_range']['to'])->endOfDay()
                : null;

            if ($from || $to) {
                $from ??= now()->subYears(10);
                $to   ??= now();
                return ['from' => $from, 'to' => $to];
            }
        }

        if (!empty($filters['7d'])) {
            return ['from' => now()->subDays(7)->startOfDay(), 'to' => now()->endOfDay()];
        }

        if (!empty($filters['30d'])) {
            return ['from' => now()->subDays(30)->startOfDay(), 'to' => now()->endOfDay()];
        }

        return null;
    }

    /** Filament table definition */
    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // try Filament state first; fallback to session snapshot
                $filters = $this->getTableFilterState('table') ?? session('lb_filters') ?? [];

                // Debug: log what we are going to use
                logger()->debug('LB EFFECTIVE FILTERS USED', $filters);

                $range = $this->resolveRangeFromFilters($filters);
                logger()->debug('LB RESOLVED RANGE', ['from' => $range['from'] ?? null, 'to' => $range['to'] ?? null]);

                $minVotes = !empty($filters['min5']) ? 5 : null;

                return $this->bayesianStaffQuery($range, $minVotes);
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

                Tables\Columns\ImageColumn::make('photo_url')
                    ->label('')
                    ->circular()->height(40)->width(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('Staff')
                    ->searchable()->sortable(),

                Tables\Columns\ViewColumn::make('bayes_score')
                    ->label('Score (Bayes)')
                    ->view('components.leaderboard-score-bar'),

                Tables\Columns\TextColumn::make('bayes_score_numeric')
                    ->label('Score (Bayes)')
                    ->state(fn ($record) => (float) $record->bayes_score)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ratings_avg_score')
                    ->label('Avg (raw)')
                    ->formatStateUsing(fn ($s) => number_format((float)$s, 2))
                    ->tooltip(fn ($record) => "Votes: {$record->ratings_count}")
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ratings_count')
                    ->label('Votes')
                    ->badge()
                    ->sortable(),
            ])

            ->filters([
                // Force filters to check ratings table existence in ranges,
                // AND write a session snapshot so bayesianStaffQuery can use the same range.
                TableFilter::make('7d')
                    ->label('7 hari')
                    ->query(function ($query) {
                        // save snapshot
                        session(['lb_filters' => ['7d' => true]]);
                        return $query->whereExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('ratings')
                                ->whereColumn('ratings.staff_id', 'staff.id')
                                ->where('ratings.created_at', '>=', now()->subDays(7)->startOfDay());
                        });
                    }),

                TableFilter::make('30d')
                    ->label('30 hari')
                    ->query(function ($query) {
                        session(['lb_filters' => ['30d' => true]]);
                        return $query->whereExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('ratings')
                                ->whereColumn('ratings.staff_id', 'staff.id')
                                ->where('ratings.created_at', '>=', now()->subDays(30)->startOfDay());
                        });
                    }),

                TableFilter::make('min5')
                    ->label('Min 5 votes')
                    ->query(function ($query) {
                        // preserve other snapshot keys if exist
                        $snap = session('lb_filters', []);
                        $snap['min5'] = true;
                        session(['lb_filters' => $snap]);
                        return $query->whereExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('ratings')
                                ->whereColumn('ratings.staff_id', 'staff.id')
                                ->groupBy('ratings.staff_id')
                                ->havingRaw('COUNT(*) >= 5');
                        });
                    }),

                TableFilter::make('date_range')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('to')->label('To'),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['from'])) {
                            $indicators[] = 'Dari: ' . Carbon::parse($data['from'])->format('d M Y');
                        }
                        if (!empty($data['to'])) {
                            $indicators[] = 'Sampai: ' . Carbon::parse($data['to'])->format('d M Y');
                        }
                        return $indicators;
                    })
                    ->query(function ($query, array $data) {
                        // snapshot same shape as Filament state so resolveRangeFromFilters works
                        $snap = session('lb_filters', []);
                        $snap['date_range'] = [
                            'from' => $data['from'] ?? null,
                            'to'   => $data['to'] ?? null,
                        ];
                        session(['lb_filters' => $snap]);

                        $from = $data['from'] ?? null;
                        $to   = $data['to'] ?? null;

                        return $query
                            ->when($from, function ($q) use ($from) {
                                return $q->whereExists(function ($sub) use ($from) {
                                    $sub->select(DB::raw(1))
                                        ->from('ratings')
                                        ->whereColumn('ratings.staff_id', 'staff.id')
                                        ->whereDate('ratings.created_at', '>=', $from);
                                });
                            })
                            ->when($to, function ($q) use ($to) {
                                return $q->whereExists(function ($sub) use ($to) {
                                    $sub->select(DB::raw(1))
                                        ->from('ratings')
                                        ->whereColumn('ratings.staff_id', 'staff.id')
                                        ->whereDate('ratings.created_at', '<=', $to);
                                });
                            });
                    }),
            ])
            ->filtersFormColumns(3)
            ->persistFiltersInSession();
    }

    /**
     * Header action: open route GET PDF (use URL only)
     */
    protected function getHeaderActions(): array
    {
        $filters = $this->getTableFilterState('table') ?? session('lb_filters') ?? [];

        $query = [];
        if (!empty($filters['date_range']['from']) || !empty($filters['date_range']['to'])) {
            if (!empty($filters['date_range']['from'])) {
                $query['from'] = Carbon::parse($filters['date_range']['from'])->toDateString();
            }
            if (!empty($filters['date_range']['to'])) {
                $query['to'] = Carbon::parse($filters['date_range']['to'])->toDateString();
            }
        } elseif (!empty($filters['7d'])) {
            $query['range'] = '7d';
        } elseif (!empty($filters['30d'])) {
            $query['range'] = '30d';
        }
        if (!empty($filters['min5'])) {
            $query['min5'] = 1;
        }

        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-text')
                ->color('secondary')
                ->url(fn () => route('staff.leaderboard.pdf', $query))
                ->openUrlInNewTab(),
        ];
    }
}
