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

class StaffOverallLeaderboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected ?string $heading = 'Staff Leaderboard (Overall)';

    public static function getNavigationIcon(): string { return 'heroicon-o-trophy'; }
    public static function getNavigationGroup(): ?string { return 'Monitoring'; }
    public static function getNavigationLabel(): string { return 'Leaderboard Staff'; }

    public function getView(): string
    {
        return 'filament.pages.staff-overall-leaderboard';
    }

    /**
     * Bangun query Bayesian dengan opsi:
     * - $range: ['from' => Carbon, 'to' => Carbon] untuk 7d/30d
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
        // Ambil state filter biar konsisten dengan tabel
      $filters = $this->getTableFilterState('table');


        $range = null;
        if (!empty($filters['7d'])) {
            $range = ['from' => now()->subDays(7), 'to' => now()];
        } elseif (!empty($filters['30d'])) {
            $range = ['from' => now()->subDays(30), 'to' => now()];
        }

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

    /** Tabel leaderboard detail (Filament v4) */
    public function table(Table $table): Table
    {
        return $table
            // Build query berdasar state filter setiap render
          ->query(function () {
    $filters = $this->getTableFilterState('table');

    $range = null;
    if (!empty($filters['7d'])) {
        $range = ['from' => now()->subDays(7), 'to' => now()];
    } elseif (!empty($filters['30d'])) {
        $range = ['from' => now()->subDays(30), 'to' => now()];
    }

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

            // Filter di UI saja; query dibangun ulang via getTableFilterState()
            ->filters([
                Tables\Filters\Filter::make('7d')->label('7 hari'),
                Tables\Filters\Filter::make('30d')->label('30 hari'),
                Tables\Filters\Filter::make('min5')->label('Min 5 votes'),
            ])

           ->paginationPageOptions([15, 30, 50])
->defaultPaginationPageOption(15);
    }
}
