@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')
<div class="container py-4">
    <h3 class="mb-4">Tambah User Baru</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

   <form action="{{ route('admin.users.store') }}" method="POST">
    @csrf

    {{-- Nama --}}
    <div class="mb-3">
        <label class="form-label">Nama</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Email --}}
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') }}" required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Role --}}
    <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" id="roleSelect" class="form-select @error('role') is-invalid @enderror" required>
            <option value="">-- Pilih Role --</option>
            @foreach($jabatan as $jbt)
                <option value="{{ $jbt->nama_jabatan }}" {{ old('role') == $jbt->nama_jabatan ? 'selected' : '' }}>
                    {{ $jbt->nama_jabatan }}
                </option>
            @endforeach
        </select>
        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Hidden jabatan_id --}}
    <input type="hidden" name="jabatan_id" id="jabatan_id">

    {{-- Cabang --}}
    <div class="mb-3">
        <label class="form-label">Cabang</label>
        <select name="cabang_id" class="form-select @error('cabang_id') is-invalid @enderror" required>
            <option value="">-- Pilih Cabang --</option>
            @foreach($cabang as $cbg)
                <option value="{{ $cbg->id }}" {{ old('cabang_id') == $cbg->id ? 'selected' : '' }}>
                    {{ $cbg->nama_cabang }}
                </option>
            @endforeach
        </select>
        @error('cabang_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- Password --}}
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Konfirmasi Password</label>
        <input type="password" name="password_confirmation" class="form-control" required>
    </div>

    {{-- Hidden field tambahan --}}
    <input type="hidden" name="employee_id" value="{{ 'EMP' . rand(1000, 9999) }}">
    <input type="hidden" name="atasan_id" value="{{ auth()->id() }}">

    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Kembali</a>
</form>
</div>

{{-- Script: isi jabatan_id otomatis --}}
<script>
    const jabatanMap = {
        "CEO": 1,
        "CBO": 2,
        "Manager": 3,
        "SBC": 4,
        "BC": 5,
        "Trainee": 6,
        "Administrator": 7,
        "Superadmin": 8,
    };

    document.getElementById('roleSelect').addEventListener('change', function () {
        let selectedRole = this.value;
        document.getElementById('jabatan_id').value = jabatanMap[selectedRole] ?? '';
    });
</script>
@endsection
