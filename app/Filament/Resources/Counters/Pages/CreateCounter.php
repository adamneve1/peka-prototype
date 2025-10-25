<?php

namespace App\Filament\Resources\Counters\Pages;

use App\Filament\Resources\Counters\CounterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCounter extends CreateRecord
{
    protected static string $resource = CounterResource::class;
}
