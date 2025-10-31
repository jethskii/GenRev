{{-- resources/views/products/_form.blade.php --}}
@csrf
@php
  $p = $product ?? null;
@endphp

<div class="bg-white shadow-xl rounded-2xl p-8 text-black">
  <h2 class="text-2xl font-semibold mb-6 border-b border-gray-200 pb-3">Add Product</h2>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Batch Number --}}
    <div>
      <label class="block text-sm font-medium mb-1">Batch Number <span class="text-red-500">*</span></label>
      <input name="batch_number" value="{{ old('batch_number', $p->batch_number ?? '') }}"
             class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-4 focus:ring-blue-200 focus:border-blue-400 transition" required>
      @error('batch_number') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Quantity --}}
    <div>
      <label class="block text-sm font-medium mb-1">Quantity</label>
      <input type="number" name="quantity" min="0"
             value="{{ old('quantity', $p->quantity ?? '') }}"
             class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-4 focus:ring-blue-200 focus:border-blue-400 transition">
      @error('quantity') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Forecasted Demand --}}
    <div>
      <label class="block text-sm font-medium mb-1">Forecasted Demand</label>
      <input type="number" step="0.01" name="forecasted_demand"
             value="{{ old('forecasted_demand', $p->forecasted_demand ?? '') }}"
             class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-4 focus:ring-blue-200 focus:border-blue-400 transition">
      @error('forecasted_demand') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Current Inventory --}}
    <div>
      <label class="block text-sm font-medium mb-1">Current Inventory</label>
      <input type="number" name="current_inventory" min="0"
             value="{{ old('current_inventory', $p->current_inventory ?? '') }}"
             class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-4 focus:ring-blue-200 focus:border-blue-400 transition">
      @error('current_inventory') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Unit Cost --}}
    <div>
      <label class="block text-sm font-medium mb-1">Unit Cost</label>
      <input type="number" step="0.01" name="unit_cost"
             value="{{ old('unit_cost', $p->unit_cost ?? '') }}"
             class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-4 focus:ring-blue-200 focus:border-blue-400 transition">
      @error('unit_cost') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Unit Price Pack --}}
    <div>
      <label class="block text-sm font-medium mb-1">Unit Price (Pack)</label>
      <input type="number" step="0.01" name="unit_price_pack"
             value="{{ old('unit_price_pack', $p->unit_price_pack ?? '') }}"
             class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-4 focus:ring-blue-200 focus:border-blue-400 transition">
      @error('unit_price_pack') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Unit Price Bag --}}
    <div>
      <label class="block text-sm font-medium mb-1">Unit Price (Bag)</label>
      <input type="number" step="0.01" name="unit_price_bag"
             value="{{ old('unit_price_bag', $p->unit_price_bag ?? '') }}"
             class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-4 focus:ring-blue-200 focus:border-blue-400 transition">
      @error('unit_price_bag') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Production Date --}}
    <div>
      <label class="block text-sm font-medium mb-1">Production Date</label>
      <input type="date" name="production_date"
             value="{{ old('production_date', $p->production_date ?? '') }}"
             class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-4 focus:ring-blue-200 focus:border-blue-400 transition">
      @error('production_date') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Expiration Date --}}
    <div>
      <label class="block text-sm font-medium mb-1">Expiration Date</label>
      <input type="date" name="expiration_date"
             value="{{ old('expiration_date', $p->expiration_date ?? '') }}"
             class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-4 focus:ring-blue-200 focus:border-blue-400 transition">
      @error('expiration_date') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Image Upload --}}
    <div class="md:col-span-2">
      <label class="block text-sm font-medium mb-1">Upload Image</label>
      <input type="file" name="image" accept="image/*"
             class="block w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition">
      @error('image') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
      @if(!empty($p?->image_path))
        <img src="{{ asset($p->image_path) }}" alt="Product image" class="mt-3 w-40 h-40 object-cover rounded-lg shadow border">
      @endif
      <p class="text-xs mt-1 text-gray-500">Recommended size: 512x512px (JPG or PNG)</p>
    </div>
  </div>

  {{-- Buttons --}}
  <div class="mt-8 flex justify-end space-x-3">
    <a href="{{ route('products.index') }}"
       class="px-5 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-100 transition font-medium">Cancel</a>
    <button type="submit"
            class="px-6 py-2.5 rounded-lg text-white font-semibold bg-gradient-to-r from-teal-500 via-blue-500 to-purple-500 hover:opacity-90 focus:ring-4 focus:ring-purple-200 transition">
      Save Product
    </button>
  </div>
</div>
