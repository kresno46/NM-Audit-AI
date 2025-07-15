@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container">
    <h1 class="h3 mb-4">✏️ Edit User</h1>

    <form action="{{ route('admin.users.update', $user) }}" method="POST">
        @csrf @method('PUT')

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
        </div>

        <div class="mb-3">
            <label>Role</label>
            <select name="role" class="form-control" required>
                <option value="CBO" {{ $user->role == 'CBO' ? 'selected' : '' }}>CBO</option>
                <option value="BC" {{ $user->role == 'BC' ? 'selected' : '' }}>BC</option>
                <option value="SBC" {{ $user->role == 'SBC' ? 'selected' : '' }}>SBC</option>
            </select>
        </div>

        <div class="mb-3">
            <label>New Password <small>(leave blank to keep old)</small></label>
            <input type="password" name="password" class="form-control">
        </div>

        <div class="mb-3">
            <label>Confirm New Password</label>
            <input type="password" name="password_confirmation" class="form-control">
        </div>

        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
