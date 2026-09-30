<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = User::with('post')
            ->withCount(['works as open_works' => fn ($q) => $q->where('status', 'planned')])
            ->orderBy('name')
            ->get()
            ->map(function (User $employee) {
                $doneWorks = $employee->works()->where('status', 'done')->get(['norm_hours', 'price', 'hourly_rate']);

                return [
                    'user' => $employee,
                    'hours' => round((float) $doneWorks->sum('norm_hours'), 1),
                    'revenue' => (float) $doneWorks->sum('price'),
                    'profit' => round((float) $doneWorks->sum('price') - $doneWorks->sum(
                        fn ($w) => (float) $w->norm_hours * (float) $w->hourly_rate
                    ), 2),
                ];
            });

        return view('employees.index', ['employees' => $employees, 'roles' => User::roles()]);
    }

    public function create(): View
    {
        return view('employees.form', [
            'employee' => new User(['role' => 'mechanic', 'is_active' => true]),
            'posts' => Post::orderBy('name')->get(),
            'roles' => User::roles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return redirect()->route('employees.index')->with('success', 'Сотрудник добавлен');
    }

    public function edit(User $employee): View
    {
        return view('employees.form', [
            'employee' => $employee,
            'posts' => Post::orderBy('name')->get(),
            'roles' => User::roles(),
        ]);
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $data = $this->validated($request, $employee);

        if (! isset($data['password']) || $data['password'] === '') {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $employee->update($data);

        return redirect()->route('employees.index')->with('success', 'Данные сотрудника обновлены');
    }

    public function destroy(User $employee): RedirectResponse
    {
        if ($employee->id === auth()->id()) {
            return back()->with('error', 'Нельзя удалить собственную учётную запись');
        }
        if ($employee->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Должен остаться хотя бы один управляющий');
        }

        $employee->update(['is_active' => false]);
        $employee->works()->update(['mechanic_id' => null]);
        $employee->orders()->update(['mechanic_id' => null]);

        return back()->with('success', 'Сотрудник деактивирован, наряды переназначены вручную');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $employee = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users')->ignore($employee?->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'position' => ['nullable', 'string', 'max:80'],
            'role' => ['required', 'in:admin,advisor,mechanic'],
            'post_id' => ['nullable', 'exists:posts,id'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:6'],
        ], [], [
            'name' => 'ФИО',
            'email' => 'E-mail',
            'password' => 'Пароль',
            'hourly_rate' => 'Ставка',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
