<div id="staff-leaderboard-root">
    <x-filament-panels::page>
        {{ $this->table }}
    </x-filament-panels::page>

    @once
        @push('scripts')
            <script>
                // Listen to Livewire event emitted by the Page
                if (typeof Livewire !== 'undefined') {
                    Livewire.on('openExportUrl', function (url) {
                        if (url) {
                            window.open(url, '_blank');
                        } else {
                            console.error('openExportUrl emitted without url');
                            alert('Gagal membuka export: URL tidak tersedia.');
                        }
                    });
                } else {
                    console.warn('Livewire not found when attaching export listener');
                }
            </script>
        @endpush
    @endonce
</div>
