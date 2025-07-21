<?php

namespace Database\Seeders;

use App\Models\AuditQuestionTemplate;
use App\Services\QuestionTemplateService;
use Illuminate\Database\Seeder;

class AuditQuestionTemplatesSeeder extends Seeder
{
    public function run()
    {
        $service = new QuestionTemplateService(app(\App\Services\OpenAIService::class));
        
        // Seed initial templates for all roles
        $roles = array_keys(AuditQuestionTemplate::ROLES);
        $categories = array_keys(AuditQuestionTemplate::CATEGORIES);
        
        foreach ($roles as $role) {
            $this->command->info("Seeding templates for role: {$role}");
            $service->generateRoleSpecificQuestions($role, $categories, 3);
        }
        
        $this->command->info('Audit question templates seeded successfully!');
    }
}
