<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $type = (string) $request->query('type', '');

        $movements = StockMovement::with(['part', 'order', 'user'])
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($request->filled('part'), fn ($q) => $q->where('part_id', $request->query('part')))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $totals = [
            'in_qty' => StockMovement::where('type', 'in')->sum('qty'),
            'in_sum' => StockMovement::where('type', 'in')->sum('qty'),
            'out_qty' => StockMovement::where('type', 'out')->sum('qty'),
            'out_sum' => (float) StockMovement::where('type', 'out')
                ->selectRaw('COALESCE(SUM(qty * price),0) as s')->value('s'),
        ];

        return view('stock.index', [
            'movements' => $movements,
            'type' => $type,
            'totals' => $totals,
            'types' => StockMovement::types(),
            'parts' => Part::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:in,out,adjust'],
            'part_id' => ['required', 'exists:parts,id'],
            'qty' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], [
            'type' => 'Операция',
            'part_id' => 'Запчасть',
            'qty' => 'Количество',
        ]);

        $part = Part::findOrFail($data['part_id']);
        $qty = (float) $data['qty'];
        $price = (float) ($data['price'] ?? $part->price);

        if ($data['type'] === 'out' && (float) $part->qty < $qty) {
            return back()->withErrors(['qty' => "Недостаточно на складе: доступно {$part->qty} {$part->unit}"])->withInput();
        }

        DB::transaction(function () use ($part, $qty, $price, $data) {
            $delta = match ($data['type']) {
                'in' => $qty,
                'out' => -$qty,
                'adjust' => 0,
            };

            if ($data['type'] === 'adjust') {
                $delta = $qty - (float) $part->qty;
            }

            $part->update(['qty' => round((float) $part->qty + $delta, 2)]);

            StockMovement::create([
                'part_id' => $part->id,
                'type' => $data['type'],
                'qty' => abs($delta) ?: $qty,
                'price' => $price,
                'user_id' => auth()->id(),
                'note' => $data['note'] ?? null,
            ]);
        });

        $labels = StockMovement::types();

        return back()->with('success', "Операция «{$labels[$data['type']]}» выполнена: {$part->name}");
    }

    public function destroy(StockMovement $movement): RedirectResponse
    {
        $part = $movement->part;
        $delta = $movement->signedQty();
        $newQty = round((float) $part->qty - $delta, 2);

        DB::transaction(function () use ($movement, $part, $newQty) {
            $part->update(['qty' => max(0, $newQty)]);
            $movement->delete();
        });

        return back()->with('success', 'Движение отменено, остаток пересчитан');
    }
}
