<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $status  = Str::lower((string) $request->get('status', 'all'));
        $search  = trim((string) $request->get('search', ''));
        $sort    = (string) $request->get('sort', 'first_asc');
        $perPage = $request->get('per_page', 12);

        // sanitize perPage
        $perPage = $perPage === 'all' ? 'all' : (int) max(1, (int) $perPage);

        $q = Employee::query();

        if (in_array($status, ['active', 'inactive'], true)) {
            $q->where('status', $status);
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name',  'like', "%{$search}%")
                  ->orWhere('email',      'like', "%{$search}%")
                  ->orWhere('username',   'like', "%{$search}%")
                  ->orWhere('position',   'like', "%{$search}%");
            });
        }

        switch ($sort) {
            case 'first_desc': $q->orderBy('first_name', 'desc')->orderBy('last_name'); break;
            case 'last_asc':   $q->orderBy('last_name')->orderBy('first_name'); break;
            case 'last_desc':  $q->orderBy('last_name', 'desc')->orderBy('first_name'); break;
            case 'newest':     $q->orderByDesc('id'); break;
            case 'oldest':     $q->orderBy('id'); break;
            default:           $q->orderBy('first_name')->orderBy('last_name');
        }

        // KPIs respect current search filter
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

        $employees = ($perPage === 'all')
            ? $q->get()
            : $q->paginate($perPage)->appends($request->only('status','search','sort','per_page'));

        return view('employees.index', compact('employees', 'kpis'));
    }

    public function show(int $id)
    {
        $employee = Employee::findOrFail($id);
        return view('employees.show', compact('employee'));
    }

    public function edit(int $id)
    {
        $employee = Employee::findOrFail($id);
        return view('employees.edit', compact('employee'));
    }

    public function store(Request $request)
    {
        [$data, $provision] = $this->validatedData($request);

        // normalize identifiers
        $data['email']    = Str::lower($data['email']);
        $data['username'] = Str::lower($data['username']);

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $employee = Employee::create($data);

        // optional: create/link user
        if ($provision['create_login']) {
            $plainPassword = $provision['login_password'] ?: $this->generatePassword();
            $role          = $provision['role'] ?? 'staff';

            // ensure lowercase role consistency
            $role = Str::lower($role);

            $user = User::updateOrCreate(
                ['email' => $employee->email],
                [
                    'name'      => trim($employee->first_name.' '.$employee->last_name),
                    'password'  => Hash::make($plainPassword),
                    'role'      => $role,
                    'is_active' => $employee->status === 'active',
                ]
            );

            // link back to employee
            $employee->update(['user_id' => $user->id]);

            // show the one-time password to the admin
            session()->flash('login_created_for', $employee->email);
            session()->flash('login_password_plain', $plainPassword);
        }

        return redirect()
            ->route('employees.index', $request->only('status','search'))
            ->with('ok', "Employee {$employee->first_name} {$employee->last_name} created.");
    }

    public function update(Request $request, int $id)
    {
        $employee = Employee::findOrFail($id);
        [$data, $provision] = $this->validatedData($request, $employee->id, isUpdate: true);

        $data['email']    = Str::lower($data['email']);
        $data['username'] = Str::lower($data['username']);

        if ($request->boolean('remove_avatar') && !empty($employee->avatar_path)) {
            Storage::disk('public')->delete($employee->avatar_path);
            $data['avatar_path'] = null;
        }
        if ($request->hasFile('avatar')) {
            if (!empty($employee->avatar_path)) {
                Storage::disk('public')->delete($employee->avatar_path);
            }
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Apply employee updates
        $employee->update($data);

        // Keep linked user's is_active in sync with employee status
        if ($employee->user_id) {
            $linkedUser = User::find($employee->user_id);
            if ($linkedUser) {
                $linkedUser->is_active = $employee->status === 'active';
                $linkedUser->save();
            }
        }

        // optional: provision/link on update
        if ($provision['create_login']) {
            $plainPassword = $provision['login_password']; // may be null; if null keep current user password
            $role          = $provision['role'] ? Str::lower($provision['role']) : null;

            $user = $employee->user_id
                ? User::find($employee->user_id)
                : User::where('email', $employee->email)->first();

            if (!$user) {
                $user = new User([
                    'email' => $employee->email,
                    'name'  => trim($employee->first_name.' '.$employee->last_name),
                ]);
            } else {
                $user->email = $employee->email;
                $user->name  = trim($employee->first_name.' '.$employee->last_name);
            }

            if ($plainPassword) {
                $user->password = Hash::make($plainPassword);
                session()->flash('login_password_plain', $plainPassword);
            }
            if ($role) {
                $user->role = $role;
            }

            // Ensure is_active matches employee status
            $user->is_active = $employee->status === 'active';

            $user->save();
            $employee->update(['user_id' => $user->id]);

            session()->flash('login_created_for', $employee->email);
        }

        return redirect()->route('employees.show', $employee->id)->with('ok', 'Employee updated.');
    }

    public function destroy(int $id)
    {
        $employee = Employee::findOrFail($id);

        if (!empty($employee->avatar_path)) {
            Storage::disk('public')->delete($employee->avatar_path);
        }

        $employee->delete();

        return redirect()->route('employees.index')->with('ok', 'Employee removed.');
    }

    public function toggleBlock(int $id)
    {
        $employee = Employee::findOrFail($id);

        $employee->status = $employee->status === 'active' ? 'inactive' : 'active';
        $employee->save();

        // Sync linked user is_active flag (if exists)
        if ($employee->user_id) {
            $user = User::find($employee->user_id);
            if ($user) {
                $user->is_active = $employee->status === 'active';
                $user->save();
            }
        }

        return back()->with('ok', "Employee is now {$employee->status}.");
    }

    /* ---------------------- Helpers ---------------------- */

    /**
     * Validates employee fields and returns [employeeData, provisionData]
     */
    private function validatedData(Request $request, ?int $ignoreId = null, bool $isUpdate = false): array
    {
        $emailUnique = Rule::unique('employees', 'email');
        $userUnique  = Rule::unique('employees', 'username');

        if ($ignoreId) {
            $emailUnique = $emailUnique->ignore($ignoreId);
            $userUnique  = $userUnique->ignore($ignoreId);
        }

        // normalize role in request before validation, so Rule::in matches
        if ($request->has('role')) {
            $request->merge(['role' => Str::lower((string) $request->input('role'))]);
        }

        $employeeData = $request->validate([
            'first_name'    => ['required','string','max:120'],
            'last_name'     => ['required','string','max:120'],
            'position'      => ['nullable','string','max:160'],
            'email'         => ['required','email','max:190', $emailUnique],
            'username'      => ['required','string','max:120', $userUnique],
            'status'        => ['required', Rule::in(['active','inactive'])],
            'avatar'        => ['nullable','file','image','max:3072'],
            'remove_avatar' => ['sometimes','boolean'],

            // optional provisioning inputs live in the same request
            'create_login'  => ['sometimes','boolean'],
            'role'          => ['sometimes', Rule::in(['masters admin','production manager','sales','inventory','staff','manager'])],
            'login_password'=> ['nullable','string','min:8'],
        ]);

        $provision = [
            'create_login'   => (bool) $request->boolean('create_login'),
            'role'           => $request->input('role'), // already lowercased above
            'login_password' => $request->input('login_password'),
        ];

        return [$employeeData, $provision];
    }

    /** Generate a strong friendly password when not supplied. */
    private function generatePassword(int $length = 12): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
        return collect(range(1, $length))
            ->map(fn() => $chars[random_int(0, strlen($chars)-1)])
            ->implode('');
    }
}
