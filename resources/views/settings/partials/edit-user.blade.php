{{-- Edit User Modal --}}
<div id="editUserModal" class="hidden fixed inset-0 z-50">
  {{-- Backdrop --}}
  <div class="absolute inset-0 bg-black/70"></div>

  <div class="relative mx-auto my-10 w-[95%] max-w-xl rounded-2xl border border-gray-700 bg-gray-900 shadow-2xl">
    <div class="flex items-center justify-between px-6 pt-5">
      <h2 class="text-2xl font-bold text-white">Edit User</h2>
      <button id="closeEditModal" class="rounded-lg px-3 py-1 text-gray-300 hover:bg-gray-700">✕</button>
    </div>

    <form id="editUserForm" method="POST" class="px-6 pb-6 pt-4 space-y-4">
      @csrf @method('PUT')

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- Name --}}
        <div class="sm:col-span-2">
          <label class="mb-1 block text-sm text-gray-300" for="edit_name">Full Name</label>
          <input id="edit_name" name="name" type="text" required
            class="w-full rounded-xl border border-gray-600 bg-gray-800 px-4 py-2 text-gray-200 focus:ring-2 focus:ring-blue-400 outline-none" />
        </div>

        {{-- Email --}}
        <div class="sm:col-span-2">
          <label class="mb-1 block text-sm text-gray-300" for="edit_email">Email</label>
          <input id="edit_email" name="email" type="email" required
            class="w-full rounded-xl border border-gray-600 bg-gray-800 px-4 py-2 text-gray-200 focus:ring-2 focus:ring-blue-400 outline-none" />
        </div>

        {{-- New Password (optional) --}}
        <div>
          <label class="mb-1 block text-sm text-gray-300" for="edit_password">New Password <span class="text-xs text-gray-400">(optional)</span></label>
          <input id="edit_password" name="password" type="password"
            class="w-full rounded-xl border border-gray-600 bg-gray-800 px-4 py-2 text-gray-200 focus:ring-2 focus:ring-blue-400 outline-none"
            placeholder="leave blank" />
        </div>

        {{-- Role --}}
        <div>
          <label class="mb-1 block text-sm text-gray-300" for="edit_role">Role</label>
          <select id="edit_role" name="role" required
            class="w-full rounded-xl border border-gray-600 bg-gray-800 px-4 py-2 text-gray-200 focus:ring-2 focus:ring-blue-400 outline-none">
            <option value="admin">Admin</option>
            <option value="sales">Sales</option>
            <option value="inventory">Inventory</option>
            <option value="staff">Employee</option>
          </select>
        </div>
      </div>

      {{-- Active --}}
      <label class="inline-flex items-center gap-2 text-gray-200">
        <input id="edit_active" type="checkbox" name="is_active" value="1"
          class="h-4 w-4 rounded border-gray-600 bg-gray-800" />
        Active
      </label>

      <div class="mt-4 flex justify-end gap-3">
        <button type="button" id="closeEditModalBottom"
          class="rounded-lg bg-gray-700 px-4 py-2 text-gray-200 hover:bg-gray-600">Cancel</button>
        <button type="submit"
          class="rounded-lg bg-gradient-to-r from-blue-500 to-blue-700 px-6 py-2 font-semibold text-white shadow-lg hover:opacity-90">
          Update
        </button>
      </div>
    </form>
  </div>
</div>
