<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Leaderboard Pegawai</title>

    <style>
        @page { margin: 18mm; }
        body {
            font-family: "Inter", "Helvetica", sans-serif;
            font-size: 12px;
            line-height: 1.45;
            color: #1f2937;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            letter-spacing: 0.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #111827;
        }

        .header .sub {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
            margin-bottom: 12px;
        }

        .period-info {
            display: inline-block;
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
            font-size: 11px;
            color: #4b5563;
        }

        table {
            width: 100%;
            margin-top: 18px;
            border-collapse: collapse;
        }

        table th {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        table td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            font-size: 11.5px;
        }

        td.center { text-align: center; }
        td.name { font-weight: 600; }

        .footer {
            margin-top: 35px;
            text-align: right;
            font-size: 11.5px;
            color: #6b7280;
        }

        .signature {
            margin-top: 45px;
            text-align: right;
            font-size: 12px;
        }

        .signature .name {
            margin-top: 40px;
            font-weight: 700;
            text-decoration: underline;
        }

        .signature .nip {
            font-size: 11px;
            color: #6b7280;
        }

        .generated {
            position: fixed;
            bottom: 10px;
            left: 18mm;
            font-size: 10px;
            color: #9ca3af;
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
        <div class="sub">Sistem Penilaian Emoji Kinerja Aparatur (PEKA)</div>

        <div class="period-info">
            @php $f = $filters['from'] ?? null; $t = $filters['to'] ?? null; @endphp

            @if($f && $t)
                Periode: {{ \Carbon\Carbon::parse($f)->format('d M Y') }} — {{ \Carbon\Carbon::parse($t)->format('d M Y') }}
            @elseif(!empty($filters['range']) && $filters['range'] === '7d')
                7 Hari Terakhir
            @elseif(!empty($filters['range']) && $filters['range'] === '30d')
                30 Hari Terakhir
            @else
                Semua Waktu
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="8%">No</th>
                <th style="text-align:left;">Nama Pegawai</th>
                <th width="22%">Rata-rata / Suara</th>
                <th width="22%">Skor Kinerja Pelayanan</th>
            </tr>
        </thead>

        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="name">{{ $r['name'] ?? '-' }}</td>
                    <td class="center">{{ number_format($r['avg'], 2) }} / {{ $r['cnt'] }}</td>
                    <td class="center">{{ number_format($r['bayes'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center; padding:16px; color:#6b7280;">
                        Tidak ada data untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature">
        <div>Batam, {{ \Carbon\Carbon::now()->format('d F Y') }}</div>
        <div>Kepala Unit / Atasan Langsung</div>

        <div class="name">Nama Pejabat</div>
        <div class="nip">NIP. 19800101 200012 1 001</div>
    </div>

    <div class="generated">
        Dokumen dihasilkan otomatis — {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
    </div>

</body>
</html>
