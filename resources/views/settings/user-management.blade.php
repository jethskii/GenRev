@extends('layout.mainlayout')

@section('title', 'User Management • GenRev')
@section('page_title', 'User Management')

@section('content')
<div class="p-6 text-gray-100">

  {{-- Header --}}
  <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <h1 class="text-3xl font-bold text-white">User Management</h1>

    <div class="flex items-center gap-3">
      <a href="{{ route('users.export.csv') }}"
         class="rounded-xl bg-green-600 px-4 py-2 font-semibold text-white hover:bg-green-700 shadow">
        Export CSV
      </a>
      <button id="addUserBtn"
        class="rounded-xl bg-gradient-to-r from-red-500 to-red-700 px-5 py-2 font-semibold text-white hover:opacity-90 shadow-lg">
        + Add User
      </button>
    </div>
  </div>

  {{-- Alerts --}}
  @if(session('success'))
    <div class="mb-4 rounded-lg border border-green-400 bg-green-800/30 px-4 py-3 text-green-200">
      {{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div class="mb-4 rounded-lg border border-red-400 bg-red-800/30 px-4 py-3 text-red-200">
      {{ session('error') }}
    </div>
  @endif
  @if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-400 bg-red-800/30 px-4 py-3 text-red-100">
      <ul class="list-disc list-inside space-y-1">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Search / Filters --}}
  <div class="flex flex-wrap gap-3 items-center mb-4">
    <input id="searchBox" type="text" placeholder="Search name or email"
           class="w-full sm:w-80 rounded-xl border border-gray-600 bg-gray-800 px-4 py-2 text-gray-200 focus:ring-2 focus:ring-red-400 outline-none">
    <select id="filterRole"
            class="rounded-xl bg-gray-800 border border-gray-600 text-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-400">
      <option value="">All Roles</option>
      <option value="admin">Admin</option>
      <option value="sales">Sales</option>
      <option value="inventory">Inventory</option>
      <option value="staff">Employee</option>
    </select>
    <select id="filterStatus"
            class="rounded-xl bg-gray-800 border border-gray-600 text-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-400">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
      <option value="deleted">Deleted</option>
    </select>
  </div>

  {{-- Table --}}
  <div class="overflow-x-auto rounded-2xl shadow-xl bg-gray-900/60 backdrop-blur-lg border border-gray-700">
    <table class="w-full text-left">
      <thead>
        <tr class="text-gray-300 border-b border-gray-700">
          <th class="py-3 px-6">Name</th>
          <th class="py-3 px-6">Email</th>
          <th class="py-3 px-6">Role</th>
          <th class="py-3 px-6">Status</th>
          <th class="py-3 px-6 text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="userTableBody">
        @forelse($users ?? [] as $user)
          @php
            $role = strtolower(optional($user)->role ?? '');
            $roleClass = match ($role) {
              'admin'     => 'bg-red-600/30 text-red-300',
              'sales'     => 'bg-blue-600/30 text-blue-300',
              'inventory' => 'bg-indigo-600/30 text-indigo-300',
              'manager'   => 'bg-yellow-600/30 text-yellow-300',
              default     => 'bg-green-600/30 text-green-300',
            };
            $statusAttr = optional($user)->deleted_at ? 'deleted' : ((optional($user)->is_active ?? false) ? 'active' : 'inactive');
          @endphp
          <tr class="border-b border-gray-700 hover:bg-gray-800/50 transition-all"
              data-role="{{ $role }}"
              data-status="{{ $statusAttr }}">
            <td class="py-3 px-6">
              <div class="flex items-center gap-2">
                <span class="font-medium">{{ optional($user)->display_name ?? '—' }}</span>
                @if(optional($user)->deleted_at)
                  <span class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-300">Trashed</span>
                @endif
              </div>
            </td>
            <td class="py-3 px-6">{{ optional($user)->email ?? '—' }}</td>
            <td class="py-3 px-6">
              <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $roleClass }}">
                {{ optional($user)->role_label ?? ucfirst($role ?: 'N/A') }}
              </span>
            </td>
            <td class="py-3 px-6">
              @if(optional($user)->deleted_at)
                <span class="px-2 py-1 rounded-md text-xs font-semibold bg-gray-700/70 text-gray-300">Deleted</span>
              @else
                <span class="px-2 py-1 rounded-md text-xs font-semibold {{ (optional($user)->is_active ?? false) ? 'bg-green-700/40 text-green-300' : 'bg-gray-700/60 text-gray-300' }}">
                  {{ (optional($user)->is_active ?? false) ? 'Active' : 'Inactive' }}
                </span>
              @endif
            </td>
            <td class="py-3 px-6 text-center">
              @if(optional($user)->deleted_at)
                <form action="{{ route('users.restore', optional($user)->id) }}" method="POST" class="inline">
                  @csrf @method('PATCH')
                  <button class="text-amber-300 hover:text-amber-200 mx-1">Restore</button>
                </form>
              @else
                <button class="editBtn text-blue-400 hover:text-blue-300 mx-1"
                        aria-label="Edit {{ optional($user)->display_name ?? 'User' }}"
                        data-json='@json(["id"=>optional($user)->id,"name"=>optional($user)->name,"email"=>optional($user)->email,"role"=>$role,"is_active"=>optional($user)->is_active])'>
                  Edit
                </button>
                <form action="{{ route('users.toggle-active', optional($user)->id) }}" method="POST" class="inline">
                  @csrf @method('PATCH')
                  <button class="text-purple-300 hover:text-purple-200 mx-1">
                    {{ (optional($user)->is_active ?? false) ? 'Deactivate' : 'Activate' }}
                  </button>
                </form>
                <form action="{{ route('users.destroy', optional($user)->id) }}" method="POST" class="inline"
                      onsubmit="return confirm('Move this user to trash?')">
                  @csrf @method('DELETE')
                  <button class="text-red-400 hover:text-red-300 mx-1">Delete</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="py-6 px-6 text-center text-gray-300">
              No users found. Click <button id="addUserBtnEmpty" class="underline text-red-300">Add User</button> to create one.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Include modals --}}
  @include('settings.partials.add-user')
  @include('settings.partials.edit-user')

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const $ = (s, r=document) => r.querySelector(s);
  const open  = el => { if (el) { el.classList.remove('hidden'); const f = el.querySelector('input,select'); if (f) setTimeout(()=>f.focus(), 50); }};
  const close = el => el && el.classList.add('hidden');

  const addBtn = $('#addUserBtn') || $('#addUserBtnEmpty');
  const addModal = $('#addUserModal');
  const closeAdd = $('#closeAddModal');
  const editModal = $('#editUserModal');
  const closeEdit = $('#closeEditModal');
  const editForm  = $('#editUserForm');

  addBtn?.addEventListener('click', () => open(addModal));
  closeAdd?.addEventListener('click', () => close(addModal));

  document.addEventListener('click', e => {
    const btn = e.target.closest('.editBtn');
    if (!btn) return;
    e.preventDefault();
    try {
      const data = JSON.parse(btn.getAttribute('data-json') || '{}');
      $('#edit_name').value  = data.name || '';
      $('#edit_email').value = data.email || '';
      $('#edit_role').value  = (data.role || '').toLowerCase();
      $('#edit_active').checked = !!data.is_active;
      if (editForm) editForm.action = `{{ url('users') }}/${data.id}`;
      open(editModal);
    } catch (err) { console.error('Edit payload error', err); }
  });

  closeEdit?.addEventListener('click', () => close(editModal));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') { close(addModal); close(editModal); } });

  const searchBox = $('#searchBox');
  const filterRole = $('#filterRole');
  const filterStatus = $('#filterStatus');

  function applyFilters() {
    const q = (searchBox?.value || '').toLowerCase();
    const role = (filterRole?.value || '').toLowerCase();
    const status = (filterStatus?.value || '').toLowerCase();
    document.querySelectorAll('#userTableBody tr').forEach(row => {
      const text = row.textContent.toLowerCase();
      const rowRole = (row.getAttribute('data-role') || '').toLowerCase();
      const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
      const ok = text.includes(q) && (!role || rowRole === role) && (!status || rowStatus === status);
      row.style.display = ok ? '' : 'none';
    });
  }
  [searchBox, filterRole, filterStatus].forEach(el => el?.addEventListener('input', applyFilters));
});
</script>
@endpush
