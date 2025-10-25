<?php

namespace App\Filament\Resources\StaffAssignments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaffAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // biar gak N+1: eager load counter & staff
            ->modifyQueryUsing(fn ($query) => $query->with(['counter','staff']))
            ->columns([
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('note')
                    ->label('Catatan')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // optional: filter aktif (ends_at null)
                // SelectFilter::make('active')->options([...])
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
