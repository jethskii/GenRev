@extends('layout.mainlayout')

@section('content')
<div class="glass border border-dark-line shadow-md p-6 rounded-lg text-[#1F4B2C]">
    <h2 class="text-lg font-bold mb-4">Employees</h2>

    <div class="overflow-x-auto rounded-lg">
        <table class="min-w-full text-sm text-left bg-white rounded-lg overflow-hidden">
            <thead class="bg-sidebar text-[#1F4B2C] uppercase text-xs">
                <tr>
                    <th scope="col" class="py-3 px-4 border-b border-dark-line">Employee ID</th>
                    <th scope="col" class="py-3 px-4 border-b border-dark-line">Full Name</th>
                    <th scope="col" class="py-3 px-4 border-b border-dark-line">Position</th>
                    <th scope="col" class="py-3 px-4 border-b border-dark-line">Email / Username</th>
                    <th scope="col" class="py-3 px-4 border-b border-dark-line">Password</th>
                    <th scope="col" class="py-3 px-4 border-b border-dark-line">Status</th>
                </tr>
            </thead>
            <tbody class="text-[#1F4B2C] divide-y divide-dark-line">
                @forelse ($employees as $emp)
                <tr class="hover:bg-sidebar-hover transition">
                    <td class="py-3 px-4">EMP{{ str_pad($emp->id, 4, '0', STR_PAD_LEFT) }}</td>
                    <td class="py-3 px-4">{{ $emp->first_name }} {{ $emp->last_name }}</td>
                    <td class="py-3 px-4">{{ $emp->position }}</td>
                    <td class="py-3 px-4">{{ $emp->username }}</td>
                    <td class="py-3 px-4">********</td>
                    <td class="py-3 px-4 capitalize">{{ $emp->status }}</td>
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
