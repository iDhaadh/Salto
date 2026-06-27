<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Repositories\SaltoLockRepository;
use App\Services\AlertNotifier;
use App\Support\BatteryStatus;
use App\Support\LockSnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'open');

        $query = Alert::query()->with(['lock', 'notifications'])->latest('opened_at');

        if (in_array($status, ['open', 'resolved'], true)) {
            $query->where('status', $status);
        }

        return view('alerts.index', [
            'alerts' => $query->paginate(25)->withQueryString(),
            'status' => $status,
            'openCount' => Alert::where('status', 'open')->count(),
        ]);
    }

    public function resolve(Alert $alert, Request $request, SaltoLockRepository $salto, AlertNotifier $notifier): RedirectResponse
    {
        if ($alert->status !== 'open') {
            return back()->with('status', 'Alert is already resolved.');
        }

        $lock = $alert->lock;

        // Check current live status from SALTO unless admin forces it.
        if (! $request->boolean('force') && $lock) {
            try {
                $reading = $salto->findBySaltoId($lock->salto_id);

                if ($reading && $reading->battery->isAlertable()) {
                    // Still bad in SALTO — reopen/keep open and re-notify.
                    $alert->update(['last_notified_at' => now()]);
                    $snapshot = LockSnapshot::fromLock($lock);
                    $notifier->notify($snapshot, $reading->battery, 'reminder', $alert);

                    return back()->with('error',
                        "Cannot resolve — SALTO still reports \"{$lock->name}\" battery as "
                        . strtoupper($reading->battery->label())
                        . '. Notification resent. Replace the battery first, or use Force Resolve.'
                    );
                }
            } catch (\Throwable) {
                // SALTO unreachable — allow the resolve to proceed.
            }
        }

        $alert->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
        ]);

        return back()->with('status', "Alert for \"{$lock?->name}\" marked as resolved.");
    }

    public function resend(Alert $alert, AlertNotifier $notifier): RedirectResponse
    {
        $lock = $alert->lock;

        if (! $lock) {
            return back()->with('error', 'Lock not found for this alert.');
        }

        $snapshot = LockSnapshot::make(
            saltoId:    $lock->salto_id,
            name:       $lock->name,
            location:   $lock->location ?? '',
            lastSeenAt: $lock->last_seen_at?->toImmutable() ?? now()->toImmutable(),
        );

        $status = BatteryStatus::tryFrom($alert->severity) ?? BatteryStatus::Low;
        $count  = $notifier->notify($snapshot, $status, 'reminder', $alert);

        $alert->update(['last_notified_at' => now()]);

        $message = $count > 0
            ? "Resent {$count} notification(s) for \"{$lock->name}\". Check the queue worker is running."
            : 'No notifications sent — check your channel settings and recipients.';

        return back()->with('status', $message);
    }
}
