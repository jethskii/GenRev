<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * List employees with filters, search, sort + KPI counts.
     */
    public function index(Request $request)
    {
        $status   = strtolower((string) $request->get('status', 'all'));
        $search   = trim((string) $request->get('search', ''));
        $sort     = (string) $request->get('sort', 'first_asc');
        $perPage  = $request->get('per_page', 12);

        $q = Employee::query();

        // Filter: status
        if (in_array($status, ['active', 'inactive'], true)) {
            $q->where('status', $status);
        }

        // Filter: search
        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name',  'like', "%{$search}%")
                  ->orWhere('email',      'like', "%{$search}%")
                  ->orWhere('username',   'like', "%{$search}%")
                  ->orWhere('position',   'like', "%{$search}%");
            });
        }

        // Sorting
        switch ($sort) {
            case 'first_desc': $q->orderBy('first_name', 'desc')->orderBy('last_name'); break;
            case 'last_asc':   $q->orderBy('last_name')->orderBy('first_name'); break;
            case 'last_desc':  $q->orderBy('last_name', 'desc')->orderBy('first_name'); break;
            case 'newest':     $q->orderByDesc('id'); break;
            case 'oldest':     $q->orderBy('id'); break;
            default:           $q->orderBy('first_name')->orderBy('last_name'); // first_asc
        }

        // KPI counts (independent of pagination)
        $base = Employee::query();
        if ($search !== '') {
            $base->where(function ($w) use ($search) {
                $w->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name',  'like', "%{$search}%")
                  ->orWhere('email',      'like', "%{$search}%")
                  ->orWhere('username',   'like', "%{$search}%")
                  ->orWhere('position',   'like', "%{$search}%");
            });
        }
        $kpis = [
            'all'      => (clone $base)->count(),
            'active'   => (clone $base)->where('status', 'active')->count(),
            'inactive' => (clone $base)->where('status', 'inactive')->count(),
        ];

        // Paginate or return all
        $employees = ($perPage === 'all')
            ? $q->get()
            : $q->paginate((int) $perPage)->appends($request->only('status','search','sort','per_page'));

        return view('employees.index', compact('employees', 'kpis'));
    }

    /**
     * Show details page.
     */
    public function show(int $id)
    {
        $employee = Employee::findOrFail($id);
        return view('employees.show', compact('employee'));
    }

    /**
     * 👉 EDIT FORM (added)
     */
    public function edit(int $id)
    {
        $employee = Employee::findOrFail($id);
        return view('employees.edit', compact('employee'));
    }

    /**
     * Create (from quick-add modal).
     */
    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        // Password required on create
        $data['password'] = Hash::make($data['password']);

        // Avatar upload (optional)
        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $employee = Employee::create($data);

        return redirect()
            ->route('employees.index', $request->only('status','search'))
            ->with('ok', "Employee {$employee->first_name} {$employee->last_name} created.");
    }

    /**
     * Update record (password optional).
     */
    public function update(Request $request, int $id)
    {
        $employee = Employee::findOrFail($id);

        $data = $this->validatedData($request, $employee->id, isUpdate: true);

        // Update password only if provided
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Remove current avatar?
        if ($request->boolean('remove_avatar') && !empty($employee->avatar_path)) {
            Storage::disk('public')->delete($employee->avatar_path);
            $data['avatar_path'] = null;
        }

        // New avatar? Replace the old one
        if ($request->hasFile('avatar')) {
            if (!empty($employee->avatar_path)) {
                Storage::disk('public')->delete($employee->avatar_path);
            }
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $employee->update($data);

        return redirect()->route('employees.show', $employee->id)->with('ok', 'Employee updated.');
    }

    /**
     * Delete.
     */
    public function destroy(int $id)
    {
        $employee = Employee::findOrFail($id);

        if (!empty($employee->avatar_path)) {
            Storage::disk('public')->delete($employee->avatar_path);
        }

        $employee->delete();

        return redirect()->route('employees.index')->with('ok', 'Employee removed.');
    }

    /**
     * Toggle active/inactive.
     */
    public function toggleBlock(int $id)
    {
        $employee = Employee::findOrFail($id);
        $employee->status = $employee->status === 'active' ? 'inactive' : 'active';
        $employee->save();

        return back()->with('ok', "Employee is now {$employee->status}.");
    }

    /* ---------------------- Helpers ---------------------- */

    private function validatedData(Request $request, ?int $ignoreId = null, bool $isUpdate = false): array
    {
        $emailUnique = Rule::unique('employees', 'email');
        $userUnique  = Rule::unique('employees', 'username');

        if ($ignoreId) {
            $emailUnique = $emailUnique->ignore($ignoreId);
            $userUnique  = $userUnique->ignore($ignoreId);
        }

        return $request->validate([
            'first_name' => ['required','string','max:120'],
            'last_name'  => ['required','string','max:120'],
            'position'   => ['nullable','string','max:160'],
            'email'      => ['required','email','max:190', $emailUnique],
            'username'   => ['required','string','max:120', $userUnique],
            'password'   => [$isUpdate ? 'nullable' : 'required','string','min:6'],
            'status'     => ['required', Rule::in(['active','inactive'])],
            'avatar'     => ['nullable','file','image','max:3072'], // ~3MB
            'remove_avatar' => ['sometimes','boolean'],
        ]);
    }
}
