<?php

namespace App\Filament\Resources\Ratings\Tables;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Rating;

use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

use Filament\Actions\ViewAction; // ← penting: v4 pakai ini, bukan Filament\Tables\Actions\ViewAction

use Filament\Infolists;
use Filament\Infolists\Components\TextEntry;

class RatingsTable
{
    public static function configure(Table $table): Table
{
    return $table
        // query + eager load
        ->query(
            Rating::query()->with(['counter:id,name', 'service:id,name', 'staff:id,name,photo_path'])
        )
        ->defaultSort('created_at', 'desc')
        ->paginationPageOptions([10, 25, 50, 100])
        ->poll('60s') // auto refresh 60 detik
        ->persistFiltersInSession()
        ->columns([
            // Waktu (kecil, sebelah kiri)
            TextColumn::make('created_at')
                ->label('Waktu')
                ->dateTime('d M Y H:i')
                ->sortable()
                ->toggleable()
                ->width('120px'),

            // Avatar + Petugas (gabungan visual)
            Tables\Columns\ImageColumn::make('staff.photo_path')
                ->label('')
                ->circular()
                ->height(40)
                ->width(40)
                ->toggleable(false)
                ->extraAttributes(fn ($record) => [
                    'style' => 'vertical-align:middle;margin-right:8px;'
                ])
                ->url(fn ($value, $record) => $record->staff?->photo_path ? (str_starts_with($record->staff->photo_path, 'http') ? $record->staff->photo_path : asset($record->staff->photo_path)) : null),

            TextColumn::make('staff.name')
                ->label('Petugas')
                ->placeholder('-')
                ->sortable()
                ->searchable()
                ->wrap()
                ->limit(40)
                ->toggleable(false)
                ->getStateUsing(fn ($record) => $record->staff?->name ?? '-'),

            // Service + counter sebagai "keterangan" di kolom yang sama
            TextColumn::make('service.name')
                ->label('Layanan / Loket')
                ->sortable()
                ->searchable()
                ->wrap()
                ->formatStateUsing(function ($state, $record) {
                    $counter = $record->counter?->name ? "<div style='font-size:11px;color:#6b7280;margin-top:4px;'>{$record->counter->name}</div>" : '';
                    // return HTML fragment (enable html rendering)
                    return $state ? $state . $counter : $counter;
                })
                ->html(),

            // Status badge berdasarkan skor (visual pembeda yang paling jelas)
            TextColumn::make('status')
                ->label('Status')
                ->getStateUsing(fn ($record) => match ((int) $record->score) {
                    1,2 => 'Buruk',
                    3 => 'Cukup',
                    4,5 => 'Baik',
                    default => '-',
                })
                ->sortable()
                ->toggleable()
                ->formatStateUsing(fn ($state, $record) => $state)
                ->badge()
                ->colors([
                    'danger' => fn ($state) => in_array($state, ['Buruk']),
                    'warning' => fn ($state) => $state === 'Cukup',
                    'success' => fn ($state) => $state === 'Baik',
                ]),

            // Skor compact but still visible
            TextColumn::make('score')
                ->label('Skor')
                ->badge()
                ->formatStateUsing(fn ($state) => "{$state}/5")
                ->sortable()
                ->color(fn ($state) => match (true) {
                    $state <= 2 => 'danger',
                    $state === 3 => 'warning',
                    $state >= 4 => 'success',
                    default => null,
                })
                ->width('90px'),

            // Komentar: tampil sebagai preview, dengan background ringan agar beda
            TextColumn::make('comment')
                ->label('Komentar')
                ->limit(160)
                ->wrap()
                ->tooltip(fn ($record) => $record->comment ?: null)
                ->toggleable()
                ->extraAttributes(fn ($record) => [
                    'style' => $record->comment ? 'background:#f8fafc;padding:8px;border-radius:6px;' : '',
                ])
                ->summarize([
                    Count::make()->label('Jumlah'),
                ]),
        ])
        ->filters([
            // filter relasi
            SelectFilter::make('counter_id')
                ->label('Loket')
                ->relationship('counter', 'name')
                ->searchable(),

            SelectFilter::make('service_id')
                ->label('Layanan')
                ->relationship('service', 'name')
                ->searchable(),

            SelectFilter::make('staff_id')
                ->label('Petugas')
                ->relationship('staff', 'name')
                ->searchable(),

            // date range
            Filter::make('tanggal')
                ->label('Rentang Tanggal')
                ->form([
                    DatePicker::make('from')->label('Dari'),
                    DatePicker::make('until')->label('Sampai'),
                ])
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['from'] ?? null) {
                        $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['from'])->format('d M Y');
                    }
                    if ($data['until'] ?? null) {
                        $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['until'])->format('d M Y');
                    }
                    return $indicators;
                })
                ->query(function (Builder $query, array $data) {
                    return $query
                        ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                }),

            // score range
            Filter::make('score_range')
                ->label('Rentang Skor')
                ->form([
                    TextInput::make('min')->numeric()->minValue(1)->maxValue(5)->label('Min'),
                    TextInput::make('max')->numeric()->minValue(1)->maxValue(5)->label('Max'),
                ])
                ->indicateUsing(function (array $data): array {
                    $i = [];
                    if (filled($data['min'] ?? null)) {
                        $i[] = 'Skor ≥ ' . $data['min'];
                    }
                    if (filled($data['max'] ?? null)) {
                        $i[] = 'Skor ≤ ' . $data['max'];
                    }
                    return $i;
                })
                ->query(function (Builder $query, array $data) {
                    return $query
                        ->when(filled($data['min'] ?? null), fn ($q) => $q->where('score', '>=', (int) $data['min']))
                        ->when(filled($data['max'] ?? null), fn ($q) => $q->where('score', '<=', (int) $data['max']));
                }),

            // hanya yang punya komentar
            Filter::make('has_comment')
                ->label('Dengan Komentar')
                ->query(fn (Builder $q) => $q->whereNotNull('comment')->where('comment', '<>', '')),

            // preset cepat
            Filter::make('today')
                ->label('Hari ini')
                ->query(fn (Builder $q) => $q->whereDate('created_at', today())),

            Filter::make('last7')
                ->label('7 hari terakhir')
                ->query(fn (Builder $q) => $q->where('created_at', '>=', now()->subDays(7))),

            Filter::make('last30')
                ->label('30 hari terakhir')
                ->query(fn (Builder $q) => $q->where('created_at', '>=', now()->subDays(30))),

            Filter::make('low')
                ->label('Skor ≤ 2')
                ->query(fn (Builder $q) => $q->where('score', '<=', 2)),
        ])
        ->filtersFormColumns(3)
        ->recordActions([
            ViewAction::make()
                ->modalHeading('Detail Rating')
                ->modalWidth('lg')
                ->slideOver()
                ->infolist([
                    TextEntry::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->placeholder('-'),
                    TextEntry::make('counter.name')->label('Loket')->placeholder('-'),
                    TextEntry::make('service.name')->label('Layanan')->placeholder('-'),
                    TextEntry::make('staff.name')->label('Petugas')->placeholder('-'),
                    TextEntry::make('score')->label('Skor'),
                    TextEntry::make('comment')->label('Komentar')->wrap()->columnSpanFull(),
                ]),
        ])
        ->emptyStateHeading('Belum ada rating')
        ->emptyStateDescription('Saat data masuk, daftar rating akan muncul di sini.')
        ->striped();
}

}
