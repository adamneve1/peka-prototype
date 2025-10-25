<?php

namespace App\Filament\Resources\Counters\Pages;

use App\Filament\Resources\Counters\CounterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCounter extends EditRecord
{
    protected static string $resource = CounterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
