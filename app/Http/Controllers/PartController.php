<?php

namespace App\Http\Controllers;

use App\Models\Part;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));
        $onlyLow = $request->boolean('low');

        $parts = Part::query()
            ->when($term, function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('brand', 'like', $like));
            })
            ->when($onlyLow, fn ($q) => $q->whereColumn('qty', '<=', 'min_stock'))
            ->orderBy('category')->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $lowCount = Part::whereColumn('qty', '<=', 'min_stock')->count();
        $stockValue = Part::selectRaw('COALESCE(SUM(qty * price),0) as v')->value('v');

        return view('parts.index', compact('parts', 'term', 'onlyLow', 'lowCount', 'stockValue'));
    }

    public function create(): View
    {
        return view('parts.form', ['part' => new Part(['unit' => 'шт', 'min_stock' => 2])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $part = Part::create($this->validated($request));

        return redirect()->route('parts.index')->with('success', "Запчасть «{$part->name}» добавлена в каталог");
    }

    public function edit(Part $part): View
    {
        return view('parts.form', ['part' => $part]);
    }

    public function update(Request $request, Part $part): RedirectResponse
    {
        $part->update($this->validated($request));

        return redirect()->route('parts.index')->with('success', 'Карточка запчасти обновлена');
    }

    public function destroy(Part $part): RedirectResponse
    {
        if ($part->orderParts()->exists()) {
            return back()->with('error', 'Нельзя удалить позицию, которая уже продана по наряду');
        }

        $part->delete();

        return redirect()->route('parts.index')->with('success', 'Позиция удалена из каталога');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['nullable', 'string', 'max:64'],
            'brand' => ['nullable', 'string', 'max:64'],
            'category' => ['required', 'string', 'max:64'],
            'unit' => ['required', 'string', 'max:12'],
            'price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'sell_price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'location' => ['nullable', 'string', 'max:32'],
            'is_active' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'Наименование',
            'price' => 'Закупочная цена',
            'sell_price' => 'Цена продажи',
            'min_stock' => 'Минимальный остаток',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
