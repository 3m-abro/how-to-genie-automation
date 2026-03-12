<?php

namespace App\Http\Controllers;

use App\Models\ContentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MissionControlController extends Controller
{
    private function n8nBaseUrl(): string
    {
        return config('services.n8n.base_url', 'http://localhost:5678');
    }

    public function dashboard()
    {
        $data = $this->getMissionControlData();
        return view('mission-control.dashboard', $data);
    }

    /** Return mission control data (cached); used by dashboard view and API. */
    public function getMissionControlData(): array
    {
        return Cache::remember('mission_control_data', 300, function () {
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
    }

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

    public function quickAction(Request $request, $action)
    {
        switch ($action) {
            case 'run-pipeline-now':
                return $this->triggerN8nWorkflow('main-blog-pipeline');
            case 'check-all-status':
                Cache::forget('mission_control_data');
                return response()->json(['message' => 'Status refreshed', 'status' => 'success']);
            case 'approve-ab-test':
                return $this->approveABTests();
            case 'boost-viral-post':
                return $this->boostTopPost();
            default:
                return response()->json(['error' => 'Unknown action'], 400);
        }
    }

    /** Public for API use when DB may be unavailable (e.g. tests without sqlite). */
    public function getSystemStatus(): array
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

        $allGreen = collect($modules)->every(fn ($status) => $status['status'] === 'running');

        return [
            'overall' => $allGreen ? 'all_green' : 'needs_attention',
            'modules' => $modules,
            'last_check' => now()->toDateTimeString(),
        ];
    }

    private function checkN8nWorkflow(string $workflowName): array
    {
        try {
            $url = $this->n8nBaseUrl() . '/api/v1/workflows';
            $request = Http::timeout(3);
            if (config('services.n8n.api_key')) {
                $request = $request->withHeaders(['X-N8N-API-KEY' => config('services.n8n.api_key')]);
            }
            $request = $request->get($url);
            if ($request->successful()) {
                $workflow = collect($request->json('data', []))->firstWhere('name', $workflowName);
                if ($workflow) {
                    return [
                        'status' => $workflow['active'] ?? false ? 'running' : 'stopped',
                        'last_run' => $workflow['updatedAt'] ?? 'unknown',
                        'next_run' => 'Scheduled',
                    ];
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return ['status' => 'unknown', 'last_run' => 'N/A', 'next_run' => 'N/A'];
    }

    private function getTodayProgress(): int
    {
        $tasksCompleted = ContentLog::whereDate('created_at', today())->count();
        $tasksExpected = 9;
        return (int) min(100, round(($tasksCompleted / $tasksExpected) * 100));
    }

    private function getWeeklyWins(): array
    {
        $startOfWeek = now()->startOfWeek();
        $count = ContentLog::where('created_at', '>=', $startOfWeek)->count();
        return [
            ['icon' => '📝', 'text' => $count . ' posts published this week', 'points' => $count * 10],
            ['icon' => '💰', 'text' => 'Revenue: $' . $this->getWeeklyRevenue(), 'points' => (int) $this->getWeeklyRevenue()],
            ['icon' => '🔥', 'text' => $this->getStreak() . '-day streak maintained!', 'points' => $this->getStreak() * 10],
            ['icon' => '📈', 'text' => number_format($this->getWeeklyViews()) . ' page views', 'points' => (int) ($this->getWeeklyViews() / 100)],
        ];
    }

    private function getPriorities(): array
    {
        $priorities = [];
        $systemStatus = $this->getSystemStatus();
        if ($systemStatus['overall'] === 'all_green') {
            $priorities[] = ['title' => '✅ Everything is Running Perfectly', 'description' => 'All systems operational.', 'urgency' => 'none', 'time_estimate' => '0 min', 'action' => 'Just relax'];
        } else {
            $priorities[] = ['title' => '⚠️ System Needs Attention', 'description' => 'One or more modules require a quick check.', 'urgency' => 'high', 'time_estimate' => '5 min', 'action' => 'Check status'];
        }
        if (now()->isWeekend()) {
            $priorities[] = ['title' => '📊 Weekly Review Time', 'description' => 'Review analytics, approve A/B tests.', 'urgency' => 'low', 'time_estimate' => '30 min', 'action' => 'Open weekly dashboard'];
        }
        return collect($priorities)->take(3)->values()->all();
    }

    private function getStreak(): int
    {
        $streak = 0;
        $date = now();
        while (ContentLog::whereDate('created_at', $date)->exists()) {
            $streak++;
            $date = $date->subDay();
            if ($streak > 365) {
                break;
            }
        }
        return $streak;
    }

    private function triggerN8nWorkflow(string $workflowName)
    {
        try {
            $response = Http::post($this->n8nBaseUrl() . '/webhook/' . $workflowName);
            return response()->json(['message' => 'Workflow triggered', 'status' => 'success', 'workflow' => $workflowName]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to trigger workflow', 'status' => 'error', 'error' => $e->getMessage()], 500);
        }
    }

    private function getWeeklyViews(): int
    {
        return 0;
    }

    private function getWeeklyRevenue(): float
    {
        return 0.0;
    }

    private function getRevenueGrowth(): int
    {
        return 0;
    }

    private function getTopPost(): ?string
    {
        return null;
    }

    private function getActionItems(): array
    {
        return [];
    }

    private function getCelebrationMoment(): string
    {
        return '';
    }

    private function approveABTests()
    {
        return response()->json(['message' => 'A/B tests approved']);
    }

    private function boostTopPost()
    {
        return response()->json(['message' => 'Viral boost activated']);
    }

    private function getQuickStats(): array
    {
        return [
            'total_posts' => ContentLog::count(),
            'total_revenue' => '$0',
            'subscribers' => 0,
            'this_month_views' => '0',
        ];
    }

    private function getNextActions(): array
    {
        return [
            'next_post' => 'Tomorrow 8:00 AM',
            'next_email' => 'Tuesday 9:00 AM',
            'next_review' => 'Saturday morning',
        ];
    }
}
