<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $status  = strtolower((string) $request->get('status', 'all'));
        $search  = trim((string) $request->get('search', ''));
        $sort    = (string) $request->get('sort', 'first_asc');
        $perPage = $request->get('per_page', 12);

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
            : $q->paginate((int) $perPage)->appends($request->only('status','search','sort','per_page'));

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

        // 🔐 Optional: create/link user
        if ($provision['create_login']) {
            $plainPassword = $provision['login_password'] ?: $this->generatePassword();
            $role = $provision['role'] ?? 'staff'; // safe default if you kept staff/manager

            // Create or update the user by email
            $user = User::updateOrCreate(
                ['email' => $employee->email],
                [
                    'name'     => trim($employee->first_name.' '.$employee->last_name),
                    'password' => $plainPassword, // auto-hashed by cast
                    'role'     => $role,
                ]
            );

            // link back to employee
            $employee->update(['user_id' => $user->id]);

            // Show the one-time password to the admin
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

        $employee->update($data);

        // 🔐 Optional: provision/link on update (if not already linked or forced)
        if ($provision['create_login']) {
            $plainPassword = $provision['login_password']; // may be null; if null we keep current user password
            $role = $provision['role'] ?? null;

            // if employee already linked, update user; else create
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
                $user->password = $plainPassword; // hashed by cast
                session()->flash('login_password_plain', $plainPassword);
            }
            if ($role) {
                $user->role = $role;
            }

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

        $employeeData = $request->validate([
            'first_name'    => ['required','string','max:120'],
            'last_name'     => ['required','string','max:120'],
            'position'      => ['nullable','string','max:160'],
            'email'         => ['required','email','max:190', $emailUnique],
            'username'      => ['required','string','max:120', $userUnique],
            'status'        => ['required', Rule::in(['active','inactive'])],
            'avatar'        => ['nullable','file','image','max:3072'],
            'remove_avatar' => ['sometimes','boolean'],

            // 👇 optional provisioning inputs live in the same request
            'create_login'  => ['sometimes','boolean'],
            'role'          => ['sometimes', Rule::in(['admin','sales','inventory','staff','manager'])],
            'login_password'=> ['nullable','string','min:6'],
        ]);

        $provision = [
            'create_login'   => (bool)($request->boolean('create_login')),
            'role'           => $request->input('role'),
            'login_password' => $request->input('login_password'),
        ];

        return [$employeeData, $provision];
    }

    /** Generate a strong friendly password when not supplied. */
    private function generatePassword(int $length = 12): string
    {
        // 12-char mix, readable
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
        return collect(range(1, $length))
            ->map(fn() => $chars[random_int(0, strlen($chars)-1)])
            ->implode('');
    }
}
