<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Userlog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class UserlogController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('developer'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $userlogs_by_date = Userlog::query()
            ->select('userlogs.country_name', 'userlogs.country_code', 'userlogs.created_at', 'users.name', 'users.is_developer')
            ->leftjoin('users', 'userlogs.user_id', '=', 'users.id')
            ->where('userlogs.user_id', '!=', 2)
            ->where('userlogs.created_at', '>=', carbon::now()->subMonths(3))
            ->orderBy('userlogs.created_at', 'desc')
            ->get()
            ->groupBy('date');

        return view('back.userslog.index', compact('userlogs_by_date'));
    }

    public function statsCountry()
    {
        abort_if(Gate::denies('developer'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $statistics = Userlog::select('country_name')
            ->selectRaw('count(*) as visitors')
            ->where('user_id', '!=', 2)
            ->whereNotNull('country_name')
            ->groupBy('country_name')
            ->get();

        $data['chart_data'] = json_encode([
            'label' => $statistics->pluck('country_name'),
            'data' => $statistics->pluck('visitors')
        ]);

        return view('back.userslog.stats-country', $data);
    }

    public function statsPeriode()
    {
        $statistics = match (session('APP.PERIOD')) {
            'year' => Userlog::selectRaw('YEAR(created_at) as period')
                ->selectRaw('count(*) as `visitors`')
                ->where('user_id', '!=', 2)
                ->groupBy('period')
                ->orderBy('period')
                ->get(),
            'month' => Userlog::selectRaw('LPAD(MONTH(created_at), 2, 0) AS period')
                ->selectRaw('count(*) as visitors')
                ->where('user_id', '!=', 2)
                ->whereYear('created_at', session('APP.YEAR'))
                ->groupBy('period')
                ->orderBy('period')
                ->get(),
            'week' => Userlog::selectRaw('LPAD(WEEK(created_at, 0), 2, 0) AS period')
                ->selectRaw('count(*) as visitors')
                ->where('user_id', '!=', 2)
                ->whereYear('created_at', session('APP.YEAR'))
                ->groupBy('period')
                ->orderBy('period')
                ->get(),
            'day' => Userlog::selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d") AS period')
                ->selectRaw('count(*) as visitors')
                ->where('user_id', '!=', 2)
                ->whereYear('created_at', session('APP.YEAR'))
                ->groupBy('period')
                ->orderBy('period')
                ->get()
        };

        $data['chart_data'] = json_encode([
            'label' => $statistics->pluck('period'),
            'data' => $statistics->pluck('visitors')
        ]);

        return view('back.userslog.stats-periode', $data);
    }
}
