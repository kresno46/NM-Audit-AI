<?php

namespace App\Http\Livewire;

use App\Models\AuditSession as AuditSessionModel;
use App\Services\AuditService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class AuditSession extends Component
{
    public $session;
    public $currentCategory = 'leadership';
    public $currentQuestionNumber = 1;
    public $currentQuestion = '';
    public $currentAnswer = '';
    public $feedback = '';
    public $score = 0;
    public $isProcessing = false;
    public $showFeedback = false;
    public $categories = ['leadership', 'teamwork', 'recruitment', 'effectiveness', 'innovation'];
    public $categoryIndex = 0;
    public $progress = [];
    public $sessionStarted = false;
    public $sessionCompleted = false;

    protected $auditService;

    public function mount($sessionId)
    {
        $this->session = AuditSessionModel::with(['auditedUser', 'auditLogs'])->findOrFail($sessionId);
        
        // Check authorization
        if ($this->session->auditor_id !== Auth::id()) {
            abort(403, 'Unauthorized access to audit session.');
        }
        
        $this->auditService = app(AuditService::class);
        $this->initializeProgress();
        
        // Check if session is already started or completed
        if ($this->session->status === 'in_progress') {
            $this->sessionStarted = true;
            $this->loadCurrentQuestion();
        } elseif ($this->session->status === 'completed') {
            $this->sessionCompleted = true;
        }
    }

    public function startSession()
    {
        try {
            $this->auditService->beginInterview($this->session->id);
            $this->sessionStarted = true;
            $this->loadNextQuestion();
            
            session()->flash('message', 'Sesi audit dimulai!');
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function loadNextQuestion()
    {
        try {
            $this->isProcessing = true;
            
            $question = $this->auditService->getNextQuestion(
                $this->session->id,
                $this->currentCategory,
                $this->currentQuestionNumber
            );
            
            $this->currentQuestion = $question['question'];
            $this->currentAnswer = '';
            $this->feedback = '';
            $this->score = 0;
            $this->showFeedback = false;
            $this->isProcessing = false;
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->isProcessing = false;
        }
    }

    public function loadCurrentQuestion()
    {
        // Determine current position based on audit logs
        $logs = $this->session->auditLogs;
        $totalAnswered = $logs->count();
        
        if ($totalAnswered >= 15) {
            $this->sessionCompleted = true;
            return;
        }
        
        $this->categoryIndex = intval($totalAnswered / 3);
        $this->currentQuestionNumber = ($totalAnswered % 3) + 1;
        $this->currentCategory = $this->categories[$this->categoryIndex];
        
        $this->loadNextQuestion();
    }

    public function submitAnswer()
    {
        $this->validate([
            'currentAnswer' => 'required|min:10|max:1000'
        ]);
        
        try {
            $this->isProcessing = true;
            
            $result = $this->auditService->processAnswer(
                $this->session->id,
                $this->currentQuestionNumber,
                $this->currentCategory,
                $this->currentQuestion,
                $this->currentAnswer
            );
            
            $this->feedback = $result['feedback'];
            $this->score = $result['score'];
            $this->showFeedback = true;
            
            // Update progress
            $this->updateProgress();
            
            $this->isProcessing = false;
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->isProcessing = false;
        }
    }

    public function nextQuestion()
    {
        $this->currentQuestionNumber++;
        
        // Check if we need to move to next category
        if ($this->currentQuestionNumber > 3) {
            $this->categoryIndex++;
            $this->currentQuestionNumber = 1;
            
            // Check if all categories are completed
            if ($this->categoryIndex >= count($this->categories)) {
                $this->sessionCompleted = true;
                return;
            }
            
            $this->currentCategory = $this->categories[$this->categoryIndex];
        }
        
        $this->loadNextQuestion();
    }

    public function completeSession()
    {
        try {
            $this->auditService->completeAuditSession($this->session->id);
            $this->sessionCompleted = true;
            
            session()->flash('message', 'Sesi audit berhasil diselesaikan!');
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    private function initializeProgress()
    {
        foreach ($this->categories as $category) {
            $this->progress[$category] = [
                'completed' => 0,
                'total' => 3,
                'percentage' => 0
            ];
        }
        
        $this->updateProgress();
    }

    private function updateProgress()
    {
        $logs = $this->session->auditLogs()->get();
        
        foreach ($this->categories as $category) {
            $completed = $logs->where('kategori', $category)->count();
            $this->progress[$category] = [
                'completed' => $completed,
                'total' => 3,
                'percentage' => ($completed / 3) * 100
            ];
        }
    }

    public function render()
    {
        return view('livewire.audit-session');
    }
}