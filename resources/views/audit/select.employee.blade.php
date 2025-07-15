<!-- resources/views/audit/select-employee.blade.php -->
@extends('layouts.app')

@section('title', 'Select Employee for Audit')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Select Employee for Audit</h1>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Available Employees</h5>
            </div>
            <div class="card-body">
                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="employeeSearch" placeholder="Search employees...">
                        </div>
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
                                <td>{{ $employee->branch->name }}</td>
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
                <div class="d-flex justify-content-center">
                    {{ $employees->links() }}
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Audit Guidelines</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Before Starting</h6>
                    <ul class="mb-0">
                        <li>Ensure you have adequate time (15-30 minutes)</li>
                        <li>Review employee's recent performance</li>
                        <li>Prepare relevant questions for their role</li>
                        <li>Check for any pending issues</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> Remember</h6>
                    <ul class="mb-0">
                        <li>Be objective and fair</li>
                        <li>Focus on constructive feedback</li>
                        <li>Document observations clearly</li>
                        <li>Follow company audit procedures</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                @foreach($recent_activities as $activity)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <small class="text-muted">{{ $activity->created_at->format('M d, H:i') }}</small>
                        <div class="fw-bold">{{ $activity->description }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Search functionality
    document.getElementById('employeeSearch').addEventListener('input', function() {
        filterTable();
    });
    
    document.getElementById('roleFilter').addEventListener('change', function() {
        filterTable();
    });
    
    document.getElementById('branchFilter').addEventListener('change', function() {
        filterTable();
    });
    
    function filterTable() {
        const searchTerm = document.getElementById('employeeSearch').value.toLowerCase();
        const roleFilter = document.getElementById('roleFilter').value;
        const branchFilter = document.getElementById('branchFilter').value;
        
        const rows = document.querySelectorAll('#employeeTable tbody tr');
        
        rows.forEach(row => {
            const name = row.cells[0].textContent.toLowerCase();
            const role = row.cells[1].textContent;
            const branch = row.cells[2].textContent;
            
            const matchesSearch = name.includes(searchTerm);
            const matchesRole = !roleFilter || role === roleFilter;
            const matchesBranch = !branchFilter || branch === branchFilter;
            
            if (matchesSearch && matchesRole && matchesBranch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
@endpush