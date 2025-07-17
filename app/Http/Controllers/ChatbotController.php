<?php

// app/Http/Controllers/ChatbotController.php
namespace App\Http\Controllers;

use App\Models\AuditSession;
use App\Models\AuditQuestion;
use App\Models\AuditAnswer;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
    protected $openAIService;
    
    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }
    
    public function getQuestion(Request $request)
    {
        $request->validate([
            'session_code' => 'required|string',
        ]);
        
        $auditSession = AuditSession::where('session_code', $request->session_code)->firstOrFail();
        
        // Check authorization
        if (Auth::id() !== $auditSession->auditor_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Check if session is in progress
        if ($auditSession->status !== 'in_progress') {
            return response()->json(['error' => 'Session is not in progress'], 400);
        }
        
        try {
            // Get next question number
            $nextQuestionNumber = $auditSession->questions()->count() + 1;
            
            // Generate question based on employee role and previous answers
            $questionData = $this->openAIService->generateQuestion($auditSession, $nextQuestionNumber);
            
            // Save question to database
            $question = AuditQuestion::create([
                'audit_session_code' => $auditSession->id,
                'question_number' => $nextQuestionNumber,
                'question_text' => $questionData['question'],
                'question_type' => $questionData['type'],
                'category' => $questionData['category'],
                'max_score' => $questionData['max_score'],
                'generated_at' => now(),
            ]);
            
            // Update session
            $auditSession->increment('total_questions');
            
            return response()->json([
                'success' => true,
                'question' => [
                    'id' => $question->id,
                    'number' => $question->question_number,
                    'text' => $question->question_text,
                    'type' => $question->question_type,
                    'category' => $question->category,
                    'max_score' => $question->max_score,
                ],
                'progress' => $auditSession->getProgressPercentage(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate question: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function processAnswer(Request $request)
    {
        $request->validate([
            'session_code' => 'required|string',
            'question_id' => 'required|exists:audit_questions,id',
            'answer' => 'required|string|max:2000',
        ]);
        
        $auditSession = AuditSession::where('session_code', $request->session_code)->firstOrFail();
        $question = AuditQuestion::findOrFail($request->question_id);
        
        // Check authorization
        if (Auth::id() !== $auditSession->auditor_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Check if question belongs to this session
        if ($question->audit_session_code !== $auditSession->id) {
            return response()->json(['error' => 'Question does not belong to this session'], 400);
        }
        
        // Check if answer already exists
        $existingAnswer = AuditAnswer::where('audit_question_id', $question->id)->first();
        if ($existingAnswer) {
            return response()->json(['error' => 'Answer already submitted for this question'], 400);
        }
        
        try {
            // Get AI evaluation
            $evaluation = $this->openAIService->evaluateAnswer($question, $request->answer, $auditSession);
            
            // Save answer
            $answer = AuditAnswer::create([
                'audit_session_code' => $auditSession->id,
                'audit_question_id' => $question->id,
                'answer_text' => $request->answer,
                'score' => $evaluation['score'],
                'feedback' => $evaluation['feedback'],
                'ai_evaluation' => $evaluation['details'],
                'answered_at' => now(),
            ]);
            
            // Update session
            $auditSession->increment('answered_questions');
            
            return response()->json([
                'success' => true,
                'answer' => [
                    'id' => $answer->id,
                    'score' => $answer->score,
                    'max_score' => $question->max_score,
                    'percentage' => $answer->getScorePercentage(),
                    'feedback' => $answer->feedback,
                ],
                'progress' => $auditSession->getProgressPercentage(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to process answer: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function getProgress($sessionId)
    {
        $auditSession = AuditSession::where('session_code', $sessionId)->firstOrFail();
        
        // Check authorization
        if (Auth::id() !== $auditSession->auditor_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return response()->json([
            'success' => true,
            'progress' => [
                'total_questions' => $auditSession->total_questions,
                'answered_questions' => $auditSession->answered_questions,
                'percentage' => $auditSession->getProgressPercentage(),
                'status' => $auditSession->status,
                'duration_minutes' => $auditSession->getDurationMinutes(),
            ],
        ]);
    }
}