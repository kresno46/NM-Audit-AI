@extends('layouts.app')

@section('title', 'Hasil Audit')

@section('content')
<div class="container py-4">
    <h3>Hasil Audit: {{ $auditSession->employee->name }}</h3>
    <p>Jabatan: {{ $auditSession->employee->role }} | Cabang: {{ $auditSession->employee->cabang->nama ?? '-' }}</p>
    <hr>

    <div class="mb-4">
        <h5>Rekomendasi AI:</h5>
        <span class="badge {{ $auditSession->getRecommendationBadgeClass() }}">
            {{ $auditSession->rekomendasi_ai ?? 'Belum tersedia' }}
        </span>
        <p class="mt-2"><strong>Skor Total:</strong> 
            <span class="{{ $auditSession->getScoreColorClass() }}">
                {{ $auditSession->skor_total ?? '-' }}
            </span>
        </p>
    </div>

    @if($auditSession->catatan_ai)
    <div class="mb-4">
        <h5>Ringkasan Audit</h5>
        <p>{{ $auditSession->catatan_ai['ringkasan'] ?? '-' }}</p>

        <h6>Kekuatan Utama:</h6>
        <ul>
            @foreach($auditSession->catatan_ai['kekuatan_utama'] ?? [] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>

        <h6>Area for Improvement:</h6>
        <ul>
            @foreach($auditSession->catatan_ai['area_development'] ?? [] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>

        <h6>Action Plan:</h6>
        <ul>
            @foreach($auditSession->catatan_ai['action_plan'] ?? [] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>

        <p><strong>Timeline:</strong> {{ $auditSession->catatan_ai['timeline'] ?? '-' }}</p>
    </div>
    @endif

    <div class="mt-4">
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Kembali ke Dashboard</a>
        <a href="{{ route('audit.export', $auditSession->session_code) }}" class="btn btn-success">Unduh PDF</a>
    </div>
</div>
@endsection
