<!-- resources/views/audit/conduct.blade.php -->
@extends('layouts.app')

@section('title', 'Conduct Audit')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Audit: {{ $employee->name }}</h1>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger" onclick="pauseAudit()">
                    <i class="fas fa-pause"></i> Pause
                </button>
                <a href="{{ route('audit.select-employee') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Audit Questions</h5>
                <div class="d-flex align-items-center gap-3">
                    <div class="progress" style="width: 200px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ ($current_question / $total_questions) * 100 }}%"></div>
                    </div>
                    <span class="text-muted">{{ $current_question }}/{{ $total_questions }}</span>
                </div>
            </div>
            <div class="card-body">
                <form id="auditForm" action="{{ route('audit.submit-answer') }}" method="POST">
                    @csrf
                    <input type="hidden" name="audit_id" value="{{ $audit->id }}">
                    <input type="hidden" name="question_id" value="{{ $question->id }}">
                    
                    <div class="mb-4">
                        <h6 class="text-primary">Question {{ $current_question }}</h6>
                        <p class="lead">{{ $question->question_text }}</p>
                        
                        @if($question->context)
                        <div class="alert alert-light">
                            <small><strong>Context:</strong> {{ $question->context }}</small>
                        </div>
                        @endif
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Your Answer</label>
                        <textarea class="form-control" name="answer" rows="4" required 
                                  placeholder="Provide a detailed answer based on your observation..."></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Evidence/Notes (Optional)</label>
                        <textarea class="form-control" name="notes" rows="3" 
                                  placeholder="Additional notes, evidence, or observations..."></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        @if($current_question > 1)
                        <button type="button" class="btn btn-outline-secondary" onclick="previousQuestion()">
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        @endif
                        
                        <div class="ms-auto">
                            @if($current_question < $total_questions)
                            <button type="submit" class="btn btn-primary">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>
                            @else
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Complete Audit
                            </button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Employee Info -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Employee Information</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-lg bg-light rounded-circle me-3">
                        <i class="fas fa-user text-primary fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">{{ $employee->name }}</h6>
                        <small class="text-muted">{{ $employee->email }}</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">Role</small>
                        <div class="fw-bold">{{ $employee->role }}</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Branch</small>
                        <div class="fw-bold">{{ $employee->branch->name }}</div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">Last Audit</small>
                        <div class="fw-bold">
                            @if($employee->last_audit_date)
                                {{ $employee->last_audit_date->format('M d, Y') }}
                            @else
                                Never
                            @endif
                        </div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Avg Score</small>
                        <div class="fw-bold">
                            @if($employee->avg_score)
                                {{ number_format($employee->avg_score, 1) }}%
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- AI Assistant -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">AI Assistant</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-robot"></i> Need Help?</h6>
                    <p class="mb-2">The AI assistant can help you with:</p>
                    <ul class="mb-0">
                        <li>Question clarification</li>
                        <li>Scoring guidelines</li>
                        <li>Best practices</li>
                        <li>Documentation tips</li>
                    </ul>
                </div>
                
                <button class="btn btn-outline-primary w-100" onclick="openChatbot()">
                    <i class="fas fa-comments"></i> Ask AI Assistant
                </button>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-warning btn-sm" onclick="saveProgress()">
                        <i class="fas fa-save"></i> Save Progress
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="viewGuidelines()">
                        <i class="fas fa-book"></i> View Guidelines
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="takeBreak()">
                        <i class="fas fa-coffee"></i> Take Break
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Auto-save indicator -->
<div id="autoSaveIndicator" class="position-fixed bottom-0 start-50 translate-middle-x bg-success text-white px-3 py-2 rounded-top" style="display: none;">
    <i class="fas fa-check"></i> Auto-saved
</div>

@endsection

@push('scripts')
<script>
    let autoSaveInterval;
    
    // Auto-save functionality
    function startAutoSave() {
        autoSaveInterval = setInterval(function() {
            saveProgress(true);
        }, 30000); // Auto-save every 30 seconds
    }
    
    function saveProgress(isAutoSave = false) {
        const form = document.getElementById('auditForm');
        const formData = new FormData(form);
        
        fetch('{{ route("audit.save-progress") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (isAutoSave) {
                    showAutoSaveIndicator();
                } else {
                    showAlert('Progress saved successfully!', 'success');
                }
            }
        })
        .catch(error => {
            console.error('Error saving progress:', error);
        });
    }
    
    function showAutoSaveIndicator() {
        const indicator = document.getElementById('autoSaveIndicator');
        indicator.style.display = 'block';
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 2000);
    }
    
    function pauseAudit() {
        if (confirm('Are you sure you want to pause this audit? Your progress will be saved.')) {
            saveProgress();
            window.location.href = '{{ route("dashboard") }}';
        }
    }
    
    function previousQuestion() {
        window.location.href = '{{ route("audit.previous-question", $audit) }}';
    }
    
    function openChatbot() {
        document.getElementById('chatbotToggle').click();
    }
    
    function viewGuidelines() {
        // Open guidelines modal or redirect
        alert('Guidelines feature coming soon!');
    }
    
    function takeBreak() {
        if (confirm('Take a break? Your progress will be saved.')) {
            saveProgress();
            alert('Progress saved. You can resume the audit later from your dashboard.');
        }
    }
    
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.row'));
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
    
    // Initialize auto-save when page loads
    document.addEventListener('DOMContentLoaded', function() {
        startAutoSave();
    });
    
    // Clean up auto-save interval when leaving page
    window.addEventListener('beforeunload', function() {
        if (autoSaveInterval) {
            clearInterval(autoSaveInterval);
        }
    });
</script>
@endpush($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <!-- Employee List -->
                <div class="table-responsive">
                    <table class="table table-hover" id="employeeTable">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Role</th>
                                <th>Branch</th>
                                <th>Last Audit</th>
                                <th>Avg Score</th>
                                <th>Action</th>
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