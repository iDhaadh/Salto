<?php

namespace App\Http\Controllers;

use App\Services\SaltoApiService;
use Illuminate\Http\Request;

class DoorController extends Controller
{
    public function __construct(private SaltoApiService $salto) {}

    public function index()
    {
        try {
            $doors = $this->salto->getDoors();
            usort($doors, fn ($a, $b) => strcmp($a['Name'] ?? '', $b['Name'] ?? ''));
        } catch (\Throwable $e) {
            $doors = [];
            session()->flash('error', 'Could not load door list: ' . $e->getMessage());
        }

        return view('doors.index', compact('doors'));
    }

    public function open(Request $request, int $id)
    {
        $name = $request->input('name', "Door #$id");

        try {
            $uuid = $this->salto->openDoor($id);
            session()->flash('status', "Open command sent for $name" . ($uuid ? " (ref: $uuid)" : ''));
        } catch (\Throwable $e) {
            session()->flash('error', "Failed to open $name: " . $e->getMessage());
        }

        return back();
    }
}
