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