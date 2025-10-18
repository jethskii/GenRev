{{-- Add User Modal --}}
<div id="addUserModal" class="hidden fixed inset-0 z-50">
  {{-- Backdrop --}}
  <div class="absolute inset-0 bg-black/70"></div>

  <div class="relative mx-auto my-10 w-[95%] max-w-xl rounded-2xl border border-gray-700 bg-gray-900 shadow-2xl">
    <div class="flex items-center justify-between px-6 pt-5">
      <h2 class="text-2xl font-bold text-white">Add New User</h2>
      <button id="closeAddModal" class="rounded-lg px-3 py-1 text-gray-300 hover:bg-gray-700">✕</button>
    </div>

    <form action="{{ route('users.store') }}" method="POST" class="px-6 pb-6 pt-4 space-y-4">
      @csrf

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- Name --}}
        <div class="sm:col-span-2">
          <label class="mb-1 block text-sm text-gray-300" for="name">Full Name</label>
          <input id="name" name="name" type="text" required
            class="w-full rounded-xl border border-gray-600 bg-gray-800 px-4 py-2 text-gray-200 focus:ring-2 focus:ring-red-400 outline-none" />
        </div>

        {{-- Email --}}
        <div class="sm:col-span-2">
          <label class="mb-1 block text-sm text-gray-300" for="email">Email</label>
          <input id="email" name="email" type="email" required
            class="w-full rounded-xl border border-gray-600 bg-gray-800 px-4 py-2 text-gray-200 focus:ring-2 focus:ring-red-400 outline-none" />
        </div>

        {{-- Password --}}
        <div>
          <label class="mb-1 block text-sm text-gray-300" for="password">Password</label>
          <input id="password" name="password" type="password" required
            class="w-full rounded-xl border border-gray-600 bg-gray-800 px-4 py-2 text-gray-200 focus:ring-2 focus:ring-red-400 outline-none" />
        </div>

        {{-- Role --}}
        <div>
          <label class="mb-1 block text-sm text-gray-300" for="role">Role</label>
          {{-- Stored as lowercase in DB --}}
          <select id="role" name="role" required
            class="w-full rounded-xl border border-gray-600 bg-gray-800 px-4 py-2 text-gray-200 focus:ring-2 focus:ring-blue-400 outline-none">
            <option value="admin">Admin</option>
            <option value="sales">Sales</option>
            <option value="inventory">Inventory</option>
            <option value="staff">Employee</option>
          </select>
        </div>
      </div>

      {{-- Active toggle --}}
      <label class="inline-flex items-center gap-2 text-gray-200">
        <input type="checkbox" name="is_active" value="1" checked
          class="h-4 w-4 rounded border-gray-600 bg-gray-800" />
        Active
      </label>

      <div class="mt-4 flex justify-end gap-3">
        <button type="button" id="closeAddModalBottom"
          class="rounded-lg bg-gray-700 px-4 py-2 text-gray-200 hover:bg-gray-600">Cancel</button>
        <button type="submit"
          class="rounded-lg bg-gradient-to-r from-red-500 to-red-700 px-6 py-2 font-semibold text-white shadow-lg hover:opacity-90">
          Save
        </button>
      </div>
    </form>
  </div>
</div>
