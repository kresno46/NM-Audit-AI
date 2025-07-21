<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
    }


    /**
     * Generate audit question based on position and category
     */

    public function generateQuestion($session, $questionNumber)
    {
        try {
            $response = Http::withToken(config('services.openai.api_key'))

                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an audit assistant...'],
                        ['role' => 'user', 'content' => 'Buat pertanyaan ke-' . $questionNumber],
                    ],
                    'temperature' => 0.7,
                ]);

            if (!$response->successful()) {
                \Log::error('OpenAI API failed', ['status' => $response->status(), 'body' => $response->body()]);
                throw new \Exception('OpenAI API Error: ' . $response->body());
            }

            $json = $response->json();

            return [
                'question' => $json['choices'][0]['message']['content'] ?? 'Pertanyaan tidak tersedia',
                'type' => 'essay',
                'category' => 'general',
                'max_score' => 10,
            ];
        } catch (\Throwable $e) {
            \Log::error('OpenAI Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw new \Exception('Invalid response from OpenAI');
        }
    }


    /**
     * Generate role-specific question using advanced prompts
     */
    public function generateRoleSpecificQuestion($role, $category, $questionNumber)
    {
        $prompt = $this->buildRoleSpecificQuestionPrompt($role, $category, $questionNumber);
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert HR professional specializing in performance audits for securities/brokerage companies. Create insightful, role-specific questions that assess competencies relevant to the Indonesian market.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 200,
                'temperature' => 0.7,
            ]);

            $data = $response->json();
            
            if (isset($data['choices'][0]['message']['content'])) {
                return $data['choices'][0]['message']['content'];
            }
            
            throw new Exception('Invalid response from OpenAI');
            
        } catch (Exception $e) {
            Log::error('OpenAI Role-Specific Question Error: ' . $e->getMessage());
            return $this->getFallbackRoleQuestion($role, $category, $questionNumber);
        }
    }

    /**
     * Analyze role-specific answer with detailed feedback
     */
    public function analyzeRoleSpecificAnswer($question, $answer, $category, $auditedRole, $templateRole)
    {
        $prompt = $this->buildRoleSpecificAnalysisPrompt($question, $answer, $category, $auditedRole, $templateRole);
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert performance analyst for securities companies. Provide detailed, constructive feedback in Indonesian language with specific improvement suggestions.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.3,
            ]);

            $data = $response->json();
            
            if (isset($data['choices'][0]['message']['content'])) {
                $content = $data['choices'][0]['message']['content'];
                return json_decode($content, true) ?: $this->getFallbackRoleAnalysis();
            }
            
            throw new Exception('Invalid response from OpenAI');
            
        } catch (Exception $e) {
            Log::error('OpenAI Role-Specific Analysis Error: ' . $e->getMessage());
            return $this->getFallbackRoleAnalysis();
        }
    }


    private function determineCategory($session, $qNumber)
    {
        $categories = ['leadership', 'teamwork', 'recruitment', 'effectiveness', 'innovation'];
        return $categories[($qNumber - 1) % count($categories)];
    }


    public function evaluateAnswer($question, $answerText, $auditSession)
    {
        $jabatan  = $auditSession->employee->role ?? 'Staff';
        $kategori = $question->category;
        $pertanyaan = $question->question_text;

        $result = $this->analyzeAnswer($pertanyaan, $answerText, $kategori, $jabatan);

        return [
            'score'    => $result['skor'] ?? 3.0,
            'feedback' => $result['feedback'] ?? 'Jawaban cukup baik namun bisa dikembangkan lagi.',
            'details'  => $result
        ];
    }



    public function generateAuditQuestion($jabatan, $kategori, $questionNumber)
    {
        $prompt = $this->buildQuestionPrompt($jabatan, $kategori, $questionNumber);
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Anda adalah HR expert yang ahli dalam audit karyawan perusahaan pialang/securities. Berikan pertanyaan yang mendalam dan relevan.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 200,
                'temperature' => 0.7,
            ]);

            $data = $response->json();
            
            if (isset($data['choices'][0]['message']['content'])) {
                return $data['choices'][0]['message']['content'];
            }
            
            throw new Exception('Invalid response from OpenAI');
            
        } catch (Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());
            return $this->getFallbackQuestion($kategori, $questionNumber);
        }
    }

    /**
     * Analyze answer and provide score + feedback
     */
    public function analyzeAnswer($question, $answer, $kategori, $jabatan)
    {
        $prompt = $this->buildAnalysisPrompt($question, $answer, $kategori, $jabatan);
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Anda adalah AI analyzer yang expert dalam menilai jawaban audit karyawan. Berikan analisis objektif dalam format JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 300,
                'temperature' => 0.3,
            ]);

            $data = $response->json();
            
            if (isset($data['choices'][0]['message']['content'])) {
                $content = $data['choices'][0]['message']['content'];
                return json_decode($content, true) ?: $this->getFallbackAnalysis();
            }
            
            throw new Exception('Invalid response from OpenAI');
            
        } catch (Exception $e) {
            Log::error('OpenAI Analysis Error: ' . $e->getMessage());
            return $this->getFallbackAnalysis();
        }
    }

    /**
     * Generate final audit summary and recommendation
     */
    public function generateFinalSummary($auditLogs, $scores, $userInfo)
    {
        $prompt = $this->buildSummaryPrompt($auditLogs, $scores, $userInfo);
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Anda adalah senior HR manager yang memberikan rekomendasi final audit karyawan. Berikan analisis komprehensif dan rekomendasi yang objektif.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.2,
            ]);

            $data = $response->json();
            
            if (isset($data['choices'][0]['message']['content'])) {
                $content = $data['choices'][0]['message']['content'];
                return json_decode($content, true) ?: $this->getFallbackSummary($scores);
            }
            
            throw new Exception('Invalid response from OpenAI');
            
        } catch (Exception $e) {
            Log::error('OpenAI Summary Error: ' . $e->getMessage());
            return $this->getFallbackSummary($scores);
        }
    }

    private function buildQuestionPrompt($jabatan, $kategori, $questionNumber)
    {
        return "Buatkan pertanyaan audit yang mendalam untuk jabatan {$jabatan} pada kategori {$kategori} (pertanyaan ke-{$questionNumber}). 
        Pertanyaan harus:
        1. Spesifik untuk industri pialang/securities
        2. Menggali kompetensi sesuai level jabatan
        3. Mengukur kemampuan praktis dan leadership
        4. Dalam bahasa Indonesia yang profesional
        5. Maksimal 2 kalimat
        
        Berikan hanya pertanyaan tanpa penjelasan tambahan.";
    }

    private function buildAnalysisPrompt($question, $answer, $kategori, $jabatan)
    {
        return "Analisis jawaban audit karyawan berikut:
        
        Pertanyaan: {$question}
        Jawaban: {$answer}
        Kategori: {$kategori}
        Jabatan: {$jabatan}
        
        Berikan analisis dalam format JSON berikut:
        {
            \"skor\": 4.2,
            \"sentiment\": {
                \"overall\": \"positive\",
                \"confidence\": 0.85,
                \"key_words\": [\"leadership\", \"proactive\", \"target\"]
            },
            \"feedback\": \"Jawaban menunjukkan pemahaman yang baik tentang leadership...\",
            \"kekuatan\": [\"Komunikasi jelas\", \"Pengalaman relevan\"],
            \"area_improvement\": [\"Perlu lebih detail dalam strategi\"]
        }
        
        Skor 1-5 dimana 5 = excellent, 4 = good, 3 = average, 2 = below average, 1 = poor";
    }

    private function buildSummaryPrompt($auditLogs, $scores, $userInfo)
    {
        $logsText = '';
        foreach ($auditLogs as $log) {
            $logsText .= "Q: {$log['pertanyaan']}\nA: {$log['jawaban']}\nSkor: {$log['skor_jawaban']}\n\n";
        }
        
        return "Buat ringkasan audit final untuk karyawan:
        
        Nama: {$userInfo['name']}
        Jabatan: {$userInfo['role']}
        Cabang: {$userInfo['cabang']}
        
        SKOR KATEGORI:
        - Leadership: {$scores['leadership']}
        - Teamwork: {$scores['teamwork']}
        - Recruitment: {$scores['recruitment']}
        - Effectiveness: {$scores['effectiveness']}
        - Innovation: {$scores['innovation']}
        - Total: {$scores['total']}
        
        DETAIL JAWABAN:
        {$logsText}
        
        Berikan analisis dalam format JSON:
        {
            \"ringkasan\": \"Ringkasan overall performa...\",
            \"kekuatan_utama\": [\"Kekuatan 1\", \"Kekuatan 2\"],
            \"area_development\": [\"Area 1\", \"Area 2\"],
            \"rekomendasi\": \"PROMOSI\",
            \"alasan_rekomendasi\": \"Penjelasan mengapa rekomendasi ini diberikan\",
            \"action_plan\": [\"Aksi 1\", \"Aksi 2\"],
            \"timeline\": \"3-6 bulan\"
        }
        
        Rekomendasi: PROMOSI (skor ≥4.0), TETAP (skor 3.0-3.9), DEMOSI (skor <3.0)";
    }

    private function getFallbackQuestion($kategori, $questionNumber)
    {
        $questions = [
            'leadership' => [
                'Bagaimana cara Anda memimpin tim untuk mencapai target bulanan?',
                'Ceritakan pengalaman Anda mengatasi konflik dalam tim.',
                'Bagaimana strategi Anda dalam memotivasi bawahan?'
            ],
            'teamwork' => [
                'Bagaimana Anda berkolaborasi dengan rekan kerja?',
                'Ceritakan pengalaman kerja sama yang paling berkesan.',
                'Bagaimana cara Anda berbagi knowledge dengan tim?'
            ],
            'recruitment' => [
                'Bagaimana strategi Anda dalam mencari nasabah baru?',
                'Apa yang menjadi key success factor dalam closing?',
                'Bagaimana Anda mengatasi objection nasabah?'
            ],
            'effectiveness' => [
                'Bagaimana Anda mengelola waktu kerja dengan efektif?',
                'Apa yang Anda lakukan untuk meningkatkan produktivitas?',
                'Bagaimana cara Anda mengukur keberhasilan kerja?'
            ],
            'innovation' => [
                'Ide inovatif apa yang pernah Anda implementasikan?',
                'Bagaimana Anda beradaptasi dengan perubahan pasar?',
                'Apa rencana pengembangan bisnis Anda ke depan?'
            ]
        ];
        
        $categoryQuestions = $questions[$kategori] ?? $questions['effectiveness'];
        $index = ($questionNumber - 1) % count($categoryQuestions);
        
        return $categoryQuestions[$index];
    }

    private function getFallbackAnalysis()
    {
        return [
            'skor' => 3.0,
            'sentiment' => [
                'overall' => 'neutral',
                'confidence' => 0.5,
                'key_words' => ['standard', 'average']
            ],
            'feedback' => 'Jawaban menunjukkan pemahaman standar terhadap pertanyaan yang diajukan.',
            'kekuatan' => ['Menjawab dengan lengkap'],
            'area_improvement' => ['Perlu lebih spesifik dan detail']
        ];
    }

    private function getFallbackSummary($scores)
    {
        $totalScore = $scores['total'];
        $rekomendasi = $totalScore >= 4.0 ? 'PROMOSI' : ($totalScore >= 3.0 ? 'TETAP' : 'DEMOSI');
        
        return [
            'ringkasan' => 'Karyawan menunjukkan performa yang sesuai dengan standar jabatan.',
            'kekuatan_utama' => ['Konsisten dalam pekerjaan', 'Memiliki pengalaman yang relevan'],
            'area_development' => ['Perlu peningkatan dalam beberapa area kompetensi'],
            'rekomendasi' => $rekomendasi,
            'alasan_rekomendasi' => 'Berdasarkan skor total ' . $totalScore . ' yang menunjukkan performa sesuai standar.',
            'action_plan' => ['Mengikuti training lanjutan', 'Meningkatkan target pencapaian'],
            'timeline' => '3-6 bulan'
        ];
    }
}