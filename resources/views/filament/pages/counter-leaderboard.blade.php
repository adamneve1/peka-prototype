<x-filament::page>
    <x-filament::section>
        <x-slot name="heading">Leaderboard Loket</x-slot>
        <x-slot name="description">
            Mode <b>Terbaik</b> = urut Skor Terpercaya (Bayes). 
            Mode <b>Toxic</b> = urut % skor ≤2 tertinggi.
        </x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament::page>
