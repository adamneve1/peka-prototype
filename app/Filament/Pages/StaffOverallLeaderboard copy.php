<?php

namespace App\Filament\Pages;

use App\Models\Rating;
use App\Models\Staff;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;

class StaffOverallLeaderboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected ?string $heading = 'Staff Leaderboard (Overall)';

    // pakai methods biar aman lintas versi
    public static function getNavigationIcon(): string { return 'heroicon-o-trophy'; }
    public static function getNavigationGroup(): ?string { return 'Monitoring'; }
    public static function getNavigationLabel(): string { return 'Leaderboard Staff'; }

    // wajib public di build lo
    public function getView(): string
    {
        return 'filament.pages.staff-overall-leaderboard';
    }

    /** Base query: stabil di SQLite (pakai selectSub), urut avg lalu count */
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
                ->whereNotNull('staff_id')->distinct()->select('staff_id'))
            ->orderByDesc('ratings_avg_score')
            ->orderByDesc('ratings_count');
    }

    /** Data buat podium top-3 (dipakai di blade) */
    protected function getViewData(): array
    {
        $podium = (clone $this->baseStaffQuery())
            ->limit(3)
            ->get(['id', 'name', 'photo_path'])
            ->map(fn ($s) => [
                'id'    => $s->id,
                'name'  => $s->name,
                'photo' => $s->photo_url,               // accessor dari model
                'avg'   => (float) ($s->ratings_avg_score ?? 0),
                'cnt'   => (int) ($s->ratings_count ?? 0),
            ])
            ->values();

        return compact('podium');
    }

    /** Tabel leaderboard detail */
    public function table(Table $table): Table
    {
        return $table
            ->query($this->baseStaffQuery())
            ->columns([
                // Rank badge (index per halaman)
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->rowIndex()
                    ->badge()
                    ->color(fn ($state) => match ((int) $state) {
                        1 => 'warning', // emas
                        2 => 'gray',    // perak
                        3 => 'danger',  // perunggu
                        default => 'info',
                    }),

                Tables\Columns\ImageColumn::make('photo_url')
                    ->label('')
                    ->circular()->height(40)->width(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('Staff')
                    ->searchable()->sortable(),

                // Progress bar avg score (view custom biar “leaderboard vibes”)
                Tables\Columns\ViewColumn::make('ratings_avg_score')
                    ->label('Avg')
                    ->view('components.leaderboard-score-bar'),

                Tables\Columns\TextColumn::make('ratings_count')
                    ->label('Votes')
                    ->badge()->sortable(),
            ])
            ->filters([
                // Filter timeframe aman buat SQLite (pakai subquery)
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
