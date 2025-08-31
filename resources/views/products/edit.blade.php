{{-- resources/views/products/edit.blade.php --}}
@extends('layout.mainlayout')

@section('content')
<div class="bg-dark-bg text-white border border-dark-line p-6 rounded-2xl shadow-md max-w-5xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-2xl font-bold">Edit Product</h2>
    <a href="{{ route('products.show', $product) }}" class="text-armygreen underline">View product</a>
  </div>

  @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-200">
      Please fix the errors below.
    </div>
  @endif
  @if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-sm text-emerald-200">
      {{ session('success') }}
    </div>
  @endif

  <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
    @method('PUT')
    @include('products._form', ['submitLabel' => 'Update', 'product' => $product])
  </form>
</div>
@endsection
