<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('audit-session.{sessionId}', function ($user, $sessionId) {
    return App\Models\AuditSession::where('id', $sessionId)
        ->where('auditor_id', $user->id)
        ->exists();
});

// config/openai.php
return [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4-turbo-preview'),
    'max_tokens' => env('OPENAI_MAX_TOKENS', 1000),
    'temperature' => env('OPENAI_TEMPERATURE', 0.7),
    'timeout' => env('OPENAI_TIMEOUT', 30),
    'system_prompt' => env('OPENAI_SYSTEM_PROMPT', 'You are an AI assistant helping with employee audit interviews. Provide professional, constructive feedback and fair scoring based on the responses given.'),
];