@extends('layouts.app')

@section('title', 'Select Employee for Audit')

@section('content')
<div class="row">
    <div class="col-12 mb-4 d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Select Employee for Audit</h1>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Available Employees</h5>
            </div>
            <div class="card-body">
                <!-- Filter -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="employeeSearch" placeholder="Search employees...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="roleFilter">
                            <option value="">All Roles</option>
                            <option value="Manager">Manager</option>
                            <option value="SBC">SBC</option>
                            <option value="BC">BC</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="branchFilter">
                            <option value="">All Branches</option>
                            @foreach($employees->pluck('cabang.nama_cabang')->unique()->filter()->sort() as $branch)
                                <option value="{{ $branch }}">{{ $branch }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Employee Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="employeeTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Branch</th>
                                <th>Last Audit</th>
                                <th>Avg Score</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $employee)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-light rounded-circle me-2">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $employee->name }}</div>
                                            <small class="text-muted">{{ $employee->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $employee->role }}</td>
                                <td>{{ $employee->cabang?->nama_cabang ?? '-' }}</td>
                                <td>
                                    @if($employee->last_audit_date)
                                        {{ $employee->last_audit_date->format('M d, Y') }}
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </td>
                                <td>
                                    @if($employee->avg_score)
                                        <span class="badge bg-{{ $employee->avg_score >= 80 ? 'success' : ($employee->avg_score >= 60 ? 'warning' : 'danger') }}">
                                            {{ number_format($employee->avg_score, 1) }}%
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('audit.start', $employee) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-play"></i> Start Audit
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $employees->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Tips -->
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Audit Guidelines</h5></div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Before Starting</h6>
                    <ul class="mb-0">
                        <li>Allocate 15–30 minutes</li>
                        <li>Review recent performance</li>
                        <li>Prepare questions</li>
                        <li>Check pending issues</li>
                    </ul>
                </div>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> Remember</h6>
                    <ul class="mb-0">
                        <li>Be objective and fair</li>
                        <li>Focus on constructive feedback</li>
                        <li>Document observations clearly</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Recent Activity</h5></div>
            <div class="card-body">
                @forelse($recent_activities as $activity)
                <div class="mb-2">
                    <small class="text-muted">{{ $activity->created_at->format('M d, H:i') }}</small>
                    <div class="fw-bold">{{ $activity->description }}</div>
                </div>
                @empty
                <div class="text-muted">No recent activity.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Search and filter logic
    document.querySelectorAll('#employeeSearch, #roleFilter, #branchFilter').forEach(input =>
        input.addEventListener('input', filterTable)
    );

    function filterTable() {
        const search = document.getElementById('employeeSearch').value.toLowerCase();
        const role = document.getElementById('roleFilter').value;
        const branch = document.getElementById('branchFilter').value;
        const rows = document.querySelectorAll('#employeeTable tbody tr');

        rows.forEach(row => {
            const name = row.cells[0].innerText.toLowerCase();
            const empRole = row.cells[1].innerText;
            const empBranch = row.cells[2].innerText;

            const match = name.includes(search) &&
                          (!role || empRole === role) &&
                          (!branch || empBranch === branch);

            row.style.display = match ? '' : 'none';
        });
    }
</script>
@endpush
