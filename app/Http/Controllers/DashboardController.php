<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Material;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();
        $totalMaterialsWeight = Material::sum('quantity_kg');
        $totalMaterialsCount = Material::count();

        $totalRevenue = Sale::sum(DB::raw('quantity * price'));
        $totalSales = Sale::count();

        $recentSales = Sale::orderBy('date', 'desc')->take(5)->get();

        $weeklyProduction = Sale::select('product_name', DB::raw('SUM(quantity) as weekly_total'))
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->groupBy('product_name')
            ->get();

        $weeklySales = Sale::selectRaw("DATE(date) as day, SUM(quantity * price) as total")
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return view('dashboard', compact(
            'totalProducts',
            'totalMaterialsWeight',
            'totalMaterialsCount',
            'totalRevenue',
            'totalSales',
            'recentSales',
            'weeklyProduction',
            'weeklySales'
        ));
    }
}
