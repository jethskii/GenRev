{{-- resources/views/products/create.blade.php --}}
@extends('layout.mainlayout')

@section('title', 'Add Product | GenRev Admin')
@section('page_title', 'Add Product')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
      Add Product
    </h2>

    <a
      href="{{ route('products.index') }}"
      class="text-sm font-medium text-blue-600 hover:text-blue-800 underline transition-colors"
    >
      Back to list
    </a>
  </div>

  {{-- Validation / flash --}}
  @if($errors->any())
    <div class="mb-2 rounded-xl border border-red-400 bg-red-100 p-4 text-sm text-red-700 dark:bg-red-900/40 dark:border-red-500/60 dark:text-red-100">
      <strong class="block mb-1">⚠️ Please fix the errors below:</strong>
      <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if(session('success'))
    <div class="mb-2 rounded-xl border border-emerald-400 bg-emerald-100 p-4 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:border-emerald-500/60 dark:text-emerald-100">
      ✅ {{ session('success') }}
    </div>
  @endif

  {{-- Create Form --}}
  <form
    action="{{ route('products.store') }}"
    method="POST"
    enctype="multipart/form-data"
    class="space-y-6"
  >
    @csrf

    @include('products._form', ['submitLabel' => 'Create'])
  </form>
</div>
@endsection
