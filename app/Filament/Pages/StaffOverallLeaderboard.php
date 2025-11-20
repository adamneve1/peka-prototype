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
public static function getNavigationGroup(): ?string { return 'Monitoring'; } // fine if parent allows null
public static function getNavigationLabel(): string { return 'Leaderboard Staff'; }


    public function getView(): string
    {
        return 'filament.pages.staff-overall-leaderboard';
    }

    /**
     * Bangun query Bayesian dengan opsi:
     * - $range: ['from' => Carbon, 'to' => Carbon] untuk custom range / 7d / 30d
     * - $minVotes: integer minimal vote (contoh 5 kalau filter aktif)
     */
    protected function bayesianStaffQuery(?array $range = null, ?int $minVotes = null, int $m = 10)
    {
        // Global mean (C) mengikuti timeframe kalau $range ada
        $global = Rating::query();
        if ($range) {
            $global->whereBetween('created_at', [$range['from'], $range['to']]);
        }
        $C = (float) ($global->avg('score') ?? 0);

        // Subquery agregasi per staff, ikut timeframe + minVotes
        $agg = Rating::query()
            ->select('staff_id')
            ->when($range, fn ($q) =>
                $q->whereBetween('created_at', [$range['from'], $range['to']])
            )
            ->selectRaw('COUNT(*) as ratings_count')
            ->selectRaw('ROUND(AVG(score), 2) as ratings_avg_score')
            ->groupBy('staff_id')
            ->when($minVotes, fn ($q) =>
                $q->havingRaw('COUNT(*) >= ?', [$minVotes])
            );

        // Mulai dari Eloquent builder
        return Staff::query()
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
            // urutan: yang punya rating dulu, baru bayes_score
            ->orderByDesc('has_ratings')
            ->orderByDesc('bayes_score')
            ->orderByDesc('ratings_count');
    }

    /** Data podium top-3 (ikut state filter) */
    protected function getViewData(): array
    {
        $filters = $this->getTableFilterState('table');

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
     * Central helper: resolve range (from/to) from filter state
     */
   /**
 * Central helper: resolve range (from/to) from filter state
 */
protected function resolveRangeFromFilters(?array $filters): ?array
{
    // Normalize: jika null, jadikan array kosong
    $filters = $filters ?? [];

    // Prioritize explicit date_range filter (from/to)
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

    // Quick toggles fallback
    if (!empty($filters['7d'])) {
        return ['from' => now()->subDays(7), 'to' => now()];
    }

    if (!empty($filters['30d'])) {
        return ['from' => now()->subDays(30), 'to' => now()];
    }

    return null;
}


    /** Tabel leaderboard detail (Filament v4) */
    public function table(Table $table): Table
    {
        return $table
            // Build query berdasar state filter setiap render
            ->query(function () {
                $filters = $this->getTableFilterState('table');

                $range = $this->resolveRangeFromFilters($filters);
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

                // Visual bar untuk bayes_score
                Tables\Columns\ViewColumn::make('bayes_score')
                    ->label('Score (Bayes)')
                    ->view('components.leaderboard-score-bar'),

                // Kolom numeric hidden untuk sorting manual lewat header
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
    // quick toggles — null-safe
    TableFilter::make('7d')
        ->label('7 hari')
        ->query(function ($query) {
            if (! $query) return $query;
            return $query->where('created_at', '>=', now()->subDays(7));
        }),

    TableFilter::make('30d')
        ->label('30 hari')
        ->query(function ($query) {
            if (! $query) return $query;
            return $query->where('created_at', '>=', now()->subDays(30));
        }),

    // Min 5 votes — robust via whereExists (works regardless join/subquery)
    TableFilter::make('min5')
        ->label('Min 5 votes')
        ->query(function ($query) {
            if (! $query) return $query;
            return $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('ratings')
                    ->whereColumn('ratings.staff_id', 'staff.id')
                    ->groupBy('ratings.staff_id')
                    ->havingRaw('COUNT(*) >= 5');
            });
        }),

    // date range -> form with query (robust & null-safe)
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
            if (! $query) return $query;
            return $query
                ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                ->when($data['to'] ?? null,   fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
        }),
])
->filtersFormColumns(3)
->persistFiltersInSession()

;
    }

    /**
     * Header action: buka route GET PDF (jangan pake Livewire action)
     * NOTE: remove any old getActions() / exportPdf() — this is the only action.
     */
    protected function getHeaderActions(): array
    {
        $filters = $this->getTableFilterState('table');

        $query = [];
        // Prioritize explicit date_range
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
                 ->action('prepareExport') // panggil method di Page
          
                ->url(fn () => route('staff.leaderboard.pdf', $query))
                ->openUrlInNewTab(),
        ];
    }
    public function prepareExport()
{
    $filters = $this->getTableFilterState('table') ?? [];
    // build URL with filters
    $url = route('staff.leaderboard.pdf', $filters);
    // return JS to open new tab
    return $this->dispatchBrowserEvent('open-export-url', ['url' => $url]);
}

}

