<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ReturnOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    // ── Payments ────────────────────────────────────────────────────────────────
    public function getPayments(): JsonResponse
    {
        $payments = Payment::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $payments]);
    }

    public function storePayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'payment_value' => 'required|numeric|min:0',
            'date'          => 'required|date',
        ]);

        $payment = Payment::create($validated);

        return response()->json([
            'message' => 'Payment added successfully!',
            'data'    => $payment,
        ], 201);
    }

    public function updatePayment(Request $request, $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'payment_value' => 'required|numeric|min:0',
            'date'          => 'required|date',
        ]);

        $payment->update($validated);

        return response()->json([
            'message' => 'Payment updated successfully!',
            'data'    => $payment,
        ]);
    }

    public function destroyPayment($id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return response()->json(['message' => 'Payment deleted successfully!']);
    }

    // ── Orders ──────────────────────────────────────────────────────────────────
    public function getOrders(): JsonResponse
    {
        $orders = Order::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $orders]);
    }

    public function storeOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => 'required|string|max:255',
            'date'         => 'required|date',
        ]);

        $order = Order::create($validated);

        return response()->json([
            'message' => 'Order added successfully!',
            'data'    => $order,
        ], 201);
    }

    public function updateOrder(Request $request, $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $validated = $request->validate([
            'order_number' => 'required|string|max:255',
            'date'         => 'required|date',
        ]);

        $order->update($validated);

        return response()->json([
            'message' => 'Order updated successfully!',
            'data'    => $order,
        ]);
    }

    public function destroyOrder($id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully!']);
    }

    // ── Return Orders ───────────────────────────────────────────────────────────
    public function getReturnOrders(): JsonResponse
    {
        $returnOrders = ReturnOrder::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $returnOrders]);
    }

    public function storeReturnOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'return_order_number' => 'required|string|max:255',
            'date'                => 'required|date',
        ]);

        $returnOrder = ReturnOrder::create($validated);

        return response()->json([
            'message' => 'Return order added successfully!',
            'data'    => $returnOrder,
        ], 201);
    }

    public function updateReturnOrder(Request $request, $id): JsonResponse
    {
        $returnOrder = ReturnOrder::findOrFail($id);
        $validated = $request->validate([
            'return_order_number' => 'required|string|max:255',
            'date'                => 'required|date',
        ]);

        $returnOrder->update($validated);

        return response()->json([
            'message' => 'Return order updated successfully!',
            'data'    => $returnOrder,
        ]);
    }

    public function destroyReturnOrder($id): JsonResponse
    {
        $returnOrder = ReturnOrder::findOrFail($id);
        $returnOrder->delete();

        return response()->json(['message' => 'Return order deleted successfully!']);
    }

    // ── Web Views ───────────────────────────────────────────────────────────────
    public function paymentsIndex()
    {
        return view('payments');
    }

    public function ordersIndex()
    {
        return view('orders');
    }

    public function returnsIndex()
    {
        return view('returns');
    }
}
