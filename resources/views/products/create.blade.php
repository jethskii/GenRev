{{-- resources/views/products/create.blade.php --}}
@extends('layout.mainlayout')

@section('content')
<div class="bg-dark-bg text-white border border-dark-line p-6 rounded-2xl shadow-md max-w-5xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-2xl font-bold">Add Product</h2>
    <a href="{{ route('products.index') }}" class="text-armygreen underline">Back to list</a>
  </div>

  {{-- Validation / flash --}}
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

  <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
    @include('products._form', ['submitLabel' => 'Create'])
  </form>
</div>
@endsection
