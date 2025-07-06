@extends('layout.mainlayout')

@section('content')
<div class="bg-dark-bg text-white border border-dark-line shadow-md p-6 rounded-lg">
    <h2 class="text-lg font-semibold mb-4">Employees</h2>

    <div class="overflow-x-auto rounded-lg">
        <table class="min-w-full text-sm text-left bg-dark-bg rounded-lg overflow-hidden">
            <thead class="bg-sidebar text-white uppercase text-xs">
                <tr>
                    <th class="py-3 px-4 border-b border-dark-line">Employee ID</th>
                    <th class="py-3 px-4 border-b border-dark-line">Name</th>
                    <th class="py-3 px-4 border-b border-dark-line">Position</th>
                    <th class="py-3 px-4 border-b border-dark-line">Username</th>
                    <th class="py-3 px-4 border-b border-dark-line">Password</th>
                    <th class="py-3 px-4 border-b border-dark-line">Status</th>
                </tr>
            </thead>
            <tbody class="text-gray-100 divide-y divide-dark-line">
                @forelse ($employees as $emp)
                <tr class="hover:bg-sidebar-hover transition">
                    <td class="py-3 px-4">{{ $emp->employee_id }}</td>
                    <td class="py-3 px-4">{{ $emp->name }}</td>
                    <td class="py-3 px-4">{{ $emp->position }}</td>
                    <td class="py-3 px-4">{{ $emp->username }}</td>
                    <td class="py-3 px-4">{{ $emp->password }}</td>
                    <td class="py-3 px-4">{{ $emp->status }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-4 text-center text-gray-400">No employees found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
