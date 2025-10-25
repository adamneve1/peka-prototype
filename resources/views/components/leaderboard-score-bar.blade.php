@php
    $avg = (float) ($getState() ?? 0);
    $pct = max(0, min(100, ($avg / 5) * 100));
@endphp

<div class="flex items-center gap-2 min-w-[140px]">
    <div class="w-24 h-2 bg-slate-200 rounded-full overflow-hidden">
        <div class="h-full rounded-full" style="width: {{ $pct }}%; background-color: rgb(16 185 129)"></div>
    </div>
    <span class="text-xs font-medium tabular-nums">{{ number_format($avg, 2) }}</span>
</div>
