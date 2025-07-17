<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditSession;
use App\Models\AuditQuestion;
use App\Models\AuditAnswer;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotApiController extends Controller
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

        if (Auth::id() !== $auditSession->auditor_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($auditSession->status !== 'in_progress') {
            return response()->json(['error' => 'Session is not in progress'], 400);
        }

        $nextQuestionNumber = $auditSession->questions()->count() + 1;

        $kategori = getNextKategori($nextQuestionNumber); // fungsi bantu rotasi kategori
        $questionText = $this->openAIService->generateAuditQuestion($auditSession->employee->jabatan->name, $kategori, $nextQuestionNumber);

        $question = AuditQuestion::create([
            'audit_session_code' => $auditSession->id,
            'question_number' => $nextQuestionNumber,
            'question_text' => $questionText,
            'category' => $kategori,
            'max_score' => 5,
            'generated_at' => now(),
        ]);

        $auditSession->increment('total_questions');

        return response()->json([
            'success' => true,
            'question' => [
                'id' => $question->id,
                'number' => $question->question_number,
                'text' => $question->question_text,
                'category' => $question->category,
                'max_score' => $question->max_score,
            ],
            'progress' => $auditSession->getProgressPercentage(),
        ]);
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

        if (Auth::id() !== $auditSession->auditor_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($question->audit_session_code !== $auditSession->id) {
            return response()->json(['error' => 'Question does not belong to this session'], 400);
        }

        if (AuditAnswer::where('audit_question_id', $question->id)->exists()) {
            return response()->json(['error' => 'Answer already submitted for this question'], 400);
        }

        $evaluation = $this->openAIService->analyzeAnswer(
            $question->question_text,
            $request->answer,
            $question->category,
            $auditSession->employee->jabatan->name
        );

        $answer = AuditAnswer::create([
            'audit_session_code' => $auditSession->id,
            'audit_question_id' => $question->id,
            'answer_text' => $request->answer,
            'score' => $evaluation['skor'],
            'feedback' => $evaluation['feedback'],
            'ai_evaluation' => $evaluation,
            'answered_at' => now(),
        ]);

        $auditSession->increment('answered_questions');

        return response()->json([
            'success' => true,
            'answer' => [
                'id' => $answer->id,
                'score' => $answer->score,
                'percentage' => $answer->getScorePercentage(),
                'feedback' => $answer->feedback,
            ],
            'progress' => $auditSession->getProgressPercentage(),
        ]);
    }
}

function getNextKategori($index)
{
    $kategori = ['leadership', 'teamwork', 'recruitment', 'effectiveness', 'innovation'];
    return $kategori[($index - 1) % count($kategori)];
}
