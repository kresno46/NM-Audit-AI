@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container">
    <h1 class="h3 mb-4">👤 User Management</h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary mb-3">+ Add User</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th><th>Email</th><th>Role</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->role }}</td>
                <td>
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this user?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $users->links() }}
</div>
@endsection
