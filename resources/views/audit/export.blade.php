<!DOCTYPE html>
<html>
<head>
    <title>Audit Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        h2, h4 { margin: 0; }
    </style>
</head>
<body>
    <h2>Audit Report</h2>
    <h4>Session: {{ $session->session_code }}</h4>
    <p>Auditor: {{ $session->auditor->name }}</p>
    <p>Audited: {{ $session->auditedUser->name }}</p>
    <p>Date: {{ $session->created_at->format('d M Y') }}</p>

    <h4>Audit Logs</h4>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Pertanyaan</th>
                <th>Jawaban</th>
                <th>Skor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($session->auditLogs as $log)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $log->pertanyaan }}</td>
                    <td>{{ $log->jawaban }}</td>
                    <td>{{ $log->skor_jawaban }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h4>Ringkasan AI:</h4>
    <p>{{ $session->catatan_ai }}</p>
</body>
</html>
