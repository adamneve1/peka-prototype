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

    /** Base: avg & count per staff (stable di SQLite) */
    protected function baseStaffQuery()
    {
        return Staff::query()
            ->select('staff.*')
            ->selectSub(
                Rating::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('ratings.staff_id', 'staff.id'),
                'ratings_count'
            )
            ->selectSub(
                Rating::query()
                    ->selectRaw('ROUND(AVG(score), 2)')
                    ->whereColumn('ratings.staff_id', 'staff.id'),
                'ratings_avg_score'
            )
            ->whereIn('id', Rating::query()
                ->whereNotNull('staff_id')->distinct()->select('staff_id'));
    }

    /**
     * Query dengan kolom bayesian: bayes_score
     * $m: prior strength (default 10, bisa taruh di config: config('ratings.bayes_m', 10))
     * $C: global mean; default semua rating. (Kalau mau, bisa diubah per filter timeframe)
     */
    protected function bayesianStaffQuery(int $m = 10)
{
    $C = (float) (Rating::avg('score') ?? 0);

    // Subquery agregasi per staff (count & avg)
    $agg = Rating::query()
        ->select('staff_id')
        ->selectRaw('COUNT(*) as ratings_count')
        ->selectRaw('ROUND(AVG(score), 2) as ratings_avg_score')
        ->groupBy('staff_id');

    // Penting: MULAI dari Staff::query() (Eloquent), lalu joinSub
    return Staff::query()
        // join biasa = inner join -> hanya staff yang punya rating
        ->leftJoinSub($agg, 'r', 'r.staff_id', '=', 'staff.id')
        ->select('staff.*')
        ->addSelect([
            DB::raw('r.ratings_count'),
            DB::raw('r.ratings_avg_score'),
        ])
        ->addSelect([
    DB::raw('COALESCE(r.ratings_count, 0) as ratings_count'),
    DB::raw('COALESCE(r.ratings_avg_score, 0) as ratings_avg_score'),
])
        ->selectRaw(
  '( (COALESCE(r.ratings_count,0) * COALESCE(r.ratings_avg_score,0) + ? * ?) / NULLIF(COALESCE(r.ratings_count,0) + ?, 0) ) as bayes_score',
  [$m, $C, $m]
)
        ->orderByDesc('bayes_score')
        ->orderByDesc('r.ratings_count');
}

    /** Data buat podium top-3 (pakai bayes_score) */
    protected function getViewData(): array
    {
        $podium = $this->bayesianStaffQuery()
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

    /** Tabel leaderboard detail */
    public function table(Table $table): Table
    {
        return $table
            // Pakai query berbobot Bayesian
            ->query($this->bayesianStaffQuery())
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

                // Tampilkan skor Bayesian di progress bar (kalau kamu punya partialnya,
                // tinggal ubah supaya baca 'bayes_score' alih-alih 'ratings_avg_score')
                Tables\Columns\ViewColumn::make('bayes_score')
                    ->label('Score (Bayes)')
                    ->view('components.leaderboard-score-bar')
                    
                    ->sortable()   ,
                // Opsional: tampilkan rata-rata mentah sebagai info
                Tables\Columns\TextColumn::make('ratings_avg_score')
                    ->label('Avg (raw)')
                    ->numeric(2)
                    ->tooltip(fn ($record) => "Votes: {$record->ratings_count}")
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ratings_count')
                    ->label('Votes')
                    ->badge()->sortable(),
            ])
            ->filters([
                // Catatan: filter ini masih pakai C global. Kalau mau C mengikuti timeframe,
                // kamu perlu bikin varian bayesianStaffQuery() yang menerima builder/closure
                // untuk mengubah subquery C = AVG(score) dengan kondisi tanggal yang sama.
                Tables\Filters\Filter::make('7d')->label('7 hari')
                    ->query(fn ($q) => $q->whereIn('id',
                        Rating::select('staff_id')
                            ->whereNotNull('staff_id')
                            ->where('created_at', '>=', now()->subDays(7))
                            ->groupBy('staff_id')
                    )),
                Tables\Filters\Filter::make('30d')->label('30 hari')
                    ->query(fn ($q) => $q->whereIn('id',
                        Rating::select('staff_id')
                            ->whereNotNull('staff_id')
                            ->where('created_at', '>=', now()->subDays(30))
                            ->groupBy('staff_id')
                    )),
                Tables\Filters\Filter::make('min5')->label('Min 5 votes')
                    ->query(fn ($q) => $q->whereIn('id',
                        Rating::select('staff_id')
                            ->whereNotNull('staff_id')
                            ->groupBy('staff_id')
                            ->havingRaw('COUNT(*) >= 5')
                    )),
            ])
            ->defaultPaginationPageOption(15);
    }
}
