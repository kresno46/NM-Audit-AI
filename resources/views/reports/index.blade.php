@extends('layouts.app')

@section('title', 'Audit Reports')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">📊 Audit Reports Overview</h1>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase">Total Audits</h6>
                    <h3 class="fw-bold">{{ $stats['total'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase">Completed</h6>
                    <h3 class="fw-bold">{{ $stats['completed'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase">Pending</h6>
                    <h3 class="fw-bold">{{ $stats['pending'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase">In Progress</h6>
                    <h3 class="fw-bold">{{ $stats['in_progress'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Table -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📋 Audit History</h5>
            <form class="d-flex" method="GET" action="{{ route('reports.index') }}">
                <input type="text" class="form-control form-control-sm me-2" name="search" placeholder="Search name or role..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Date</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Auditor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $session)
                    <tr>
                        <td>{{ $session->auditedUser->name ?? 'N/A' }}</td>
                        <td>{{ $session->auditedUser->role ?? 'N/A' }}</td>
                        <td>{{ $session->auditedUser->cabang->nama_cabang ?? 'N/A' }}</td>
                        <td>{{ $session->created_at->format('M d, Y') }}</td>
                        <td>
                            <span class="badge 
                                @if($session->skor_total >= 80) bg-success
                                @elseif($session->skor_total >= 60) bg-warning
                                @else bg-danger
                                @endif">
                                {{ $session->skor_total }}%
                            </span>
                        </td>
                        <td>
                            @if($session->status === 'completed')
                                <span class="text-success">Completed</span>
                            @elseif($session->status === 'pending')
                                <span class="text-warning">Pending</span>
                            @elseif($session->status === 'in_progress')
                                <span class="text-info">In Progress</span>
                            @else
                                <span>{{ ucfirst($session->status) }}</span>
                            @endif
                        </td>
                        <td>{{ $session->auditor->name ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">No audit sessions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $sessions->links() }}
        </div>
    </div>

</div>
@endsection
