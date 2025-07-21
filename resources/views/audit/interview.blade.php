@extends('layouts.app')

@section('title', 'Interview Audit')

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-body">
            <h4>Audit: {{ $auditSession->employee->name }} ({{ $auditSession->employee->jabatan->nama }})</h4>
            <hr>
            <div id="audit-box">
                <div class="mb-3">
                    <label class="form-label fw-bold">Pertanyaan:</label>
                    <div id="question-box" class="form-control bg-light" readonly>Memuat pertanyaan...</div>
                </div>

                <div class="mb-3">
                    <label for="answer" class="form-label">Jawaban Anda:</label>
                    <textarea class="form-control" id="answer" rows="4" placeholder="Tulis jawaban di sini..."></textarea>
                </div>

                <div class="mb-3">
                    <button id="submitAnswer" class="btn btn-primary">Kirim Jawaban</button>
                </div>

                <div class="mb-3">
                    <p><strong>Skor:</strong> <span id="score">-</span></p>
                    <p><strong>Feedback:</strong> <span id="feedback">-</span></p>
                </div>

                <div class="mb-3">
                    <button id="nextQuestion" class="btn btn-outline-secondary" disabled>Pertanyaan Berikutnya</button>
                    <span class="ms-3" id="progressText">0% selesai</span>
                </div>

                <div>
                    <form method="POST" action="{{ route('audit.finish', $auditSession->session_code) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">Selesai Audit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sessionCode = "{{ $auditSession->session_code }}";
    const apiBase = '/chatbot';
    let currentQuestionId = null;

    const questionBox = document.getElementById('question-box');
    const answerBox = document.getElementById('answer');
    const submitBtn = document.getElementById('submitAnswer');
    const nextBtn = document.getElementById('nextQuestion');
    const scoreEl = document.getElementById('score');
    const feedbackEl = document.getElementById('feedback');
    const progressText = document.getElementById('progressText');

    // 👇 Sanctum setup
    axios.defaults.withCredentials = true;
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    async function loadQuestion() {
        questionBox.innerText = 'Memuat pertanyaan...';
        try {
            // 👇 WAJIB panggil ini dulu
            await axios.get('/sanctum/csrf-cookie');

            const res = await axios.post(`${apiBase}/question`, {
                session_code: sessionCode
            });

            const q = res.data.question;
            questionBox.innerText = q.text;
            currentQuestionId = q.id;
            nextBtn.disabled = true;
            answerBox.value = '';
            scoreEl.innerText = '-';
            feedbackEl.innerText = '-';
            progressText.innerText = res.data.progress + '% selesai';
        } catch (err) {
            questionBox.innerText = 'Gagal memuat pertanyaan.';
            console.error(err);
        }
    }

    submitBtn.addEventListener('click', async () => {
        const answer = answerBox.value.trim();
        if (!answer || !currentQuestionId) return alert('Silakan isi jawaban terlebih dahulu.');

        submitBtn.disabled = true;
        try {
            await axios.get('/sanctum/csrf-cookie');

            const res = await axios.post(`${apiBase}/answer`, {
                session_code: sessionCode,
                question_id: currentQuestionId,
                answer: answer
            });

            const data = res.data.answer;
            scoreEl.innerText = `${data.score}/${data.max_score}`;
            feedbackEl.innerText = data.feedback;
            progressText.innerText = res.data.progress + '% selesai';
            nextBtn.disabled = false;
        } catch (err) {
            alert('Gagal mengirim jawaban.');
            console.error(err);
        } finally {
            submitBtn.disabled = false;
        }
    });

    nextBtn.addEventListener('click', () => {
        loadQuestion();
    });

    loadQuestion();
});
</script>
@endpush

