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







// Route untuk PDF (debug-friendly) - Filament v4 friendly: closure route
Route::get('/adminPanel/staff-overall-leaderboard/pdf', function (Request $request) {
    // Normalize input -> ALWAYS convert to Carbon|null
    $fromInput = $request->query('from');
    $toInput   = $request->query('to');
    $range     = $request->query('range'); // optional '7d'|'30d'
    $min5      = $request->boolean('min5');

    $fromCarbon = null;
    $toCarbon = null;

    if ($fromInput || $toInput) {
        $fromCarbon = $fromInput ? Carbon::parse($fromInput)->startOfDay() : now()->subYears(10);
        $toCarbon   = $toInput   ? Carbon::parse($toInput)->endOfDay()   : now();
    } elseif ($range === '7d') {
        $fromCarbon = now()->subDays(7)->startOfDay();
        $toCarbon   = now()->endOfDay();
    } elseif ($range === '30d') {
        $fromCarbon = now()->subDays(30)->startOfDay();
        $toCarbon   = now()->endOfDay();
    }

    $rangeArr = null;
    if ($fromCarbon || $toCarbon) {
        $rangeArr = ['from' => $fromCarbon, 'to' => $toCarbon];
    }

    $minVotes = $min5 ? 5 : null;

    // --- build global mean C and m (same logic as page)
    $global = Rating::query();
    if ($rangeArr) {
        $global->whereBetween('created_at', [$rangeArr['from'], $rangeArr['to']]);
    }
    $C = (float) ($global->avg('score') ?? 0);
    $m = 10;

    // subquery agg (respect range & minVotes)
    $agg = Rating::query()
        ->select('staff_id')
        ->when($rangeArr, fn($q) => $q->whereBetween('created_at', [$rangeArr['from'], $rangeArr['to']]))
        ->selectRaw('COUNT(*) as ratings_count')
        ->selectRaw('ROUND(AVG(score), 2) as ratings_avg_score')
        ->groupBy('staff_id')
        ->when($minVotes, fn($q) => $q->havingRaw('COUNT(*) >= ?', [$minVotes]));

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

    // Map rows -> plain array for blade
    $rows = $rowsCollection->map(function ($s) {
        // keep it light: do NOT do heavy file_get_contents here
        return [
            'id'    => $s->id,
            'name'  => $s->name,
            'photo' => $s->photo_url, // just a URL, don't convert here
            'avg'   => (float) ($s->ratings_avg_score ?? 0),
            'cnt'   => (int) ($s->ratings_count ?? 0),
            'bayes' => (float) ($s->bayes_score ?? 0),
        ];
    })->values()->toArray();

    $podium = array_slice($rows, 0, 3);

    // Ensure filters array exists for the view
    $filters = [
        'from' => $fromCarbon ? $fromCarbon->toDateString() : null,
        'to'   => $toCarbon ? $toCarbon->toDateString() : null,
        'range'=> $range,
        'min5' => (bool) $minVotes,
    ];

    // Debug HTML view (inspect before generating PDF)
    if ($request->query('debug') === 'html') {
        return view('filament.pages.staff-overall-leaderboard-pdf', compact('podium','rows','filters'));
    }

    // Generate PDF (inline)
    ini_set('memory_limit','512M');
    set_time_limit(120);
    Pdf::setOptions(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

    $pdf = Pdf::loadView('filament.pages.staff-overall-leaderboard-pdf', compact('podium','rows','filters'));
    $pdf->setPaper('a4', 'portrait');

// contoh: dari query filter
$from = $request->query('from');
$to   = $request->query('to');

if ($from && $to) {
    $filename = "Laporan-Kinerja-{$from}-sd-{$to}.pdf";
} else {
    $filename = "Laporan-Kinerja-".now()->format('Ymd-His').".pdf";
}

return response($pdf->output(), 200)
    ->header('Content-Type', 'application/pdf')
    ->header('Content-Disposition', 'inline; filename="'.$filename.'"');


})->name('staff.leaderboard.pdf')->middleware(['auth']);


Route::get('staff/leaderboard/pdf', [\App\Http\Controllers\StaffLeaderboardController::class, 'pdf'])
    ->name('staff.leaderboard.pdf');

