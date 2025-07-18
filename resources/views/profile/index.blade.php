@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="container mt-4">
    <div class="row">
        {{-- Info User --}}
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <strong>Informasi Akun</strong>
                </div>
                <div class="card-body">
                    <p><strong>Nama:</strong> {{ auth()->user()->name }}</p>
                    <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                    <p><strong>Role:</strong> {{ auth()->user()->role }}</p>
                    <p><strong>Jabatan:</strong> {{ auth()->user()->jabatan->nama ?? '-' }}</p>
                    <p><strong>Cabang:</strong> {{ auth()->user()->cabang->nama ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Ubah Password --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-warning">
                    <strong>Ubah Password</strong>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @elseif(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('profile.updatePassword') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Lama</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" name="new_password_confirmation" required>
                        </div>
                        <button type="submit" class="btn btn-success">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
