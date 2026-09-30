<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        $posts = Post::withCount('orders')
            ->orderBy('name')
            ->get()
            ->map(function (Post $post) {
                $busy = Order::where('post_id', $post->id)
                    ->whereIn('status', ['in_progress', 'waiting', 'ready'])
                    ->withSum('works', 'norm_hours')
                    ->get()
                    ->sum(fn (Order $order) => (float) $order->works_sum);

                return [
                    'post' => $post,
                    'busy' => round($busy, 1),
                    'percent' => min(150, (int) round($busy / max(1, $post->capacity_hours * 22) * 100)),
                ];
            });

        return view('posts.index', compact('posts'));
    }

    public function create(): View
    {
        return view('posts.form', ['post' => new Post(['capacity_hours' => 8, 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Post::create($this->validated($request));

        return redirect()->route('posts.index')->with('success', 'Пост добавлен');
    }

    public function edit(Post $post): View
    {
        return view('posts.form', ['post' => $post]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $post->update($this->validated($request));

        return redirect()->route('posts.index')->with('success', 'Пост обновлён');
    }

    public function destroy(Post $post): RedirectResponse
    {
        if ($post->orders()->exists()) {
            return back()->with('error', 'Нельзя удалить пост, к которому привязаны заказ-наряды — деактивируйте его');
        }

        $post->delete();

        return back()->with('success', 'Пост удалён');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['nullable', 'string', 'max:20'],
            'capacity_hours' => ['required', 'integer', 'min:1', 'max:24'],
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:100000'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'Название поста',
            'capacity_hours' => 'Часов в смене',
            'hourly_rate' => 'Базовая ставка',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
