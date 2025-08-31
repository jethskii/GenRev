{{-- resources/views/products/_form.blade.php --}}
@csrf
@php
  // If editing, $product will be defined; otherwise use null-safe fallback
  $p = $product ?? null;
  $unitOptions = $unitOptions ?? ['kg' => 'Kilograms', 'pcs' => 'Pieces', 'lt' => 'Liters'];
  $statusOptions = $statusOptions ?? ['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'on_sale' => 'On Sale'];
  $categoryOptions = $categories ?? []; // pass distinct categories from controller (optional)
@endphp

<div class="grid grid-cols-1 md:grid-cols-12 gap-3">
  {{-- Left column: identity --}}
  <div class="md:col-span-7 grid grid-cols-1 md:grid-cols-2 gap-3">
    <div>
      <label class="text-sm">Product Code <span class="text-red-400">*</span></label>
      <input name="product_code" value="{{ old('product_code', $p->product_code ?? '') }}"
             class="input-dark w-full" required>
      @error('product_code') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="text-sm">Product Name <span class="text-red-400">*</span></label>
      <input name="product_name" value="{{ old('product_name', $p->product_name ?? '') }}"
             class="input-dark w-full" required>
      @error('product_name') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="text-sm">Category</label>
      <input list="categories" name="category" value="{{ old('category', $p->category ?? '') }}"
             class="input-dark w-full">
      <datalist id="categories">
        @foreach($categoryOptions as $cat) <option value="{{ $cat }}"> @endforeach
      </datalist>
      @error('category') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="text-sm">Unit</label>
      <select name="unit" class="input-dark w-full">
        @foreach($unitOptions as $val => $label)
          <option value="{{ $val }}" @selected(old('unit', $p->unit ?? 'kg') === $val)>{{ $label }}</option>
        @endforeach
      </select>
      @error('unit') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="text-sm">Status</label>
      <select name="status" class="input-dark w-full">
        @foreach($statusOptions as $val => $label)
          <option value="{{ $val }}" @selected(old('status', $p->status ?? 'active') === $val)>{{ $label }}</option>
        @endforeach
      </select>
      @error('status') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="text-sm">Default Price (sell)</label>
      <input type="number" step="0.01" min="0" name="default_price"
             value="{{ old('default_price', $p->default_price ?? '') }}"
             class="input-dark w-full">
      @error('default_price') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Right column: image --}}
  <div class="md:col-span-5">
    <label class="text-sm">Image</label>
    <input type="file" name="image" accept="image/*" class="block w-full text-sm">
    @error('image') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    @if(!empty($p?->image_url))
      <img src="{{ $p->image_url }}" class="mt-2 w-36 h-36 object-cover rounded-xl border border-white/10">
    @endif
    <p class="text-xs opacity-70 mt-1">Recommended 512×512+, JPG or PNG.</p>
  </div>

  {{-- Divider --}}
  <div class="md:col-span-12 border-t border-white/10 my-2"></div>

  {{-- Ops fields --}}
  <div class="md:col-span-12 grid grid-cols-1 md:grid-cols-3 gap-3">
    <div>
      <label class="text-sm">Shelf Life (days)</label>
      <input type="number" min="0" name="shelf_life_days"
             value="{{ old('shelf_life_days', $p->shelf_life_days ?? 0) }}"
             class="input-dark w-full">
      @error('shelf_life_days') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="text-sm">Yield Rate (%)</label>
      <input type="number" step="0.01" min="0" max="100" name="yield_rate"
             value="{{ old('yield_rate', $p->yield_rate ?? 100) }}"
             class="input-dark w-full">
      @error('yield_rate') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="text-sm">Standard Batch Size</label>
      <input type="number" step="0.001" min="0" name="standard_batch_size"
             value="{{ old('standard_batch_size', $p->standard_batch_size ?? null) }}"
             class="input-dark w-full">
      @error('standard_batch_size') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="text-sm">Lead Time (days)</label>
      <input type="number" min="0" name="lead_time_days"
             value="{{ old('lead_time_days', $p->lead_time_days ?? 0) }}"
             class="input-dark w-full">
      @error('lead_time_days') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="text-sm">Min Run Qty</label>
      <input type="number" step="0.001" min="0" name="min_run_qty"
             value="{{ old('min_run_qty', $p->min_run_qty ?? null) }}"
             class="input-dark w-full">
      @error('min_run_qty') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="text-sm">Max Run Qty</label>
      <input type="number" step="0.001" min="0" name="max_run_qty"
             value="{{ old('max_run_qty', $p->max_run_qty ?? null) }}"
             class="input-dark w-full">
      @error('max_run_qty') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>
  </div>

  {{-- Divider --}}
  <div class="md:col-span-12 border-t border-white/10 my-2"></div>

  {{-- Storage / Constraints / Costs --}}
  <div class="md:col-span-12 grid grid-cols-1 md:grid-cols-3 gap-3">
    <div>
      <label class="text-sm">Storage Zone</label>
      <select name="storage_zone" class="input-dark w-full">
        @foreach(['chiller'=>'Chiller','freezer'=>'Freezer','ambient'=>'Ambient'] as $val=>$label)
          <option value="{{ $val }}" @selected(old('storage_zone', $p->storage_zone ?? 'chiller') === $val)>{{ $label }}</option>
        @endforeach
      </select>
      @error('storage_zone') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="text-sm">Unit Cost (std)</label>
      <input type="number" step="0.01" min="0" name="unit_cost"
             value="{{ old('unit_cost', $p->unit_cost ?? null) }}"
             class="input-dark w-full">
      @error('unit_cost') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label class="text-sm">Last Cost Date</label>
      <input type="date" name="last_cost_date"
             value="{{ old('last_cost_date', optional($p->last_cost_date ?? null)->toDateString()) }}"
             class="input-dark w-full">
      @error('last_cost_date') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-3">
      <label class="text-sm">Temperature / Notes</label>
      <textarea name="temp_requirements" rows="2" class="input-dark w-full"
        placeholder="e.g., Store at 0–4°C, avoid refreeze">{{ old('temp_requirements', $p->temp_requirements ?? '') }}</textarea>
      @error('temp_requirements') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-3">
      <label class="text-sm">Line Constraints (JSON)</label>
      <textarea name="line_constraints" rows="2" class="input-dark w-full"
        placeholder='e.g., {"allowed_lines":["A","B"],"must_run_in_multiples_of":50}'>{{ old('line_constraints', is_array($p->line_constraints ?? null) ? json_encode($p->line_constraints) : ($p->line_constraints ?? '')) }}</textarea>
      @error('line_constraints') <div class="text-xs text-red-300 mt-1">{{ $message }}</div> @enderror
    </div>
  </div>
</div>

<div class="mt-5 flex justify-end gap-2">
  <a href="{{ route('products.index') }}" class="px-3 py-2 rounded-lg border border-dark-line hover:bg-sidebar-hover">Cancel</a>
  <button class="btn-armygreen">{{ $submitLabel ?? 'Save' }}</button>
</div>
