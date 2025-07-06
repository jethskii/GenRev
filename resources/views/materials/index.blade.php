@extends('layout.mainlayout')

@section('content')
<div class="bg-dark-bg text-white p-6 rounded shadow-md">

    {{-- Page Title + Add Button --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Material Management</h2>
        <button onclick="openAddModal()" class="btn-armygreen">+ Add Material</button>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="mb-4 text-green-400">{{ session('success') }}</div>
    @endif

    {{-- Material Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse bg-dark-bg text-white">
            <thead class="bg-sidebar text-xs uppercase">
                <tr>
                    <th class="py-3 px-4">Material</th>
                    <th class="py-3 px-4">Quantity (KG)</th>
                    <th class="py-3 px-4">Added</th>
                    <th class="py-3 px-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-100 divide-y divide-dark-line">
                @forelse($materials as $material)
                    <tr class="hover:bg-sidebar-hover">
                        <td class="py-3 px-4">{{ $material->name }}</td>
                        <td class="py-3 px-4">{{ number_format($material->quantity_kg, 2) }}</td>
                        <td class="py-3 px-4">
                        {{ \Carbon\Carbon::parse($material->created_at)->format('M d, Y') }}
                        </td>
                        <td class="py-3 px-4 text-center space-x-2">
                            <button onclick="openEditModal({{ $material->id }})" class="text-yellow-400 hover:underline">Edit</button>
                            <form action="{{ route('materials.destroy', $material->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this material?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-400">No materials found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ADD MODAL --}}
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 justify-center items-center">
    <div class="bg-dark-bg p-6 rounded-lg w-full max-w-md shadow-lg">
        <h3 class="text-lg font-bold mb-4">Add Material</h3>
        <button onclick="openAddModal()" class="btn-armygreen">+ Add Material</button>

        <form action="{{ route('materials.store') }}" method="POST">
            @csrf
            <input name="name" required placeholder="Material name" class="input-dark mb-4 w-full">
            <input type="number" step="0.01" name="quantity_kg" required placeholder="Quantity in KG" class="input-dark mb-4 w-full">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeAddModal()" class="btn-cancel">Cancel</button>
                <button type="submit" class="btn-armygreen">Add</button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT MODAL --}}
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 justify-center items-center">
    <div class="bg-dark-bg p-6 rounded-lg w-full max-w-md shadow-lg">
        <h3 class="text-lg font-bold mb-4">Edit Material</h3>
        <form id="editForm" method="POST">
            @csrf @method('PUT')
            <input id="edit_name" name="name" required class="input-dark mb-4 w-full">
            <input type="number" step="0.01" id="edit_quantity" name="quantity_kg" required class="input-dark mb-4 w-full">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="btn-cancel">Cancel</button>
                <button type="submit" class="btn-armygreen">Update</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden', 'opacity-0');
    document.getElementById('addModal').classList.add('flex');
    }
    function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
    }


    function openEditModal(id) {
        fetch(`/materials/${id}/edit`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('edit_name').value = data.name;
                document.getElementById('edit_quantity').value = data.quantity_kg;
                document.getElementById('editForm').action = `/materials/${id}`;
                document.getElementById('editModal').classList.remove('hidden', 'opacity-0');
                document.getElementById('editModal').classList.add('flex');
            });
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
</script>
@endsection
