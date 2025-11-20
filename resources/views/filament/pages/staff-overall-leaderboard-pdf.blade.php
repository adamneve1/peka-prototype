<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Leaderboard Pegawai</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

    <style>
        @page { margin: 15mm; }
        
        /* RESET & CORE */
        body {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #000000; /* Hitam pekat, bukan abu-abu */
            background: #fff;
        }

        /* UTILITIES */
        .mono { font-family: 'Space Mono', 'Courier New', monospace; }
        .bold { font-weight: 800; }
        .uppercase { text-transform: uppercase; }

        /* HEADER SECTION - Neo Brutalist Style */
        .header {
            display: block;
            margin-bottom: 30px;
            border-bottom: 3px solid #000;
            padding-bottom: 20px;
            position: relative;
        }

        .header h1 {
            font-family: 'Inter', sans-serif;
            font-weight: 900; /* Extra Bold */
            font-size: 24px;
            margin: 0;
            letter-spacing: -0.5px;
            line-height: 1;
            text-transform: uppercase;
        }

        .header .sub {
            font-family: 'Space Mono', monospace;
            font-size: 11px;
            color: #000;
            margin-top: 6px;
            letter-spacing: 1px;
        }

        /* PERIOD BADGE - Pill Shape with Outline */
        .period-badge {
            display: inline-block;
            margin-top: 15px;
            padding: 6px 16px;
            border: 1.5px solid #000;
            border-radius: 50px; /* Full Pill */
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            font-weight: 700;
            background: #fff;
            box-shadow: 2px 2px 0px #000; /* Hard Shadow effect */
        }

        /* TABLE STYLING - High Contrast */
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            border: 2px solid #000;
        }

        table th {
            background: #000;
            color: #fff;
            border: 2px solid #000;
            padding: 12px 10px;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        table td {
            border: 1.5px solid #000; /* Garis tebal */
            padding: 10px 12px;
            font-size: 12px;
            color: #000;
        }

        /* COLUMN SPECIFICS */
        td.rank-col {
            background: #f0f0f0; /* Sedikit pattern */
            font-family: 'Space Mono', monospace;
            font-weight: bold;
            text-align: center;
        }

        td.name-col {
            font-weight: 600;
        }

        td.data-col {
            font-family: 'Space Mono', monospace; /* Angka pakai monospace */
            text-align: center;
        }

        /* FOOTER & SIGNATURE */
        .footer-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-box {
            float: right;
            width: 220px;
            text-align: left; /* Align left inside the box looks cleaner modern */
        }

        .signature-date {
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .signature-title {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 60px; /* Ruang ttd */
        }

        .signature-name {
            font-weight: 800;
            font-size: 12px;
            text-decoration: underline;
            text-decoration-thickness: 2px; /* Garis bawah tebal */
        }

        .signature-nip {
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            margin-top: 2px;
        }

        /* BOTTOM BAR */
        .generated {
            position: fixed;
            bottom: 0;
            left: 15mm;
            right: 15mm;
            border-top: 1.5px solid #000;
            padding-top: 8px;
            font-family: 'Space Mono', monospace;
            font-size: 9px;
            display: flex;
            justify-content: space-between;
        }
        /* ... CSS lainnya ... */

/* Container untuk badge supaya sejajar */
.badge-group {
    margin-top: 15px;
}

/* Style dasar badge (dipakai untuk Periode) */
.badge {
    display: inline-block;
    padding: 6px 16px;
    border: 1.5px solid #000;
    border-radius: 50px;
    font-family: 'Space Mono', monospace;
    font-size: 10px;
    font-weight: 700;
    background: #fff;
    color: #000;
    box-shadow: 2px 2px 0px #000;
    margin-right: 8px; /* Jarak antar badge */
}

/* Style khusus untuk Total Suara (Hitam pekat) */
.badge.dark {
    background: #000;
    color: #fff;
    border: 1.5px solid #000; /* Border tetap hitam menyatu */
    box-shadow: 2px 2px 0px #999; /* Shadow jadi abu-abu biar kelihatan depth-nya */
}
    </style>
</head>

<body>

    @php
        $rows = $rows ?? [];
        $filters = $filters ?? ['from'=>null,'to'=>null,'range'=>null,'min5'=>false];
    @endphp

    <div class="header">
    <h1>Laporan Pemeringkatan Pegawai</h1>
    <div class="sub">// SISTEM PENILAIAN EMOJI KINERJA APARATUR (PEKA)</div>

    @php
        $f = $filters['from'] ?? null; 
        $t = $filters['to'] ?? null;
        // Hitung total semua 'cnt' (jumlah vote) dari rows yang ada
        $totalVotes = collect($rows)->sum('cnt'); 
    @endphp

    <div class="badge-group">
        <div class="badge">
            @if($f && $t)
                PERIODE: {{ \Carbon\Carbon::parse($f)->format('d M Y') }} — {{ \Carbon\Carbon::parse($t)->format('d M Y') }}
            @elseif(!empty($filters['range']) && $filters['range'] === '7d')
                RANGE: 7 HARI TERAKHIR
            @elseif(!empty($filters['range']) && $filters['range'] === '30d')
                RANGE: 30 HARI TERAKHIR
            @else
                RANGE: SEMUA WAKTU
            @endif
        </div>

        <div class="badge dark">
            TOTAL PENILAIAN: {{ number_format($totalVotes) }}
        </div>
    </div>
</div>

    <table>
        <thead>
            <tr>
                <th width="8%">#</th>
                <th style="text-align:left;">NAMA PEGAWAI</th>
                <th width="25%">RATA-RATA / SUARA</th>
                <th width="25%">SKOR PELAYANAN</th>
            </tr>
        </thead>

      <tbody>
    @forelse($rows as $r)
        <tr>
            <td class="rank-col">
                {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
            </td>
            <td class="name-col">{{ $r['name'] ?? '-' }}</td>
            <td class="data-col">
                {{ number_format($r['avg'], 2) }} <span style="font-size:10px; color:#666;">/ {{ $r['cnt'] }} VOTES</span>
            </td>
            
            <td class="data-col" style="font-weight:bold;">
                <span style="font-size: 14px;">{{ number_format($r['bayes'], 2) }}</span><span style="font-size: 10px; color: #888; margin-left: 2px; font-weight: normal;">/5</span>
            </td>
            </tr>
    @empty
        <tr>
            <td colspan="4" style="text-align:center; padding:30px; font-family:'Space Mono', monospace;">
                // NO_DATA_AVAILABLE_FOR_THIS_PERIOD
            </td>
        </tr>
    @endforelse
</tbody>
    </table>


    <div class="generated">
        <span>GEN: AUTOMATED_SYSTEM</span>
        <span>{{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</span>
    </div>

</body>
</html>