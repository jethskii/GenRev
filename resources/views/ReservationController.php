<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;

class ReservationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reserved_date' => ['required', 'date'],
            'units'        => ['required', 'numeric', 'min:1'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        // Optional: link to user / plant / whatever context you have
        Reservation::create([
            'reserved_date' => $validated['reserved_date'],
            'units'         => $validated['units'],
            'notes'         => $validated['notes'] ?? null,
            'user_id'       => auth()->id(), // if applicable
        ]);

        return back()->with('success', 'Reservation saved successfully.');
    }
}
