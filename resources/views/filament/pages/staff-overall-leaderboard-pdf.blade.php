<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Staff Leaderboard</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 12px; }
        .filters { font-size: 11px; color: #555; margin-bottom: 8px; }
        .podium { display:flex; gap:12px; margin-bottom: 16px; }
        .card { border:1px solid #ddd; padding:8px; border-radius:6px; flex:1; text-align:center; }
        .photo { width:80px; height:80px; object-fit:cover; border-radius:50%; margin-bottom:8px; }
        table { width:100%; border-collapse: collapse; margin-top:8px; }
        th, td { border:1px solid #eee; padding:6px; text-align:left; font-size:11px; }
        th { background:#f7f7f7; }
        .right { text-align:right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Staff Leaderboard (Overall)</h2>
        <div class="filters">
            @if(!empty($filters['7d'])) 7 hari
            @elseif(!empty($filters['30d'])) 30 hari
            @endif
            @if(!empty($filters['min5'])) — Min 5 votes @endif
        </div>
    </div>

    <div class="podium">
        @foreach($podium as $p)
            <div class="card">
                @if($p['photo'])
                    <img class="photo" src="{{ $p['photo'] }}" alt="photo">
                @else
                    <div style="width:80px;height:80px;border-radius:50%;background:#ddd;display:inline-block;margin-bottom:8px;"></div>
                @endif
                <div><strong>{{ $p['name'] }}</strong></div>
                <div>Avg: {{ number_format($p['avg'],2) }} — Votes: {{ $p['cnt'] }}</div>
                <div>Bayes: {{ number_format($p['bayes'], 4) }}</div>
            </div>
        @endforeach
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Staff</th>
                <th class="right">Bayes Score</th>
                <th class="right">Avg (raw)</th>
                <th class="right">Votes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $index => $r)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    @if($r['photo'])
                        <img src="{{ $r['photo'] }}" style="width:28px;height:28px;object-fit:cover;border-radius:50%;vertical-align:middle;margin-right:6px;">
                    @endif
                    {{ $r['name'] }}
                </td>
                <td class="right">{{ number_format($r['bayes'], 4) }}</td>
                <td class="right">{{ number_format($r['avg'], 2) }}</td>
                <td class="right">{{ $r['cnt'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
