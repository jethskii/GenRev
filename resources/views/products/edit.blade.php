@extends('layout.mainlayout')

@section('content')
<div class="bg-white text-black border border-gray-200 p-8 rounded-2xl shadow-lg max-w-5xl mx-auto mt-10">
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-3xl font-semibold text-gray-900">Edit Product</h2>
    <a href="{{ route('products.show', $product) }}" class="text-blue-600 hover:text-blue-800 underline font-medium transition-colors duration-200">
      View product
    </a>
  </div>

  {{-- Validation / Session Alerts --}}
  @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-400 bg-red-100 p-4 text-sm text-red-700">
      <strong class="block mb-1">⚠️ Please fix the errors below:</strong>
      <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if(session('success'))
    <div class="mb-4 rounded-xl border border-green-400 bg-green-100 p-4 text-sm text-green-800">
      ✅ {{ session('success') }}
    </div>
  @endif

  {{-- Edit Form --}}
  <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @method('PUT')
    @csrf

    @include('products._form', ['submitLabel' => 'Update', 'product' => $product])
  </form>
</div>
@endsection
