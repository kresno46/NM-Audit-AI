@extends('layouts.app')

@section('title', 'Audit History')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">🕓 Audit History</h1>

    <div class="card shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Auditor</th>
                        <th>Date</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($audits as $audit)
                    <tr>
                        <td>{{ $audit->employee->name }}</td>
                        <td>{{ $audit->employee->role }}</td>
                        <td>{{ $audit->employee->cabang->name ?? '-' }}</td>
                        <td>{{ $audit->auditor->name }}</td>
                        <td>{{ $audit->created_at->format('M d, Y') }}</td>
                        <td><span class="badge bg-{{ $audit->score >= 80 ? 'success' : ($audit->score >= 60 ? 'warning' : 'danger') }}">{{ $audit->score }}%</span></td>
                        <td><span class="text-{{ $audit->status === 'passed' ? 'success' : 'danger' }}">{{ ucfirst($audit->status) }}</span></td>
                        <td><a href="{{ route('audit.result', $audit->id) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
