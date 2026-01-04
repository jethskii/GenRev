<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReservationController extends Controller
{
    /**
     * List reservations in a date range
     */
    public function index(Request $request)
    {
        $start = $request->query('start');
        $end   = $request->query('end');

        // Default: current week
        if (!$start || !$end) {
            $start = Carbon::now()->startOfWeek()->toDateString();
            $end   = Carbon::now()->endOfWeek()->toDateString();
        }

        $reservations = Reservation::with('product')
            ->whereBetween('reserved_date', [$start, $end])
            ->orderBy('reserved_date')
            ->orderBy('id')
            ->get();

        return view('reservations.index', [
            'reservations' => $reservations,
            'filterStart'  => $start,
            'filterEnd'    => $end,
        ]);
    }

    /**
     * Store new reservation
     * Called from "Add reservation for this day"
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reserved_date'  => ['required', 'date'],
            'product_id'     => ['required', 'exists:products,id'],
            'units'          => ['required', 'integer', 'min:1'],
            'unit_type'      => ['required', 'in:pack,bag'],
            'type_label'     => ['nullable', 'string', 'max:255'], // ✅ variant / type
            'notes'          => ['nullable', 'string', 'max:1000'],
            'customer_name'  => ['nullable', 'string', 'max:255'],
            'reference_code' => ['nullable', 'string', 'max:255'],
        ]);

        $reservedDate = Carbon::parse($validated['reserved_date'])->toDateString();

        $reservation = Reservation::create([
            'product_id'     => $validated['product_id'],
            'reserved_date'  => $reservedDate,
            'units'          => $validated['units'],
            'unit_type'      => $validated['unit_type'],
            'type_label'     => $validated['type_label'] ?? null, // ✅ keep what user picked
            'notes'          => $validated['notes'] ?? null,
            'customer_name'  => $validated['customer_name'] ?? null,
            'reference_code' => $validated['reference_code'] ?? null,
            'status'         => 'reserved',
            'user_id'        => Auth::id(),
        ]);

        // Preserve dashboard filters
        $redirectParams = [];
        if ($request->filled('start')) $redirectParams['start'] = $request->input('start');
        if ($request->filled('end'))   $redirectParams['end']   = $request->input('end');

        return redirect()
            ->route('dashboard', $redirectParams)
            ->with('status', 'Reservation saved successfully.');
    }

    /**
     * Update reservation
     */
    public function update(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'reserved_date'  => ['sometimes', 'date'],
            'product_id'     => ['sometimes', 'exists:products,id'],
            'units'          => ['sometimes', 'integer', 'min:1'],
            'unit_type'      => ['sometimes', 'in:pack,bag'],
            'type_label'     => ['nullable', 'string', 'max:255'], // ✅ allow editing variant
            'notes'          => ['nullable', 'string', 'max:1000'],
            'status'         => ['nullable', 'in:reserved,converted,cancelled,expired'],
            'customer_name'  => ['nullable', 'string', 'max:255'],
            'reference_code' => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($validated['reserved_date'])) {
            $validated['reserved_date'] = Carbon::parse($validated['reserved_date'])->toDateString();
        }

        $reservation->update($validated);

        return back()->with('status', 'Reservation updated.');
    }

    /**
     * Cancel reservation
     */
    public function cancel(Request $request, Reservation $reservation)
    {
        $reservation->update(['status' => 'cancelled']);

        return back()->with('status', 'Reservation cancelled.');
    }

    /**
     * Delete reservation
     */
    public function destroy(Request $request, Reservation $reservation)
    {
        $reservation->delete();

        return back()->with('status', 'Reservation deleted.');
    }

    /**
     * AUTO-CONVERT Reservations into Sales
     * Called daily OR when dashboard loads
     */
    public function convertDueReservations()
    {
        $today = Carbon::today()->toDateString();

        $due = Reservation::where('status', 'reserved')
            ->whereDate('reserved_date', '<=', $today)
            ->with('product')
            ->get();

        foreach ($due as $reservation) {

            $product = $reservation->product;
            if (!$product) continue;

            // DEFAULT PRICE — change to your pricing logic
            $unitPrice = $product->default_price ?? 0;

            // ✅ Use the same type/variant the user chose in the reservation
            $typeLabel = $reservation->type_label ?? null;

            // CREATE SALES RECORD
            $sale = Sale::create([
                'product_id'     => $product->id,
                'date'           => $reservation->reserved_date,
                'quantity'       => $reservation->units,
                'unit_type'      => $reservation->unit_type,
                'unit_price'     => $unitPrice,
                'total_amount'   => $unitPrice * $reservation->units,
                'reservation_id' => $reservation->id,
                'type_label'     => $typeLabel, // ✅ push variant into Sales too
            ]);

            // UPDATE status
            $reservation->status = 'converted';
            $reservation->save();
        }
    }
}
