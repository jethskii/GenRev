@extends('layout.mainlayout')

@section('content')
<div class="bg-dark-bg text-white border border-dark-line p-6 rounded shadow-md">

    {{-- Title + Search + Add --}}
    <div class="flex flex-col lg:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-bold">Material Management</h2>

        <form action="{{ route('materials.index') }}" method="GET" class="flex gap-2">
            <input
                type="text"
                name="search"
                placeholder="Search material..."
                value="{{ request('search') }}"
                class="input-dark w-64"
            >
            <button type="submit" class="btn-armygreen">Search</button>
        </form>

        <button onclick="openAddModal()" class="btn-armygreen">+ Add Material</button>
    </div>

    {{-- Success Flash --}}
    @if(session('success'))
        <div class="mb-4 text-green-400 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto rounded-lg">
        <table class="w-full text-sm text-left bg-dark-bg rounded-lg overflow-hidden border-collapse">
            <thead class="bg-sidebar text-white text-xs uppercase">
                <tr>
                    <th class="py-3 px-4 border-b border-dark-line">Material</th>
                    <th class="py-3 px-4 border-b border-dark-line">Quantity (KG)</th>
                    <th class="py-3 px-4 border-b border-dark-line">Added</th>
                    <th class="py-3 px-4 border-b border-dark-line text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-100 divide-y divide-dark-line">
                @forelse($materials as $material)
                    <tr class="hover:bg-sidebar-hover transition">
                        <td class="py-3 px-4">{{ $material->material_name }}</td>
                        <td class="py-3 px-4">{{ number_format($material->quantity_kg, 2) }}</td>
                        <td class="py-3 px-4">{{ \Carbon\Carbon::parse($material->created_at)->format('M d, Y') }}</td>
                        <td class="py-3 px-4 text-center space-x-2">
                            <button onclick="openEditModal({{ $material->id }})" class="text-yellow-400 hover:underline">Edit</button>
                            <form action="{{ route('materials.destroy', $material->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete this material?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-4 text-center text-gray-400">No materials found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Modal --}}
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 justify-center items-center">
    <div class="bg-dark-bg p-6 rounded-lg w-full max-w-md shadow-lg border border-dark-line">
        <h3 class="text-lg font-bold mb-4">Add Material</h3>
        <form action="{{ route('materials.store') }}" method="POST">
            @csrf
            <input name="material_name" required placeholder="Material name" class="input-dark mb-4 w-full">
            <input type="number" step="0.01" name="quantity_kg" required placeholder="Quantity in KG" class="input-dark mb-4 w-full">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 rounded text-sm bg-gray-600 hover:bg-gray-700 transition">Cancel</button>
                <button type="submit" class="btn-armygreen">Add</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 justify-center items-center">
    <div class="bg-dark-bg p-6 rounded-lg w-full max-w-md shadow-lg border border-dark-line">
        <h3 class="text-lg font-bold mb-4">Edit Material</h3>
        <form id="editForm" method="POST">
            @csrf @method('PUT')
            <input id="edit_material_name" name="material_name" required class="input-dark mb-4 w-full">
            <input type="number" step="0.01" id="edit_quantity" name="quantity_kg" required class="input-dark mb-4 w-full">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded text-sm bg-gray-600 hover:bg-gray-700 transition">Cancel</button>
                <button type="submit" class="btn-armygreen">Update</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openAddModal() {
        const m = document.getElementById('addModal');
        m.classList.remove('hidden'); m.classList.add('flex');
    }
    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }
    function openEditModal(id) {
        fetch(`/materials/${id}/edit`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('edit_material_name').value = data.material_name;
                document.getElementById('edit_quantity').value = data.quantity_kg;
                document.getElementById('editForm').action = `/materials/${id}`;
                const modal = document.getElementById('editModal');
                modal.classList.remove('hidden'); modal.classList.add('flex');
            })
            .catch(() => alert('Failed to load material details.'));
    }
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
</script>
@endsection
