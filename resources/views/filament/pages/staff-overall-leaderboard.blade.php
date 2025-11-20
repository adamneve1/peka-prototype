<x-filament-panels::page>
    {{ $this->table }}
    <script>
    window.addEventListener('open-export-pdf', e => {
        window.open(e.detail.url, '_blank');
    });
</script>

</x-filament-panels::page>
