<?php

namespace App\Http\Controllers;

use App\Models\DemandEvent;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DemandEventController extends Controller
{
    /* ================================================================
     |  Small helpers
     * ================================================================ */

    /**
     * Parse a date from request and normalize as date-only (Y-m-d).
     */
    private function parseDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    /**
     * Normalize a boolean-ish input.
     */
    private function parseBool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Build a simple color + label based on event_type.
     * This is for the calendar UI (e.g. badge colors).
     */
    private function calendarStyleFor(string $type): array
    {
        switch ($type) {
            case 'reservation':
                return ['class' => 'event-reservation', 'color' => '#2563eb']; // blue
            case 'holiday':
                return ['class' => 'event-holiday', 'color' => '#f97316'];     // orange
            case 'promo':
                return ['class' => 'event-promo', 'color' => '#22c55e'];       // green
            default:
                return ['class' => 'event-generic', 'color' => '#6b7280'];     // gray
        }
    }

    /**
     * Serialize a DemandEvent into a calendar-friendly structure.
     */
    private function toCalendarEvent(DemandEvent $event): array
    {
        $style = $this->calendarStyleFor($event->event_type);

        return [
            'id'             => $event->id,
            'title'          => $event->title,
            'event_type'     => $event->event_type,
            'status'         => $event->status,
            'product_id'     => $event->product_id,
            'product_name'   => optional($event->product)->product_name,
            'start_date'     => $event->start_date,
            'end_date'       => $event->end_date,
            'reserved_qty'   => (float) $event->reserved_qty,
            'unit_type'      => $event->unit_type ?? 'pack',
            'notes'          => $event->notes,
            'is_holiday'     => $event->event_type === 'holiday',
            'is_reservation' => $event->event_type === 'reservation',
            'is_promo'       => $event->event_type === 'promo',
            'style_class'    => $style['class'],
            'style_color'    => $style['color'],
        ];
    }

    /* ================================================================
     |  Pages
     * ================================================================ */

    /**
     * Simple index page to manage demand events.
     * - If the request expects JSON, returns calendar data instead.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return $this->calendar($request);
        }

        $products = Product::orderBy('product_name')->get(['id', 'product_name']);

        return view('demand-events.index', [
            'products' => $products,
        ]);
    }

    /* ================================================================
     |  Calendar JSON endpoint
     * ================================================================ */

    /**
     * JSON endpoint for the event calendar.
     *
     * GET /demand-events/calendar?start=2025-01-01&end=2025-01-31
     *
     * Returns:
     * - events: list of reservations / holidays / promos within range
     * - demandAdjustments: daily reserved demand per product_id
     * - dailyStats: per day revenue, quantity, demand level, holiday flags,
     *               and product breakdown by variant + unit_type
     */
    public function calendar(Request $request)
    {
        $start = $this->parseDate($request->query('start'));
        $end   = $this->parseDate($request->query('end'));

        if (!$start || !$end) {
            // Default: current month if not provided
            $start = Carbon::now()->startOfMonth()->toDateString();
            $end   = Carbon::now()->endOfMonth()->toDateString();
        }

        $startCarbon = Carbon::parse($start);
        $endCarbon   = Carbon::parse($end);
        $today       = Carbon::today();

        // grab events that touch the selected range
        $events = DemandEvent::with('product')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_date')
            ->get();

        // list of events for the UI
        $calendarEvents = $events->map(fn ($e) => $this->toCalendarEvent($e));

        // daily demand adjustments (for forecasts / heatmap)
        // structure: [ 'Y-m-d' => [product_id => reserved_qty, ...], ... ]
        $demandAdjustments = [];

        foreach ($events as $event) {
            if ($event->event_type !== 'reservation' || $event->reserved_qty <= 0 || !$event->product_id) {
                continue;
            }

            $current = Carbon::parse($event->start_date);
            $last    = Carbon::parse($event->end_date);

            // distribute the reserved quantity across the days in the event range
            $daysSpan = max(1, $current->diffInDays($last) + 1);
            $perDay   = $event->reserved_qty / $daysSpan;

            while ($current->lte($last)) {
                $dayKey = $current->toDateString();

                if (!isset($demandAdjustments[$dayKey])) {
                    $demandAdjustments[$dayKey] = [];
                }

                if (!isset($demandAdjustments[$dayKey][$event->product_id])) {
                    $demandAdjustments[$dayKey][$event->product_id] = 0.0;
                }

                $demandAdjustments[$dayKey][$event->product_id] += $perDay;
                $current->addDay();
            }
        }

        // -------------------------------------------
        // Per-day stats: revenue, qty, demand level
        // -------------------------------------------

        // Initialize dailyStats for each date in the range
        $dailyStats = [];
        $cursor     = $startCarbon->copy();

        while ($cursor->lte($endCarbon)) {
            $dayKey = $cursor->toDateString();

            $dailyStats[$dayKey] = [
                'date'               => $dayKey,
                'total_revenue'      => 0.0,
                'total_qty'          => 0.0,
                'order_count'        => 0,
                'product_count'      => 0,
                'demand_level'       => 'no_data', // low | normal | high | forecast_*
                'is_past'            => $cursor->lt($today),
                'is_today'           => $cursor->isSameDay($today),
                'is_future'          => $cursor->gt($today),
                'is_holiday'         => false,
                'is_holiday_season'  => false,
                'is_weekend'         => in_array($cursor->dayOfWeekIso, [6, 7], true),
                'tags'               => [],
                // 👇 NEW: product breakdown per day (variant + unit_type)
                'products'           => [],
            ];

            $cursor->addDay();
        }

        // Mark holiday days + "holiday season" window (±2 days)
        foreach ($events as $event) {
            if ($event->event_type !== 'holiday') {
                continue;
            }

            $eventStart = Carbon::parse($event->start_date);
            $eventEnd   = Carbon::parse($event->end_date);

            // Exact holiday range
            $cursorHoliday = $eventStart->copy();
            while ($cursorHoliday->lte($eventEnd)) {
                $key = $cursorHoliday->toDateString();
                if (isset($dailyStats[$key])) {
                    $dailyStats[$key]['is_holiday'] = true;
                    $dailyStats[$key]['tags'][]     = 'holiday';
                }
                $cursorHoliday->addDay();
            }

            // Holiday season window: ±2 days around the event
            $seasonStart = $eventStart->copy()->subDays(2);
            $seasonEnd   = $eventEnd->copy()->addDays(2);

            $cursorSeason = $seasonStart->copy();
            while ($cursorSeason->lte($seasonEnd)) {
                $key = $cursorSeason->toDateString();
                if (isset($dailyStats[$key]) && !$dailyStats[$key]['is_holiday']) {
                    $dailyStats[$key]['is_holiday_season'] = true;
                    $dailyStats[$key]['tags'][]           = 'holiday_season';
                }
                $cursorSeason->addDay();
            }
        }

        // Pull sales per day in this range (summary)
        $QTY   = 'COALESCE(quantity_kg, quantity, 0)';
        $UNIT  = 'COALESCE(unit_price, price, 0)';
        $REVEX = "$QTY * $UNIT";

        $salesDaily = Sale::whereBetween(DB::raw('DATE(date)'), [$start, $end])
            ->selectRaw("
                DATE(date) as d,
                SUM($QTY) as qty,
                SUM($REVEX) as revenue,
                COUNT(*) as order_count,
                COUNT(DISTINCT product_id) as product_count
            ")
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        // 🔍 Detailed per-day product breakdown:
        // grouped by product + variant + unit_type
        $salesByProduct = Sale::leftJoin('products as p', 'p.id', '=', 'sales.product_id')
            ->whereBetween(DB::raw('DATE(sales.date)'), [$start, $end])
            ->selectRaw("
                DATE(sales.date) as d,
                sales.product_id,
                COALESCE(p.product_name, sales.product, 'Product') as product_name,
                NULLIF(TRIM(sales.type_label), '') as sale_variant,
                COALESCE(NULLIF(TRIM(sales.unit_type), ''), 'pack') as unit_type,
                SUM($QTY) as quantity,
                SUM($REVEX) as revenue
            ")
            ->groupBy(
                'd',
                'sales.product_id',
                'p.product_name',
                'sales.product',
                'sales.type_label',
                'sales.unit_type'
            )
            ->get();

        // Max revenue among past days in this range
        $maxRevPast = 0.0;
        foreach ($salesDaily as $d => $row) {
            if ($d <= $today->toDateString()) {
                $maxRevPast = max($maxRevPast, (float) $row->revenue);
            }
        }

        // Fill dailyStats with sales data and demand classification for past days
        foreach ($salesDaily as $d => $row) {
            if (!isset($dailyStats[$d])) {
                continue;
            }

            $dailyStats[$d]['total_revenue'] = (float) $row->revenue;
            $dailyStats[$d]['total_qty']     = (float) $row->qty;
            $dailyStats[$d]['order_count']   = (int) $row->order_count;
            $dailyStats[$d]['product_count'] = (int) $row->product_count;

            if ($maxRevPast > 0 && $d <= $today->toDateString()) {
                $ratio = $row->revenue / $maxRevPast;

                if ($ratio >= 0.7 || $dailyStats[$d]['is_holiday']) {
                    $dailyStats[$d]['demand_level'] = 'high';
                } elseif ($ratio >= 0.4) {
                    $dailyStats[$d]['demand_level'] = 'normal';
                } else {
                    $dailyStats[$d]['demand_level'] = 'low';
                }
            } elseif ($d <= $today->toDateString() && $row->revenue > 0) {
                $dailyStats[$d]['demand_level'] = 'normal';
            }
        }

        // Attach product list (variant + unit_type) into each day's stats
        foreach ($salesByProduct as $row) {
            $dayKey = $row->d;

            if (!isset($dailyStats[$dayKey])) {
                continue;
            }

            $variant = trim($row->sale_variant ?? '');
            $unit    = strtolower($row->unit_type ?? 'pack');

            $baseName = trim($row->product_name . ' ' . $variant);
            $display  = $baseName . ' (' . $unit . ')';

            $dailyStats[$dayKey]['products'][] = [
                'product_id'    => $row->product_id,
                'product_name'  => $row->product_name,
                'sale_variant'  => $variant,
                'unit_type'     => $unit,      // 👈 per pack / per bag
                'quantity'      => (float) $row->quantity,
                'revenue'       => (float) $row->revenue,
                'display_label' => $display,
            ];
        }

        // For future days, use holiday / season / promo signals to mark forecasted demand
        foreach ($dailyStats as $dayKey => &$stat) {
            $dayCarbon = Carbon::parse($dayKey);

            if ($dayCarbon->gt($today)) {
                $hasPromo = $events->contains(function (DemandEvent $e) use ($dayKey) {
                    if ($e->event_type !== 'promo') {
                        return false;
                    }
                    $start = Carbon::parse($e->start_date);
                    $end   = Carbon::parse($e->end_date);

                    return $dayKey >= $start->toDateString() && $dayKey <= $end->toDateString();
                });

                if ($stat['is_holiday']) {
                    $stat['demand_level'] = 'forecast_high';
                } elseif ($stat['is_holiday_season'] || $hasPromo) {
                    $stat['demand_level'] = 'forecast_medium';
                } else {
                    $stat['demand_level'] = $stat['demand_level'] === 'no_data'
                        ? 'forecast_normal'
                        : $stat['demand_level'];
                }
            } else {
                // Slight boost for past holiday season days if they still have "no_data" or "low"
                if (in_array($stat['demand_level'], ['no_data', 'low'], true) && $stat['is_holiday_season']) {
                    $stat['demand_level'] = 'normal';
                }
            }
        }
        unset($stat); // break reference

        return response()->json([
            'start'             => $start,
            'end'               => $end,
            'events'            => $calendarEvents,
            'demandAdjustments' => $demandAdjustments,
            'dailyStats'        => $dailyStats,
        ]);
    }

    /* ================================================================
     |  CRUD
     * ================================================================ */

    /**
     * Store a new demand event (reservation / promo / holiday).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'event_type'   => ['required', 'string', 'in:reservation,holiday,promo,other'],
            'product_id'   => ['nullable', 'exists:products,id'],
            'start_date'   => ['required', 'date'],
            'end_date'     => ['required', 'date', 'after_or_equal:start_date'],
            'reserved_qty' => ['nullable', 'numeric', 'min:0'],
            'unit_type'    => ['nullable', 'string', 'max:50'],
            'status'       => ['nullable', 'string', 'in:planned,confirmed,cancelled,fulfilled'],
            'notes'        => ['nullable', 'string'],
        ]);

        $event = new DemandEvent();
        $event->title        = $validated['title'];
        $event->event_type   = $validated['event_type'];
        $event->product_id   = $validated['product_id'] ?? null;
        $event->start_date   = $this->parseDate($validated['start_date']);
        $event->end_date     = $this->parseDate($validated['end_date']);
        $event->reserved_qty = $validated['reserved_qty'] ?? 0;
        $event->unit_type    = $validated['unit_type'] ?? 'pack';
        $event->status       = $validated['status'] ?? 'planned';
        $event->notes        = $validated['notes'] ?? null;
        $event->save();

        return $request->wantsJson()
            ? response()->json($this->toCalendarEvent($event), 201)
            : redirect()->back()->with('success', 'Demand event created.');
    }

    public function show(DemandEvent $demandEvent)
    {
        $demandEvent->load('product');

        return response()->json($this->toCalendarEvent($demandEvent));
    }

    /**
     * Update an existing demand event.
     */
    public function update(Request $request, DemandEvent $demandEvent)
    {
        $validated = $request->validate([
            'title'        => ['sometimes', 'required', 'string', 'max:255'],
            'event_type'   => ['sometimes', 'required', 'string', 'in:reservation,holiday,promo,other'],
            'product_id'   => ['sometimes', 'nullable', 'exists:products,id'],
            'start_date'   => ['sometimes', 'required', 'date'],
            'end_date'     => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
            'reserved_qty' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'unit_type'    => ['sometimes', 'nullable', 'string', 'max:50'],
            'status'       => ['sometimes', 'nullable', 'string', 'in:planned,confirmed,cancelled,fulfilled'],
            'notes'        => ['sometimes', 'nullable', 'string'],
        ]);

        if (array_key_exists('title', $validated)) {
            $demandEvent->title = $validated['title'];
        }
        if (array_key_exists('event_type', $validated)) {
            $demandEvent->event_type = $validated['event_type'];
        }
        if (array_key_exists('product_id', $validated)) {
            $demandEvent->product_id = $validated['product_id'];
        }
        if (array_key_exists('start_date', $validated)) {
            $demandEvent->start_date = $this->parseDate($validated['start_date']);
        }
        if (array_key_exists('end_date', $validated)) {
            $demandEvent->end_date = $this->parseDate($validated['end_date']);
        }
        if (array_key_exists('reserved_qty', $validated)) {
            $demandEvent->reserved_qty = $validated['reserved_qty'] ?? 0;
        }
        if (array_key_exists('unit_type', $validated)) {
            $demandEvent->unit_type = $validated['unit_type'] ?? 'pack';
        }
        if (array_key_exists('status', $validated)) {
            $demandEvent->status = $validated['status'] ?? 'planned';
        }
        if (array_key_exists('notes', $validated)) {
            $demandEvent->notes = $validated['notes'];
        }

        $demandEvent->save();

        return $request->wantsJson()
            ? response()->json($this->toCalendarEvent($demandEvent))
            : redirect()->back()->with('success', 'Demand event updated.');
    }

    /**
     * Soft-delete / remove the event.
     */
    public function destroy(Request $request, DemandEvent $demandEvent)
    {
        $demandEvent->delete();

        return $request->wantsJson()
            ? response()->json(['ok' => true])
            : redirect()->back()->with('success', 'Demand event removed.');
    }
}
