<?php
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Rating;
use App\Models\Staff;
use App\Livewire\Kiosk\Rate as KioskRate;

use App\Livewire\Peka;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

RateLimiter::for('ratings', fn(Request $r) => [Limit::perMinute(10)->by($r->ip())]);

// Root -> Livewire Peka
Route::middleware('throttle:ratings')->get('/', Peka::class)->name('peka.page');







// Route untuk PDF (debug-friendly)
Route::get('/adminPanel/staff-overall-leaderboard/pdf', function (Request $request) {
    // Baca query params: from=YYYY-MM-DD, to=YYYY-MM-DD, range=7d|30d, min5=1
    $from = $request->query('from');
    $to   = $request->query('to');

    $range = null;
    if ($from || $to) {
        $from = $from ? Carbon::parse($from)->startOfDay() : now()->subYears(10);
        $to   = $to   ? Carbon::parse($to)->endOfDay()   : now();
        $range = ['from' => $from, 'to' => $to];
    } elseif ($request->query('range') === '7d') {
        $range = ['from' => now()->subDays(7), 'to' => now()];
    } elseif ($request->query('range') === '30d') {
        $range = ['from' => now()->subDays(30), 'to' => now()];
    }

    $minVotes = $request->boolean('min5') ? 5 : null;

    // --- build global mean C and m (sama logic di page)
    $global = Rating::query();
    if ($range) {
        $global->whereBetween('created_at', [$range['from'], $range['to']]);
    }
    $C = (float) ($global->avg('score') ?? 0);
    $m = 10;

    // subquery agg
    $agg = Rating::query()
        ->select('staff_id')
        ->when($range, fn($q) => $q->whereBetween('created_at', [$range['from'], $range['to']]))
        ->selectRaw('COUNT(*) as ratings_count')
        ->selectRaw('ROUND(AVG(score), 2) as ratings_avg_score')
        ->groupBy('staff_id')
        ->when($minVotes, fn($q) => $q->havingRaw('COUNT(*) >= ?', [$minVotes]));

    // main rows
    $rowsCollection = Staff::query()
        ->leftJoinSub($agg, 'r', 'r.staff_id', '=', 'staff.id')
        ->select('staff.*')
        ->addSelect([
            DB::raw('COALESCE(r.ratings_count, 0) as ratings_count'),
            DB::raw('COALESCE(r.ratings_avg_score, 0) as ratings_avg_score'),
            DB::raw('CASE WHEN COALESCE(r.ratings_count,0) > 0 THEN 1 ELSE 0 END as has_ratings'),
        ])
        ->selectRaw(
            '( (COALESCE(r.ratings_count,0) * COALESCE(r.ratings_avg_score,0) + ? * ?) / NULLIF(COALESCE(r.ratings_count,0) + ?, 0) ) as bayes_score',
            [$m, $C, $m]
        )
        ->orderByDesc('has_ratings')
        ->orderByDesc('bayes_score')
        ->orderByDesc('ratings_count')
        ->get(['id','name','photo_path','ratings_avg_score','ratings_count','bayes_score']);

    // Map rows -> normal array + ensure photo is data-uri or absolute url
    ini_set('memory_limit','512M');
    set_time_limit(120);
    Pdf::setOptions(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

    $rows = $rowsCollection->map(function ($s) {
        $photoPath = $s->photo_path;
        $photo = null;

        if ($photoPath) {
            // data URI passthrough
            if (Str::startsWith($photoPath, 'data:')) {
                $photo = $photoPath;
            }
            // absolute HTTP(S): try to fetch and convert to base64 (safer)
            elseif (Str::startsWith($photoPath, 'http://') || Str::startsWith($photoPath, 'https://')) {
                $contents = @file_get_contents($photoPath);
                if ($contents !== false) {
                    $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $contents) ?: 'image/png';
                    $photo = 'data:' . $mime . ';base64,' . base64_encode($contents);
                } else {
                    // fallback to absolute URL (dompdf may still load if remote enabled)
                    $photo = $photoPath;
                }
            } else {
                // try storage/public path
                $candidate = null;
                if (Str::startsWith($photoPath, 'storage/') || Str::startsWith($photoPath, 'public/')) {
                    $candidate = public_path($photoPath);
                }
                if (!$candidate && Storage::disk('public')->exists($photoPath)) {
                    $candidate = Storage::disk('public')->path($photoPath);
                }
                if (!$candidate && file_exists(public_path($photoPath))) {
                    $candidate = public_path($photoPath);
                }
                if (!$candidate && file_exists(base_path($photoPath))) {
                    $candidate = base_path($photoPath);
                }

                if ($candidate && is_readable($candidate)) {
                    $contents = @file_get_contents($candidate);
                    if ($contents !== false) {
                        $mime = mime_content_type($candidate) ?: 'image/png';
                        $photo = 'data:' . $mime . ';base64,' . base64_encode($contents);
                    }
                } else {
                    // last resort: absolute URL via url()
                    $photoUrl = url($photoPath);
                    $contents = @file_get_contents($photoUrl);
                    if ($contents !== false) {
                        $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $contents) ?: 'image/png';
                        $photo = 'data:' . $mime . ';base64,' . base64_encode($contents);
                    } else {
                        $photo = $photoUrl; // allow dompdf remote if enabled
                    }
                }
            }
        }

        return [
            'id'    => $s->id,
            'name'  => $s->name,
            'photo' => $photo,
            'avg'   => (float) ($s->ratings_avg_score ?? 0),
            'cnt'   => (int) ($s->ratings_count ?? 0),
            'bayes' => (float) ($s->bayes_score ?? 0),
        ];
    })->values()->toArray();

    // podium top 3
    $podium = array_slice($rows, 0, 3);

    // prepare filters array for view (so compact('filters') won't fail)
    $filters = [
        'from' => $from ? $from->toDateString() : null,
        'to'   => $to ? $to->toDateString() : null,
        'range'=> $request->query('range'),
        'min5' => (bool) $minVotes,
    ];

    // --- DEBUG MODE: render HTML so you can inspect <img src="...">
    // change to PDF streaming once verified
    if ($request->query('debug') === 'html') {
        return view('filament.pages.staff-overall-leaderboard-pdf', compact('podium','rows','filters'));
    }

    // --- PRODUCTION: render PDF
    $pdf = Pdf::loadView('filament.pages.staff-overall-leaderboard-pdf', compact('podium','rows','filters'));
    $pdf->setPaper('a4', 'portrait');

    return response($pdf->stream('staff-leaderboard.pdf'), 200)
        ->header('Content-Type', 'application/pdf');

})->name('staff.leaderboard.pdf')->middleware(['auth']);

