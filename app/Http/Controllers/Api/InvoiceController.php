<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * REST API endpoints to query processed invoices.
 * Optional — use only if you need API access.
 */
class InvoiceController extends Controller
{
    // GET /api/invoices
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()->with('store');

        if ($store = $request->get('store')) {
            $query->byStore($store);
        }
        if ($lang = $request->get('lang')) {
            $query->where('document_language', $lang);
        }
        if ($from = $request->get('from')) {
            $query->where('invoice_date', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->where('invoice_date', '<=', $to);
        }

        $invoices = $query->orderByDesc('processed_at')
                          ->paginate($request->get('per_page', 20));

        return response()->json($invoices);
    }

    // GET /api/invoices/{id}
    public function show(int $id): JsonResponse
    {
        $invoice = Invoice::with('store')->findOrFail($id);
        return response()->json($invoice);
    }

    // GET /api/stores
    public function stores(): JsonResponse
    {
        $stores = Store::withCount('invoices')->get();
        return response()->json($stores);
    }

    // GET /api/stores/{code}/invoices
    public function storeInvoices(string $code): JsonResponse
    {
        $invoices = Invoice::byStore($code)
                           ->orderByDesc('processed_at')
                           ->get();

        return response()->json([
            'store'    => $code,
            'count'    => $invoices->count(),
            'invoices' => $invoices,
        ]);
    }

    // GET /api/stats
    public function stats(): JsonResponse
    {
        return response()->json([
            'total_invoices'   => Invoice::count(),
            'total_stores'     => Store::count(),
            'arabic_invoices'  => Invoice::arabic()->count(),
            'english_invoices' => Invoice::english()->count(),
            'today_processed'  => Invoice::processedToday()->count(),
            'by_store'         => Invoice::selectRaw('store_code, count(*) as count')
                                         ->groupBy('store_code')
                                         ->pluck('count', 'store_code'),
            'by_currency'      => Invoice::selectRaw('currency, count(*) as count')
                                         ->whereNotNull('currency')
                                         ->groupBy('currency')
                                         ->pluck('count', 'currency'),
        ]);
    }
}
