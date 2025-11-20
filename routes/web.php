<?php
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Models\Rating;
use App\Models\Staff;

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

    // Hitung global mean C
    $global = Rating::query();
    if ($range) {
        $global->whereBetween('created_at', [$range['from'], $range['to']]);
    }
    $C = (float) ($global->avg('score') ?? 0);
    $m = 10;

    // Subquery aggregate per staff
    $agg = Rating::query()
        ->select('staff_id')
        ->when($range, fn($q) => $q->whereBetween('created_at', [$range['from'], $range['to']]))
        ->selectRaw('COUNT(*) as ratings_count')
        ->selectRaw('ROUND(AVG(score), 2) as ratings_avg_score')
        ->groupBy('staff_id')
        ->when($minVotes, fn($q) => $q->havingRaw('COUNT(*) >= ?', [$minVotes]));

    $rows = Staff::query()
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
        ->get(['id', 'name', 'photo_path', 'ratings_avg_score', 'ratings_count', 'bayes_score'])
        ->map(function ($s) {
            $photo = $s->photo_path
                ? (str_starts_with($s->photo_path, 'http') ? $s->photo_path : asset($s->photo_path))
                : null;

            return [
                'id'    => $s->id,
                'name'  => $s->name,
                'photo' => $photo,
                'avg'   => (float) ($s->ratings_avg_score ?? 0),
                'cnt'   => (int) ($s->ratings_count ?? 0),
                'bayes' => (float) ($s->bayes_score ?? 0),
            ];
        })
        ->values();

    $podium = $rows->take(3);

    $pdf = Pdf::loadView('filament.pages.staff-overall-leaderboard-pdf', [
        'podium'  => $podium,
        'rows'    => $rows,
        'filters' => [
            'from' => $from ? $from->toDateString() : null,
            'to'   => $to ? $to->toDateString() : null,
            'range' => $request->query('range'),
            'min5' => (bool) $minVotes,
        ],
    ]);

    $pdf->setPaper('a4', 'portrait');

    return response($pdf->stream('staff-leaderboard.pdf'), 200)
        ->header('Content-Type', 'application/pdf');
})->name('staff.leaderboard.pdf')->middleware(['auth']);
