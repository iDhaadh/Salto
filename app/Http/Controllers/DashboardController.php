<?php

namespace App\Http\Controllers;

use App\Models\Lock;
use App\Support\BatteryStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('battery');
        $search = trim($request->query('q', ''));

        $query = Lock::query()->orderByRaw(
            "CASE battery_status WHEN 'flat' THEN 0 WHEN 'low' THEN 1 WHEN 'unknown' THEN 2 ELSE 3 END"
        )->orderBy('name');

        if (in_array($filter, ['normal', 'low', 'flat', 'unknown'], true)) {
            $query->where('battery_status', $filter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('location', 'like', '%' . $search . '%')
                  ->orWhere('salto_id', 'like', '%' . $search . '%');
            });
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
            'locks'   => $locks,
            'counts'  => $counts,
            'filter'  => $filter,
            'search'  => $search,
            'lastSync' => $lastSync,
            'statuses' => BatteryStatus::cases(),
        ]);
    }

    public function sync(): RedirectResponse
    {
        Artisan::call('salto:check');

        return redirect()->route('dashboard')
            ->with('status', 'Sync complete — battery status updated from SALTO.');
    }
}
