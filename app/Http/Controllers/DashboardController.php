<?php

namespace App\Http\Controllers;

use App\Models\Lock;
use App\Support\BatteryStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('battery');

        $query = Lock::query()->orderByRaw(
            "CASE battery_status WHEN 'flat' THEN 0 WHEN 'low' THEN 1 WHEN 'unknown' THEN 2 ELSE 3 END"
        )->orderBy('name');

        if (in_array($filter, ['normal', 'low', 'flat', 'unknown'], true)) {
            $query->where('battery_status', $filter);
        }

        $locks = $query->paginate(25)->withQueryString();

        $counts = [
            'total' => Lock::count(),
            'flat' => Lock::where('battery_status', 'flat')->count(),
            'low' => Lock::where('battery_status', 'low')->count(),
            'unknown' => Lock::where('battery_status', 'unknown')->count(),
        ];

        $lastSync = Lock::max('synced_at');

        return view('dashboard.index', [
            'locks' => $locks,
            'counts' => $counts,
            'filter' => $filter,
            'lastSync' => $lastSync,
            'statuses' => BatteryStatus::cases(),
        ]);
    }
}
