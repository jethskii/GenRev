{{-- resources/views/products/_form.blade.php --}}

@once
  @push('styles')
    <style>
      .product-section {
        border-radius: 1rem;
        border: 1px solid var(--line);
        background: var(--bg-card);
        box-shadow: 0 10px 25px rgba(15,23,42,0.06);
      }
      .product-section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text);
      }
      .product-step-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 1.75rem;
        width: 1.75rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        color: #fff;
      }
      .product-step-1 { background: #2563eb; }
      .product-step-2 { background: #059669; }
      .product-step-3 { background: #f59e0b; }
      .product-step-4 { background: #7c3aed; }

      .product-label {
        display: block;
        margin-bottom: 0.25rem;
        font-size: 0.8rem;
        font-weight: 500;
        color: #374151;
      }
      .dark-mode .product-label { color: #e5e7eb; }

      .product-error-text {
        margin-top: 0.25rem;
        font-size: 0.7rem;
      }
    </style>
  @endpush
@endonce

@php
    /** @var \App\Models\Product|null $product */
    $isEdit = isset($product) && $product->exists;

    // Safely compute last cost date for the date input (Y-m-d)
    $lastCostDateValue = old('last_cost_date');
    if (!$lastCostDateValue && $isEdit && !empty($product->last_cost_date)) {
        $lastCostDateValue = optional($product->last_cost_date)->format('Y-m-d');
    }
@endphp

<div class="space-y-8">

  {{-- ========== BASIC INFO ========== --}}
  <div class="product-section p-6">
    <h3 class="product-section-title">
      <span class="product-step-badge product-step-1">
        1
      </span>
      Basic Information
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

      {{-- Product Name (required) --}}
      <div class="md:col-span-2">
        <label for="product_name" class="product-label">
          Product Name <span class="text-red-500">*</span>
        </label>
        <input
          type="text"
          id="product_name"
          name="product_name"
          value="{{ old('product_name', $isEdit ? $product->product_name : '') }}"
          class="block w-full rounded-xl border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2
            @error('product_name')
              border-red-500 bg-red-50 focus:ring-red-400
            @else
              border-gray-300 bg-white focus:ring-blue-500
            @enderror"
          placeholder="Enter product name"
          required
        >
        @error('product_name')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Parent product (for variants) --}}
      @isset($parents)
        <div>
          <label for="parent_id" class="product-label">
            Parent Product
          </label>
          <select
            id="parent_id"
            name="parent_id"
            class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">No parent (base product)</option>
            @foreach($parents as $parent)
              <option
                value="{{ $parent->id }}"
                @selected(old('parent_id', $isEdit ? $product->parent_id : null) == $parent->id)
              >
                {{ $parent->product_name }}
              </option>
            @endforeach
          </select>
          @error('parent_id')
            <p class="product-error-text text-red-600">{{ $message }}</p>
          @enderror
        </div>
      @endisset

      {{-- Category (with suggestions) --}}
      <div>
        <label for="category" class="product-label">
          Category
        </label>
        <input
          list="category-list"
          id="category"
          name="category"
          value="{{ old('category', $isEdit ? $product->category : '') }}"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="e.g. Sausages, Frozen, Ready-to-cook"
        >
        @isset($categories)
          <datalist id="category-list">
            @foreach($categories as $cat)
              <option value="{{ $cat }}"></option>
            @endforeach
          </datalist>
        @endisset
        @error('category')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Unit --}}
      <div>
        <label for="unit" class="product-label">
          Unit
        </label>
        <select
          id="unit"
          name="unit"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Select unit</option>
          @isset($unitOptions)
            @foreach($unitOptions as $key => $label)
              <option
                value="{{ $key }}"
                @selected(old('unit', $isEdit ? $product->unit : '') === $key)
              >
                {{ $label }}
              </option>
            @endforeach
          @endisset
        </select>
        @error('unit')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Status --}}
      <div>
        <label for="status" class="product-label">
          Status
        </label>
        <select
          id="status"
          name="status"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Select status</option>
          @isset($statusOptions)
            @foreach($statusOptions as $key => $label)
              <option
                value="{{ $key }}"
                @selected(old('status', $isEdit ? $product->status : '') === $key)
              >
                {{ $label }}
              </option>
            @endforeach
          @endisset
        </select>
        @error('status')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

    </div>
  </div>

  {{-- ========== PRICING & COST ========== --}}
  <div class="product-section p-6">
    <h3 class="product-section-title">
      <span class="product-step-badge product-step-2">
        2
      </span>
      Pricing &amp; Cost
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      {{-- Default price (selling price) --}}
      <div>
        <label for="default_price" class="product-label">
          Default Price (₱)
        </label>
        <input
          type="number"
          step="0.01"
          id="default_price"
          name="default_price"
          value="{{ old('default_price', $isEdit ? $product->default_price : '') }}"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="0.00"
        >
        @error('default_price')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Unit cost (computed or manual override) --}}
      <div>
        <label for="unit_cost" class="product-label">
          Unit Cost (₱)
        </label>
        <input
          type="number"
          step="0.01"
          id="unit_cost"
          name="unit_cost"
          value="{{ old('unit_cost', $isEdit ? $product->unit_cost : '') }}"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="0.00"
        >
        @error('unit_cost')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Last cost date --}}
      <div>
        <label for="last_cost_date" class="product-label">
          Last Cost Date
        </label>
        <input
          type="date"
          id="last_cost_date"
          name="last_cost_date"
          value="{{ $lastCostDateValue }}"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
        @error('last_cost_date')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>
    </div>
  </div>

  {{-- ========== PRODUCTION / PLANNING ========== --}}
  <div class="product-section p-6">
    <h3 class="product-section-title">
      <span class="product-step-badge product-step-3">
        3
      </span>
      Production &amp; Planning
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      {{-- Shelf life (days) --}}
      <div>
        <label for="shelf_life_days" class="product-label">
          Shelf Life (days)
        </label>
        <input
          type="number"
          min="0"
          id="shelf_life_days"
          name="shelf_life_days"
          value="{{ old('shelf_life_days', $isEdit ? $product->shelf_life_days : '') }}"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="0"
        >
        @error('shelf_life_days')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Yield rate (%) --}}
      <div>
        <label for="yield_rate" class="product-label">
          Yield Rate (%)
        </label>
        <input
          type="number"
          step="0.01"
          min="0"
          max="100"
          id="yield_rate"
          name="yield_rate"
          value="{{ old('yield_rate', $isEdit ? $product->yield_rate : '') }}"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="0.00"
        >
        @error('yield_rate')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Standard batch size --}}
      <div>
        <label for="standard_batch_size" class="product-label">
          Standard Batch Size
        </label>
        <input
          type="number"
          step="0.01"
          min="0"
          id="standard_batch_size"
          name="standard_batch_size"
          value="{{ old('standard_batch_size', $isEdit ? $product->standard_batch_size : '') }}"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="0.00"
        >
        @error('standard_batch_size')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Lead time (days) --}}
      <div>
        <label for="lead_time_days" class="product-label">
          Lead Time (days)
        </label>
        <input
          type="number"
          min="0"
          id="lead_time_days"
          name="lead_time_days"
          value="{{ old('lead_time_days', $isEdit ? $product->lead_time_days : '') }}"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="0"
        >
        @error('lead_time_days')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Min run qty --}}
      <div>
        <label for="min_run_qty" class="product-label">
          Min Run Qty
        </label>
        <input
          type="number"
          step="0.01"
          min="0"
          id="min_run_qty"
          name="min_run_qty"
          value="{{ old('min_run_qty', $isEdit ? $product->min_run_qty : '') }}"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="0.00"
        >
        @error('min_run_qty')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Max run qty --}}
      <div>
        <label for="max_run_qty" class="product-label">
          Max Run Qty
        </label>
        <input
          type="number"
          step="0.01"
          min="0"
          id="max_run_qty"
          name="max_run_qty"
          value="{{ old('max_run_qty', $isEdit ? $product->max_run_qty : '') }}"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="0.00"
        >
        @error('max_run_qty')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Storage zone --}}
      <div>
        <label for="storage_zone" class="product-label">
          Storage Zone
        </label>
        <select
          id="storage_zone"
          name="storage_zone"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Select storage</option>
          <option value="chiller" @selected(old('storage_zone', $isEdit ? $product->storage_zone : '') === 'chiller')>Chiller</option>
          <option value="freezer" @selected(old('storage_zone', $isEdit ? $product->storage_zone : '') === 'freezer')>Freezer</option>
          <option value="ambient" @selected(old('storage_zone', $isEdit ? $product->storage_zone : '') === 'ambient')>Ambient</option>
        </select>
        @error('storage_zone')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>
    </div>
  </div>

  {{-- ========== ADDITIONAL DETAILS ========== --}}
  <div class="product-section p-6">
    <h3 class="product-section-title">
      <span class="product-step-badge product-step-4">
        4
      </span>
      Additional Details
    </h3>

    <div class="grid grid-cols-1 gap-6">
      {{-- Temp requirements --}}
      <div>
        <label for="temp_requirements" class="product-label">
          Temperature Requirements
        </label>
        <textarea
          id="temp_requirements"
          name="temp_requirements"
          rows="3"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="e.g. Store at -18°C, avoid thaw–refreeze cycles"
        >{{ old('temp_requirements', $isEdit ? $product->temp_requirements : '') }}</textarea>
        @error('temp_requirements')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Line constraints --}}
      <div>
        <label for="line_constraints" class="product-label">
          Line Constraints / Notes
        </label>
        <textarea
          id="line_constraints"
          name="line_constraints"
          rows="3"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Any special line constraints, allergen notes, or scheduling rules"
        >{{ old('line_constraints', $isEdit ? $product->line_constraints : '') }}</textarea>
        @error('line_constraints')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Description --}}
      <div>
        <label for="description" class="product-label">
          Description
        </label>
        <textarea
          id="description"
          name="description"
          rows="3"
          class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Short description of the product"
        >{{ old('description', $isEdit ? $product->description : '') }}</textarea>
        @error('description')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- Product Image --}}
      <div>
        <label for="image" class="product-label">
          Product Image
        </label>
        <div class="flex flex-col gap-3 md:flex-row md:items-center">
          <input
            type="file"
            id="image"
            name="image"
            class="block w-full max-w-xs text-sm text-gray-700
              file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2.5
              file:text-sm file:font-semibold file:text-blue-700
              hover:file:bg-blue-100"
            accept="image/*"
          >
          @if($isEdit && !empty($product->image_path))
            <div class="flex items-center gap-3">
              <div class="text-xs text-gray-500">Current:</div>
              <img
                src="{{ asset('storage/' . $product->image_path) }}"
                alt="Current product image"
                class="h-16 w-16 rounded-xl object-cover border border-gray-200 shadow-sm"
              >
            </div>
          @endif
        </div>
        @error('image')
          <p class="product-error-text text-red-600">{{ $message }}</p>
        @enderror
      </div>
    </div>
  </div>
</div>

{{-- SUBMIT BAR --}}
<div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-4">
  <a
    href="{{ route('products.index') }}"
    class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
  >
    Cancel
  </a>

  <button
    type="submit"
    class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
  >
    {{ $submitLabel ?? 'Save' }}
  </button>
</div>
