<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditQuestionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'category',
        'question_text',
        'question_type',
        'max_score',
        'difficulty_level',
        'options',
        'guidelines',
        'expected_response',
        'is_active',
        'order_index'
    ];

    protected $casts = [
        'options' => 'array',
        'max_score' => 'decimal:2',
        'is_active' => 'boolean',
        'order_index' => 'integer'
    ];

    // Constants for roles
    const ROLES = [
        'CEO' => 'CEO',
        'CBO' => 'CBO',
        'Manager' => 'Manager',
        'SBC' => 'Senior Branch Coordinator',
        'BC' => 'Branch Coordinator',
        'Trainee' => 'Trainee'
    ];

    // Constants for categories
    const CATEGORIES = [
        'leadership' => 'Leadership',
        'teamwork' => 'Teamwork',
        'recruitment' => 'Recruitment',
        'effectiveness' => 'Effectiveness',
        'innovation' => 'Innovation',
        'compliance' => 'Compliance',
        'customer_service' => 'Customer Service'
    ];

    // Constants for question types
    const QUESTION_TYPES = [
        'open-ended' => 'Open Ended',
        'multiple-choice' => 'Multiple Choice',
        'rating' => 'Rating Scale',
        'yes-no' => 'Yes/No'
    ];

    // Constants for difficulty levels
    const DIFFICULTY_LEVELS = [
        'basic' => 'Basic',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced'
    ];

    /**
     * Scope for active questions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific role
     */
    public function scopeForRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope for specific category
     */
    public function scopeForCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get questions ordered by index
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index')->orderBy('created_at');
    }

    /**
     * Get questions for a specific role and category
     */
    public static function getQuestionsForRoleAndCategory($role, $category = null)
    {
        $query = self::active()->forRole($role)->ordered();
        
        if ($category) {
            $query->forCategory($category);
        }
        
        return $query->get();
    }

    /**
     * Get all categories for a role
     */
    public static function getCategoriesForRole($role)
    {
        return self::active()
            ->forRole($role)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->toArray();
    }

    /**
     * Get questions count by role and category
     */
    public static function getQuestionsCount($role = null)
    {
        $query = self::active();
        
        if ($role) {
            $query->forRole($role);
        }
        
        return $query->select('role', 'category')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('role', 'category')
            ->get();
    }
}
