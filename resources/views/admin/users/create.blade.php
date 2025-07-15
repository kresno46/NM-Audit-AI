@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')
<div class="container py-4">
    <h3 class="mb-4">Tambah User Baru</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('users.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" required>
            @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required>
            @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                <option value="">-- Pilih Role --</option>
                <option value="CBO" {{ old('role') == 'CBO' ? 'selected' : '' }}>CBO</option>
                <option value="BC" {{ old('role') == 'BC' ? 'selected' : '' }}>BC</option>
                <option value="SBC" {{ old('role') == 'SBC' ? 'selected' : '' }}>SBC</option>
            </select>
            @error('role')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                   required>
            @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Konfirmasi Password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection
