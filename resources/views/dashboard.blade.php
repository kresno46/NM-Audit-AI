<!-- resources/views/dashboard.blade.php -->
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Dashboard</h1>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="refreshStats()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <a href="{{ route('audit.select-employee') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Audit
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Audits</h6>
                        <h3 class="mb-0">{{ $stats['total_audits'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clipboard-check fa-2x opacity-75"></i>
                    </div>
                </div>
                <small class="opacity-75">
                    <i class="fas fa-arrow-up"></i> 
                    {{ $stats['this_month_audits'] ?? 0 }} this month
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Completed</h6>
                        <h3 class="mb-0">{{ $stats['completed_audits'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
                <small class="opacity-75">
                    {{ number_format($stats['completion_rate'], 1) }}% completion rate
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pending</h6>
                        <h3 class="mb-0">{{ $stats['in_progress_audits'] ?? 0 }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x opacity-75"></i>
                    </div>
                </div>
                <small class="opacity-75">
                    {{ $stats['overdue_audits'] ?? 0 }} overdue
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Avg Score</h6>
                        <h3 class="mb-0">{{ number_format($stats['avg_score'] ?? 0, 1) }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-star fa-2x opacity-75"></i>
                    </div>
                </div>
                <small class="opacity-75">
                    Out of 100 points
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Audit Trends</h5>
            </div>
            <div class="card-body">
                <canvas id="auditTrendsChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Score Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="scoreDistributionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Audits -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Audits</h5>
                <a href="{{ route('audit.history') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Auditor</th>
                                <th>Date</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent_audits as $audit)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-light rounded-circle me-2">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $audit->employee->name }}</div>
                                            <small class="text-muted">{{ $audit->employee->role }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $audit->auditor->name }}</td>
                                <td>{{ $audit->created_at->format('M d, Y') }}</td>
                                <td>
                                    @if($audit->final_score)
                                        <span class="badge bg-{{ $audit->final_score >= 80 ? 'success' : ($audit->final_score >= 60 ? 'warning' : 'danger') }}">
                                            {{ $audit->final_score }}%
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $audit->status }}">
                                        {{ ucfirst($audit->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('audit.show', $audit) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($audit->status === 'completed')
                                        <a href="{{ route('audit.report', $audit) }}" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('audit.select-employee') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Start New Audit
                    </a>
                    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-chart-bar"></i> Generate Report
                    </a>
                    <a href="{{ route('audit.history') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-history"></i> View History
                    </a>
                    @if(auth()->user()->hasRole(['CEO', 'CBO']))
                    <!-- Removed link to undefined route 'admin.users.index' -->
                    <!-- <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-users-cog"></i> Manage Users
                    </a> -->
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Upcoming Audits -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Upcoming Audits</h5>
            </div>
            <div class="card-body">
                @if($upcoming_audits->count() > 0)
                    @foreach($upcoming_audits as $audit)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="fw-bold">{{ $audit->employee->name }}</div>
                            <small class="text-muted">{{ $audit->scheduled_date->format('M d, Y') }}</small>
                        </div>
                        <span class="badge bg-warning">Pending</span>
                    </div>
                    @endforeach
                @else
                    <p class="text-muted mb-0">No upcoming audits scheduled.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Performance by Branch (for CEO/CBO) -->
@if(auth()->user()->hasRole(['CEO', 'CBO']))
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Performance by Branch</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Total Audits</th>
                                <th>Completed</th>
                                <th>Avg Score</th>
                                <th>Compliance Rate</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branch_performance as $branch)
                            <tr>
                                <td>{{ $branch->name }}</td>
                                <td>{{ $branch->total_audits }}</td>
                                <td>{{ $branch->completed_audits }}</td>
                                <td>
                                    <span class="badge bg-{{ $branch->avg_score >= 80 ? 'success' : ($branch->avg_score >= 60 ? 'warning' : 'danger') }}">
                                        {{ number_format($branch->avg_score, 1) }}%
                                    </span>
                                </td>
                                <td>{{ number_format($branch->compliance_rate, 1) }}%</td>
                                <td>
                                    <a href="{{ route('reports.branch', $branch) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Audit Trends Chart
    const auditTrendsCtx = document.getElementById('auditTrendsChart').getContext('2d');
    const auditTrendsChart = new Chart(auditTrendsCtx, {
        type: 'line',
        data: {
            labels: @json($chartData['months']),
            datasets: [{
                label: 'Completed Audits',
                data: @json($chartData['completed']),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }, {
                label: 'Total Audits',
                data: @json($chartData['audits']),
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Audit Completion Trends'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Score Distribution Chart
    const scoreDistributionCtx = document.getElementById('scoreDistributionChart').getContext('2d');
    const scoreDistributionChart = new Chart(scoreDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Excellent (80-100)', 'Good (60-79)', 'Poor (0-59)'],
            datasets: [{
                data: @json($chartData['score_distribution'] ?? []),
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Score Distribution'
                }
            }
        }
    });

    // Refresh stats function
    function refreshStats() {
        // Show loading indicator
        const refreshBtn = document.querySelector('[onclick="refreshStats()"]');
        const originalText = refreshBtn.innerHTML;
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        refreshBtn.disabled = true;
        
        // Simulate API call (replace with actual AJAX call)
        setTimeout(() => {
            location.reload();
        }, 1000);
    }
</script>
@endpush