<?php

namespace App\Filament\Resources\Ratings\Tables;

use App\Models\Rating;                 // ← penting
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class RatingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(                               // ← pakai query() bukan modifyQueryUsing()
                Rating::query()
                    ->with(['counter:id,name','service:id,name','staff:id,name'])
            )
            ->defaultSort('created_at','desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('counter.name')
                    ->label('Loket')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Layanan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Petugas')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('score')
                    ->label('Skor')
                    ->badge()
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state <= 2 => 'danger',
                        $state === 3 => 'warning',
                        $state >= 4 => 'success',
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('comment')
                    ->label('Komentar')
                    ->limit(120)
                    ->wrap(),
            ])
            ->filters([
    // by relasi
    \Filament\Tables\Filters\SelectFilter::make('counter_id')
        ->label('Loket')->relationship('counter','name'),

    \Filament\Tables\Filters\SelectFilter::make('service_id')
        ->label('Layanan')->relationship('service','name'),

    \Filament\Tables\Filters\SelectFilter::make('staff_id')
        ->label('Petugas')->relationship('staff','name'),

    // preset date filters (aman, 1 argumen)
    \Filament\Tables\Filters\Filter::make('today')
        ->label('Hari ini')
        ->query(fn ($q) => $q->whereDate('created_at', today())),

    \Filament\Tables\Filters\Filter::make('last7')
        ->label('7 hari terakhir')
        ->query(fn ($q) => $q->where('created_at', '>=', now()->subDays(7))),

    \Filament\Tables\Filters\Filter::make('last30')
        ->label('30 hari terakhir')
        ->query(fn ($q) => $q->where('created_at', '>=', now()->subDays(30))),

    // skor rendah
    \Filament\Tables\Filters\Filter::make('low')
        ->label('Skor ≤ 2')
        ->query(fn ($q) => $q->where('score','<=',2)),
])

            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Detail Rating')
                    ->modalWidth('lg')
                    ->form([
                        Forms\Components\Placeholder::make('waktu')->label('Waktu')
                            ->content(fn($r)=>$r->created_at->format('d M Y H:i')),
                        Forms\Components\Placeholder::make('loket')->label('Loket')
                            ->content(fn($r)=>optional($r->counter)->name),
                        Forms\Components\Placeholder::make('layanan')->label('Layanan')
                            ->content(fn($r)=>optional($r->service)->name),
                        Forms\Components\Placeholder::make('petugas')->label('Petugas')
                            ->content(fn($r)=>optional($r->staff)->name ?? '-'),
                        Forms\Components\Placeholder::make('skor')->label('Skor')
                            ->content(fn($r)=>(string)$r->score),
                        Forms\Components\Textarea::make('comment')->label('Komentar')
                            ->disabled()
                            ->rows(6),
                    ]),
            ]);
    }
}
