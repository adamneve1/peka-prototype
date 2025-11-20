<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Kinerja Pegawai</title>

    <style>
        @page { 
            margin: 2.5cm; 
            margin-bottom: 3cm; /* Tambah margin bawah supaya footer tidak menabrak isi */
            size: A4;
        }
        
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
        }

        /* JUDUL */
        .judul {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            text-transform: uppercase;
            margin-bottom: 5px;
            text-decoration: underline;
        }

        .sub-judul {
            text-align: center;
            font-size: 12pt;
            margin-bottom: 30px;
        }

        /* TABEL INFO */
        .table-info {
            width: 100%;
            margin-bottom: 15px;
            font-size: 12pt;
        }
        .table-info td {
            vertical-align: top;
            padding: 2px 0;
        }

        /* TABEL DATA */
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table-data th, .table-data td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: middle;
        }

        .table-data th {
            font-weight: bold;
            text-align: center;
            background-color: #fff; 
            font-size: 11pt;
        }

        .table-data td {
            font-size: 11pt;
        }

        /* UTILS */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }

        /* TANDA TANGAN */
        .signature-section {
            margin-top: 40px;
            width: 100%;
        }
        .signature-box {
            float: right; 
            width: 280px;
            text-align: center;
        }
        .signature-name {
            margin-top: 70px; 
            font-weight: bold;
            text-decoration: underline;
        }

        /* FOOTER GENERATED */
        .generated {
            position: fixed;
            bottom: -1.5cm; /* Geser ke area margin bawah */
            left: 0;
            right: 0;
            font-size: 9pt;
            font-style: italic;
            border-top: 1px solid #000;
            padding-top: 5px;
            color: #444;
        }
    </style>
</head>

<body>

    <div class="judul">
        LAPORAN PEMERINGKATAN KINERJA PEGAWAI
    </div>
    <div class="sub-judul">
        Sistem Penilaian Emoji Kinerja Aparatur (PEKA)
    </div>

    @php
        $f = $filters['from'] ?? null;
        $t = $filters['to'] ?? null;
        $totalVotes = collect($rows)->sum('cnt');
        
        // Format Tanggal Indonesia Manual
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $formatDateIndo = function($date) use ($months) {
            $d = \Carbon\Carbon::parse($date);
            return $d->format('d') . ' ' . $months[$d->format('n')] . ' ' . $d->format('Y');
        };

        $periodeStr = 'Semua Waktu';
        if($f && $t) {
            $periodeStr = $formatDateIndo($f) . ' s.d. ' . $formatDateIndo($t);
        } elseif(($filters['range'] ?? '') == '7d') {
            $periodeStr = '7 Hari Terakhir';
        } elseif(($filters['range'] ?? '') == '30d') {
            $periodeStr = '30 Hari Terakhir';
        }
    @endphp

    <table class="table-info">
        <tr>
            <td width="140">Periode</td>
            <td width="10">:</td>
            <td>{{ $periodeStr }}</td>
        </tr>
        <tr>
            <td>Total Responden</td>
            <td>:</td>
            <td>{{ number_format($totalVotes, 0, ',', '.') }} Suara</td>
        </tr>
    </table>

    <table class="table-data">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Pegawai</th>
                <th width="20%">Jumlah Penilaian</th>
                <th width="20%">Rata-Rata</th>
                <th width="20%">Skor Pelayanan<br>(Maks 5,00)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $r['name'] ?? '-' }}</td>
                    <td class="text-center">{{ $r['cnt'] }}</td>
                    <td class="text-center">
                        @if($r['cnt'] == 0)
                            -
                        @else
                            {{ number_format($r['avg'], 2, ',', '.') }}
                        @endif
                    </td>
                    <td class="text-center">
                        @if($r['cnt'] == 0)
                            <span style="font-style: italic; font-size: 10pt;">Belum Dinilai</span>
                        @elseif($r['cnt'] < 5)
                            <div class="text-bold">
                                {{ number_format($r['bayes'], 2, ',', '.') }} <span style="font-weight:normal; font-size:10pt;">/ 5</span>
                            </div>
                            <div style="font-style: italic; font-size: 9pt; margin-top: 2px;">
                                (*Kurang Data)
                            </div>
                        @else
                            <span class="text-bold">
                                {{ number_format($r['bayes'], 2, ',', '.') }}
                            </span>
                            <span style="font-weight: normal; font-size: 10pt;"> / 5</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px; font-style:italic;">
                        Data tidak ditemukan pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>



    <div class="generated">
        Dokumen dihasilkan otomatis — {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
    </div>

</body>
</html>