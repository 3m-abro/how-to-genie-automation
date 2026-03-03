<?php

/**
 * HowTo-Genie ADHD-Friendly Mission Control
 * Laravel Admin Panel - Complete Implementation
 * 
 * Installation:
 * 1. composer require laravel/breeze --dev
 * 2. php artisan breeze:install blade
 * 3. npm install && npm run build
 * 4. php artisan migrate
 */

// ============================================
// FILE: routes/web.php
// ============================================

use App\Http\Controllers\MissionControlController;
use App\Http\Controllers\N8nWebhookController;

Route::middleware(['auth'])->group(function () {
    // Main Dashboard (ADHD-optimized single view)
    Route::get('/mission-control', [MissionControlController::class, 'dashboard'])->name('mission.control');
    
    // Weekly Summary (Weekend review)
    Route::get('/weekly-summary', [MissionControlController::class, 'weeklySummary'])->name('weekly.summary');
    
    // Quick Actions API (AJAX calls)
    Route::post('/api/quick-action/{action}', [MissionControlController::class, 'quickAction'])->name('api.quick.action');
    
    // n8n Integration
    Route::post('/api/n8n/trigger/{workflow}', [N8nWebhookController::class, 'trigger'])->name('api.n8n.trigger');
    Route::get('/api/n8n/status', [N8nWebhookController::class, 'status'])->name('api.n8n.status');
});

// ============================================
// FILE: app/Http/Controllers/MissionControlController.php
// ============================================

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\ContentLog;
use App\Models\SystemStatus;

class MissionControlController extends Controller
{
    private $n8nBaseUrl = 'http://localhost:5678'; // Your n8n instance
    
    /**
     * ADHD-Optimized Dashboard
     * Everything in one view, minimal clicks
     */
    public function dashboard()
    {
        // Cache for 5 minutes to avoid overwhelming ADHD brain with constant changes
        $data = Cache::remember('mission_control_data', 300, function () {
            return [
                'system_status' => $this->getSystemStatus(),
                'today_progress' => $this->getTodayProgress(),
                'weekly_wins' => $this->getWeeklyWins(),
                'priorities' => $this->getPriorities(),
                'streak' => $this->getStreak(),
                'next_actions' => $this->getNextActions(),
                'quick_stats' => $this->getQuickStats(),
            ];
        });
        
        return view('mission-control.dashboard', $data);
    }
    
    /**
     * Weekly Summary (Only check on weekends)
     */
    public function weeklySummary()
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        
        $summary = [
            'posts_published' => ContentLog::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count(),
            'total_views' => $this->getWeeklyViews(),
            'revenue' => $this->getWeeklyRevenue(),
            'top_post' => $this->getTopPost(),
            'action_items' => $this->getActionItems(),
            'celebration_moment' => $this->getCelebrationMoment(),
        ];
        
        return view('mission-control.weekly-summary', $summary);
    }
    
    /**
     * Quick Actions (One-click operations)
     */
    public function quickAction(Request $request, $action)
    {
        switch ($action) {
            case 'run-pipeline-now':
                return $this->triggerN8nWorkflow('main-blog-pipeline');
                
            case 'check-all-status':
                Cache::forget('mission_control_data');
                return response()->json(['message' => 'Status refreshed', 'status' => 'success']);
                
            case 'approve-ab-test':
                // Auto-approve winning A/B test variants
                return $this->approveABTests();
                
            case 'boost-viral-post':
                // Trigger viral amplification for top post
                return $this->boostTopPost();
                
            default:
                return response()->json(['error' => 'Unknown action'], 400);
        }
    }
    
    /**
     * Get System Status (All 13 modules)
     */
    private function getSystemStatus()
    {
        $modules = [
            'blog_pipeline' => $this->checkN8nWorkflow('main-blog-pipeline'),
            'video_creation' => $this->checkN8nWorkflow('video-creation-pipeline'),
            'translations' => $this->checkN8nWorkflow('multi-language-pipeline'),
            'social_media' => $this->checkN8nWorkflow('social-distribution'),
            'email_campaigns' => $this->checkN8nWorkflow('email-newsletter'),
            'audio_podcast' => $this->checkN8nWorkflow('audio-pipeline'),
            'seo_linking' => $this->checkN8nWorkflow('seo-interlinking'),
            'viral_amplifier' => $this->checkN8nWorkflow('viral-amplifier'),
            'competitor_monitor' => $this->checkN8nWorkflow('competitor-monitor'),
            'ab_testing' => $this->checkN8nWorkflow('ab-testing'),
            'messaging_apps' => $this->checkN8nWorkflow('messaging-distribution'),
            'islamic_content' => $this->checkN8nWorkflow('islamic-content-pipeline'),
        ];
        
        $allGreen = collect($modules)->every(fn($status) => $status['status'] === 'running');
        
        return [
            'overall' => $allGreen ? 'all_green' : 'needs_attention',
            'modules' => $modules,
            'last_check' => now()->toDateTimeString(),
        ];
    }
    
    /**
     * Check n8n Workflow Status
     */
    private function checkN8nWorkflow($workflowName)
    {
        try {
            // Call n8n API to check workflow status
            $response = Http::timeout(3)->get("{$this->n8nBaseUrl}/api/v1/workflows");
            
            if ($response->successful()) {
                $workflow = collect($response->json('data'))
                    ->firstWhere('name', $workflowName);
                
                return [
                    'status' => $workflow['active'] ? 'running' : 'stopped',
                    'last_run' => $workflow['updatedAt'] ?? 'unknown',
                    'next_run' => 'Scheduled',
                ];
            }
        } catch (\Exception $e) {
            \Log::error("n8n check failed for {$workflowName}: " . $e->getMessage());
        }
        
        return ['status' => 'unknown', 'last_run' => 'N/A', 'next_run' => 'N/A'];
    }
    
    /**
     * Get Today's Progress
     */
    private function getTodayProgress()
    {
        $tasksCompleted = ContentLog::whereDate('created_at', today())->count();
        $tasksExpected = 9; // 1 post in English + 8 translations
        
        return min(100, round(($tasksCompleted / $tasksExpected) * 100));
    }
    
    /**
     * Get Weekly Wins (Gamification for ADHD)
     */
    private function getWeeklyWins()
    {
        $startOfWeek = now()->startOfWeek();
        
        return [
            [
                'icon' => '📝',
                'text' => ContentLog::where('created_at', '>=', $startOfWeek)->count() . ' posts published this week',
                'points' => ContentLog::where('created_at', '>=', $startOfWeek)->count() * 10,
            ],
            [
                'icon' => '💰',
                'text' => 'Revenue: $' . $this->getWeeklyRevenue() . ' (+' . $this->getRevenueGrowth() . '%)',
                'points' => (int)$this->getWeeklyRevenue(),
            ],
            [
                'icon' => '🔥',
                'text' => $this->getStreak() . '-day streak maintained!',
                'points' => $this->getStreak() * 10,
            ],
            [
                'icon' => '📈',
                'text' => number_format($this->getWeeklyViews()) . ' page views',
                'points' => (int)($this->getWeeklyViews() / 100),
            ],
        ];
    }
    
    /**
     * Get Priorities (ADHD-friendly: max 3 items)
     */
    private function getPriorities()
    {
        $priorities = [];
        
        // Priority 1: System Check (always show)
        $systemStatus = $this->getSystemStatus();
        if ($systemStatus['overall'] === 'all_green') {
            $priorities[] = [
                'title' => '✅ Everything is Running Perfectly',
                'description' => 'All systems operational. No action needed.',
                'urgency' => 'none',
                'time_estimate' => '0 min',
                'action' => 'Just relax',
            ];
        } else {
            $priorities[] = [
                'title' => '⚠️ System Needs Attention',
                'description' => 'One or more modules require a quick check.',
                'urgency' => 'high',
                'time_estimate' => '5 min',
                'action' => 'Check status',
            ];
        }
        
        // Priority 2: Weekend Review (only show on Sat/Sun)
        if (now()->isWeekend()) {
            $priorities[] = [
                'title' => '📊 Weekly Review Time',
                'description' => 'Review analytics, approve A/B tests, celebrate wins.',
                'urgency' => 'low',
                'time_estimate' => '30 min',
                'action' => 'Open weekly dashboard',
            ];
        }
        
        // Priority 3: Optional Content Review
        $queuedIdeas = 47; // From Google Sheets
        if ($queuedIdeas > 40) {
            $priorities[] = [
                'title' => '💡 Content Ideas Waiting',
                'description' => $queuedIdeas . ' AI-generated ideas. Review if you feel like it.',
                'urgency' => 'none',
                'time_estimate' => '10 min',
                'action' => 'Browse ideas',
            ];
        }
        
        return collect($priorities)->take(3); // Max 3 to avoid overwhelming
    }
    
    /**
     * Get Current Streak (Gamification)
     */
    private function getStreak()
    {
        $streak = 0;
        $date = now();
        
        while (ContentLog::whereDate('created_at', $date)->exists()) {
            $streak++;
            $date = $date->subDay();
            
            if ($streak > 365) break; // Safety limit
        }
        
        return $streak;
    }
    
    /**
     * Trigger n8n Workflow
     */
    private function triggerN8nWorkflow($workflowName)
    {
        try {
            $response = Http::post("{$this->n8nBaseUrl}/webhook/{$workflowName}");
            
            return response()->json([
                'message' => 'Workflow triggered successfully',
                'status' => 'success',
                'workflow' => $workflowName,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to trigger workflow',
                'status' => 'error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    // Helper methods (implement based on your data sources)
    private function getWeeklyViews() { return rand(100000, 200000); } // Replace with GA4 API
    private function getWeeklyRevenue() { return rand(300, 800); } // Replace with actual revenue tracking
    private function getRevenueGrowth() { return rand(15, 45); }
    private function getTopPost() { return 'How to Start Affiliate Marketing'; }
    private function getActionItems() { return []; }
    private function getCelebrationMoment() { return 'You published 63 posts this week! 🎉'; }
    private function approveABTests() { return response()->json(['message' => 'A/B tests approved']); }
    private function boostTopPost() { return response()->json(['message' => 'Viral boost activated']); }
    private function getQuickStats() {
        return [
            'total_posts' => ContentLog::count(),
            'total_revenue' => '$5,234',
            'subscribers' => 2847,
            'this_month_views' => '145K',
        ];
    }
    private function getNextActions() {
        return [
            'next_post' => 'Tomorrow 8:00 AM',
            'next_email' => 'Tuesday 9:00 AM',
            'next_review' => 'Saturday morning',
        ];
    }
}

// ============================================
// FILE: app/Models/ContentLog.php
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentLog extends Model
{
    protected $fillable = [
        'title',
        'url',
        'keyword',
        'language',
        'status',
        'views',
        'revenue',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

// ============================================
// FILE: database/migrations/2025_01_01_000001_create_content_logs_table.php
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('content_logs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url');
            $table->string('keyword')->nullable();
            $table->string('language')->default('en');
            $table->enum('status', ['published', 'draft', 'scheduled'])->default('published');
            $table->integer('views')->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->timestamps();
            
            $table->index('created_at');
            $table->index('status');
            $table->index('language');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('content_logs');
    }
};

// ============================================
// FILE: resources/views/mission-control/dashboard.blade.php
// ============================================

/*
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧞‍♂️ Mission Control - HowTo-Genie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#667eea',
                        secondary: '#764ba2',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-purple-900 via-purple-800 to-indigo-900 min-h-screen text-white font-sans p-6">

    <!-- Header -->
    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 mb-6 border border-white/20">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold mb-1">🧞‍♂️ HowTo-Genie Mission Control</h1>
                <p class="text-sm opacity-90">ADHD-Optimized • Everything Automated • Zero Stress</p>
            </div>
            <div class="bg-white/20 rounded-xl p-4">
                <div class="text-2xl font-bold">{{ $streak }} Days</div>
                <div class="text-xs opacity-90">Streak 🔥</div>
            </div>
        </div>
    </div>

    <!-- Big Status Indicator -->
    <div class="@if($system_status['overall'] === 'all_green') bg-gradient-to-r from-green-500 to-green-600 @else bg-gradient-to-r from-yellow-500 to-orange-500 @endif rounded-2xl p-8 mb-6 text-center">
        <div class="text-6xl mb-3">@if($system_status['overall'] === 'all_green') ✅ @else ⚠️ @endif</div>
        <h2 class="text-3xl font-bold mb-2">
            @if($system_status['overall'] === 'all_green')
                Everything is Working Perfectly
            @else
                Needs Your Attention
            @endif
        </h2>
        <p class="opacity-90">
            @if($system_status['overall'] === 'all_green')
                All systems operational. You can relax. Check back next week.
            @else
                A few items need a quick look
            @endif
        </p>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white/10 backdrop-blur-lg rounded-xl p-4 border border-white/20">
            <div class="text-2xl font-bold text-green-400">{{ $quick_stats['total_posts'] }}</div>
            <div class="text-sm opacity-80">Total Posts</div>
        </div>
        <div class="bg-white/10 backdrop-blur-lg rounded-xl p-4 border border-white/20">
            <div class="text-2xl font-bold text-blue-400">{{ $quick_stats['total_revenue'] }}</div>
            <div class="text-sm opacity-80">Total Revenue</div>
        </div>
        <div class="bg-white/10 backdrop-blur-lg rounded-xl p-4 border border-white/20">
            <div class="text-2xl font-bold text-purple-400">{{ $quick_stats['subscribers'] }}</div>
            <div class="text-sm opacity-80">Subscribers</div>
        </div>
        <div class="bg-white/10 backdrop-blur-lg rounded-xl p-4 border border-white/20">
            <div class="text-2xl font-bold text-pink-400">{{ $quick_stats['this_month_views'] }}</div>
            <div class="text-sm opacity-80">This Month Views</div>
        </div>
    </div>

    <!-- Priorities -->
    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 mb-6 border border-white/20">
        <h3 class="text-xl font-bold mb-4">🎯 What To Do Next</h3>
        @foreach($priorities as $priority)
        <div class="bg-white/10 rounded-xl p-5 mb-4 @if($priority['urgency'] === 'high') border-2 border-yellow-500 @else border border-white/20 @endif">
            <div class="flex justify-between items-start mb-2">
                <h4 class="font-semibold">{{ $priority['title'] }}</h4>
                <span class="text-xs bg-white/20 px-3 py-1 rounded-full">{{ $priority['time_estimate'] }}</span>
            </div>
            <p class="text-sm opacity-90 mb-3">{{ $priority['description'] }}</p>
            <button class="bg-gradient-to-r from-green-500 to-green-600 px-4 py-2 rounded-lg text-sm font-semibold hover:from-green-600 hover:to-green-700 transition">
                {{ $priority['action'] }}
            </button>
        </div>
        @endforeach
    </div>

    <!-- Weekly Wins -->
    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 mb-6 border border-white/20">
        <h3 class="text-xl font-bold mb-4">🏆 This Week's Wins</h3>
        <div class="grid grid-cols-2 gap-3">
            @foreach($weekly_wins as $win)
            <div class="bg-white/10 rounded-xl p-4 flex items-center gap-3">
                <span class="text-3xl">{{ $win['icon'] }}</span>
                <div class="flex-1">
                    <p class="text-sm font-semibold">{{ $win['text'] }}</p>
                    <p class="text-xs opacity-70">+{{ $win['points'] }} XP</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- ADHD Reminder -->
    <div class="bg-white/5 backdrop-blur-lg rounded-xl p-5 border border-white/20">
        <p class="text-sm opacity-90">
            💡 <strong>ADHD Tip:</strong> The system runs itself. Check this dashboard once per week on weekends. No daily tasks. No guilt. No overwhelm.
        </p>
    </div>

</body>
</html>
*/
