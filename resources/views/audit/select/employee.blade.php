@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Select Employee for Audit</h1>

    @if($employees->isEmpty())
        <p>No employees available for audit.</p>
    @else
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">Name</th>
                    <th class="py-2 px-4 border-b">Role</th>
                    <th class="py-2 px-4 border-b">Branch</th>
                    <th class="py-2 px-4 border-b">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                <tr>
                    <td class="py-2 px-4 border-b">{{ $employee->name }}</td>
                    <td class="py-2 px-4 border-b">{{ $employee->role }}</td>
                    <td class="py-2 px-4 border-b">{{ $employee->cabang->name ?? 'N/A' }}</td>
                    <td class="py-2 px-4 border-b">
                        <form action="{{ route('audit.start') }}" method="POST">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                Start Audit
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
