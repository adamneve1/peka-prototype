<?php

namespace App\Filament\Resources\StaffAssignments\Tables;

use App\Models\StaffAssignment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class StaffAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // prefer query() + eager load (hindari N+1)
            ->query(
                StaffAssignment::query()
                    ->with(['counter:id,name', 'staff:id,name'])
            )
            ->defaultSort('starts_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->deferFilters(false)
            ->striped()
            ->columns([
                // Status: Scheduled / Active / Ended
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(function ($record) {
                        $now = now();
                        $start = $record->starts_at;
                        $end   = $record->ends_at;
                        if ($start && $start->isFuture()) return 'Scheduled';
                        if ($start && (!$end || $end->isFuture()) && $start->lte($now)) return 'Active';
                        return 'Ended';
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            'Active'    => 'success',
                            'Scheduled' => 'info',
                            default     => 'gray',
                        };
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        // sort status yang konsisten: Active > Scheduled > Ended
                        return $query->orderByRaw("
                            CASE 
                                WHEN starts_at <= CURRENT_TIMESTAMP 
                                     AND (ends_at IS NULL OR ends_at > CURRENT_TIMESTAMP) THEN 0
                                WHEN starts_at > CURRENT_TIMESTAMP THEN 1
                                ELSE 2
                            END {$direction}, starts_at {$direction}
                        ");
                    }),

                TextColumn::make('counter.name')
                    ->label('Loket')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('staff.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Durasi singkat (kalau ada ends_at)
                TextColumn::make('duration_hours')
                    ->label('Durasi')
                    ->state(function ($record) {
                        if (! $record->starts_at) return '—';
                        $end = $record->ends_at ?: now();
                        $mins = $record->starts_at->diffInMinutes($end);
                        if ($record->ends_at) {
                            // durasi final
                            return $mins >= 60
                                ? floor($mins / 60) . 'h ' . ($mins % 60) . 'm'
                                : $mins . 'm';
                        }
                        // on-going
                        return '~' . ($mins >= 60
                                ? floor($mins / 60) . 'h ' . ($mins % 60) . 'm'
                                : $mins . 'm');
                    })
                    ->tooltip(fn ($state) => "Estimasi jika belum selesai")
                    ->toggleable(),

                // Indikator overlap (tabrakan jadwal staff)
                TextColumn::make('conflict')
                    ->label('Conflict')
                    ->badge()
                    ->state(function ($record) {
                        // time-overlap: [A.start < B.end] AND [A.end > B.start] (null=open)
                        $exists = StaffAssignment::query()
                            ->where('staff_id', $record->staff_id)
                            ->where('id', '!=', $record->id)
                            ->where(function ($q) use ($record) {
                                $q->where(function ($qq) use ($record) {
                                    $qq->where('starts_at', '<', $record->ends_at ?? now()->addCentury())
                                       ->where(function ($qqq) use ($record) {
                                           $qqq->whereNull('ends_at')
                                               ->orWhere('ends_at', '>', $record->starts_at);
                                       });
                                });
                            })
                            ->exists();

                        return $exists ? 'Overlap' : 'OK';
                    })
                    ->color(fn ($state) => $state === 'Overlap' ? 'danger' : 'success')
                    ->toggleable(),

                TextColumn::make('note')
                    ->label('Catatan')
                    ->limit(80)
                    ->wrap()
                    ->tooltip(fn ($record) => $record->note ?: null),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // optional: group by Loket biar kebaca per counter
            ->groups([
                Group::make('counter.name')->label('Loket')->collapsible(),
            ])
            ->filters([
                // filter relasi
                SelectFilter::make('counter_id')
                    ->label('Loket')
                    ->relationship('counter', 'name')
                    ->searchable(),

                SelectFilter::make('staff_id')
                    ->label('Petugas')
                    ->relationship('staff', 'name')
                    ->searchable(),

                // aktif sekarang
                TernaryFilter::make('active_now')
                    ->label('Sedang aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak aktif')
                    ->queries(
                        true: fn (Builder $q) => $q->where('starts_at', '<=', now())
                            ->where(function ($qq) { $qq->whereNull('ends_at')->orWhere('ends_at', '>', now()); }),
                        false: fn (Builder $q) => $q->where(function ($qq) {
                            $qq->where('starts_at', '>', now())
                               ->orWhereNotNull('ends_at')->where('ends_at', '<=', now());
                        }),
                        blank: fn (Builder $q) => $q
                    ),

                // ada tanggal selesai atau open-ended
                TernaryFilter::make('has_end')
                    ->label('Ada Selesai')
                    ->trueLabel('Ada')
                    ->falseLabel('Tidak ada')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('ends_at'),
                        false: fn (Builder $q) => $q->whereNull('ends_at'),
                        blank: fn (Builder $q) => $q
                    ),

                // date range overlap filter (ambil assignment yang nempel periode)
                Filter::make('range')
                    ->label('Rentang Waktu')
                    ->form([
                        DateTimePicker::make('from')->label('Dari'),
                        DateTimePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;
                        if (! $from && ! $until) return $q;

                        // overlap rule:
                        // starts_at <= until AND (ends_at IS NULL OR ends_at >= from)
                        return $q
                            ->when($until, fn ($qq) => $qq->where('starts_at', '<=', $until))
                            ->when($from, fn ($qq) => $qq->where(function ($wq) use ($from) {
                                $wq->whereNull('ends_at')->orWhere('ends_at', '>=', $from);
                            }));
                    })
                    ->indicateUsing(function (array $data): array {
                        $labels = [];
                        if (!empty($data['from'])) $labels[] = 'Dari: ' . \Carbon\Carbon::parse($data['from'])->format('d M Y H:i');
                        if (!empty($data['until'])) $labels[] = 'Sampai: ' . \Carbon\Carbon::parse($data['until'])->format('d M Y H:i');
                        return $labels;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Detail Assignment')
                    ->slideOver()
                    ->infolist([
                        \Filament\Infolists\Components\TextEntry::make('counter.name')->label('Loket'),
                        \Filament\Infolists\Components\TextEntry::make('staff.name')->label('Petugas'),
                        \Filament\Infolists\Components\TextEntry::make('starts_at')->label('Mulai')->dateTime('d M Y H:i'),
                        \Filament\Infolists\Components\TextEntry::make('ends_at')->label('Selesai')->dateTime('d M Y H:i')->placeholder('—'),
                        \Filament\Infolists\Components\TextEntry::make('note')->label('Catatan')->columnSpanFull()->wrap(),
                    ]),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
