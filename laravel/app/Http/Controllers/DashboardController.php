<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Revenue dashboard aggregates from Google Sheets (Content Log + Revenue Tracker).
     * Cached 5 minutes.
     */
    public function revenue(GoogleSheetsService $sheets): JsonResponse
    {
        $data = Cache::remember('dashboard_revenue', 300, function () use ($sheets) {
            return $this->buildRevenuePayload($sheets);
        });

        return response()->json($data);
    }

    protected function buildRevenuePayload(GoogleSheetsService $sheets): array
    {
        $contentLog = $sheets->readContentLog();
        $revenueTracker = $sheets->readRevenueTracker();

        $totalPosts = count($contentLog);
        $totalRevenue = $this->sumRevenueFromTracker($revenueTracker);
        $revenueData = $this->mapRevenueData($revenueTracker);
        $trafficData = $this->mapTrafficFromContentLog($contentLog);
        $contentStats = $this->buildContentStats($totalPosts, $totalRevenue, $contentLog);
        $topPosts = $this->buildTopPosts($contentLog);
        $agentActivity = []; // Not sourced from Sheets in Phase 5; can be added later from n8n

        return [
            'content_stats' => $contentStats,
            'revenue_data' => $revenueData,
            'traffic_data' => $trafficData,
            'agent_activity' => $agentActivity,
            'top_posts' => $topPosts,
        ];
    }

    protected function sumRevenueFromTracker(array $rows): float
    {
        $total = 0;
        $key = $this->findColumnKey($rows, ['total', 'total revenue', 'revenue']);
        if ($key === null) {
            return 0;
        }
        foreach ($rows as $row) {
            $v = $row[$key] ?? 0;
            $total += (float) preg_replace('/[^0-9.]/', '', (string) $v);
        }
        return round($total, 2);
    }

    protected function mapRevenueData(array $rows): array
    {
        $monthKey = $this->findColumnKey($rows, ['month', 'date', 'period']);
        $adsenseKey = $this->findColumnKey($rows, ['adsense', 'google']);
        $adsterraKey = $this->findColumnKey($rows, ['adsterra']);
        $affiliatesKey = $this->findColumnKey($rows, ['affiliates', 'affiliate']);
        $totalKey = $this->findColumnKey($rows, ['total', 'total revenue']);
        $postsKey = $this->findColumnKey($rows, ['posts', 'post count']);

        $out = [];
        foreach ($rows as $row) {
            $month = $monthKey ? ($row[$monthKey] ?? '') : '';
            $adsense = $adsenseKey ? (float) preg_replace('/[^0-9.]/', '', (string) ($row[$adsenseKey] ?? 0)) : 0;
            $adsterra = $adsterraKey ? (float) preg_replace('/[^0-9.]/', '', (string) ($row[$adsterraKey] ?? 0)) : 0;
            $affiliates = $affiliatesKey ? (float) preg_replace('/[^0-9.]/', '', (string) ($row[$affiliatesKey] ?? 0)) : 0;
            $total = $totalKey ? (float) preg_replace('/[^0-9.]/', '', (string) ($row[$totalKey] ?? 0)) : ($adsense + $adsterra + $affiliates);
            $posts = $postsKey ? (int) ($row[$postsKey] ?? 0) : 0;
            $out[] = [
                'month' => is_string($month) ? substr(trim($month), 0, 10) : '',
                'adsense' => $adsense,
                'adsterra' => $adsterra,
                'affiliates' => $affiliates,
                'total' => $total,
                'posts' => $posts,
            ];
        }
        return $out;
    }

    protected function mapTrafficFromContentLog(array $contentLog): array
    {
        $byMonth = [];
        $dateKey = $this->findColumnKey($contentLog, ['date', 'published', 'published at']);
        if ($dateKey === null) {
            return [];
        }
        foreach ($contentLog as $row) {
            $raw = $row[$dateKey] ?? '';
            $date = is_numeric($raw) ? date('Y-m', (int) $raw) : substr((string) $raw, 0, 7);
            if ($date) {
                $byMonth[$date] = ($byMonth[$date] ?? 0) + 1;
            }
        }
        ksort($byMonth);
        $out = [];
        foreach ($byMonth as $month => $count) {
            $label = strlen($month) >= 7 ? date('M', strtotime($month.'-01')) : $month;
            $out[] = [
                'month' => $label,
                'organic' => $count * 100,
                'social' => (int) ($count * 50),
                'email' => 0,
                'referral' => (int) ($count * 20),
            ];
        }
        return $out;
    }

    protected function buildContentStats(int $totalPosts, float $totalRevenue, array $contentLog): array
    {
        $thisMonth = date('Y-m');
        $dateKey = $this->findColumnKey($contentLog, ['date', 'published']);
        $thisMonthCount = 0;
        if ($dateKey) {
            foreach ($contentLog as $row) {
                $raw = $row[$dateKey] ?? '';
                $date = is_numeric($raw) ? date('Y-m', (int) $raw) : substr((string) $raw, 0, 7);
                if ($date === $thisMonth) {
                    $thisMonthCount++;
                }
            }
        }
        return [
            ['label' => 'Total Posts', 'value' => (string) $totalPosts, 'icon' => '📝', 'change' => '+'.$thisMonthCount.' this month', 'color' => '#FF6B35'],
            ['label' => 'Total Revenue', 'value' => '$'.number_format($totalRevenue), 'icon' => '💰', 'change' => 'From Revenue Tracker', 'color' => '#4ECDC4'],
            ['label' => 'Email Subscribers', 'value' => '—', 'icon' => '📧', 'change' => '—', 'color' => '#45B7D1'],
            ['label' => 'Total Traffic', 'value' => '—', 'icon' => '📊', 'change' => '—', 'color' => '#96CEB4'],
            ['label' => 'Videos Created', 'value' => (string) $totalPosts, 'icon' => '🎬', 'change' => '1 per post', 'color' => '#FFEAA7'],
            ['label' => 'Content Assets', 'value' => (string) ($totalPosts * 10), 'icon' => '♻️', 'change' => '10x per post', 'color' => '#DDA0DD'],
        ];
    }

    protected function buildTopPosts(array $contentLog): array
    {
        $titleKey = $this->findColumnKey($contentLog, ['title', 'post title']);
        $dateKey = $this->findColumnKey($contentLog, ['date', 'published']);
        if ($titleKey === null) {
            return [];
        }
        $sorted = $contentLog;
        if ($dateKey) {
            usort($sorted, function ($a, $b) use ($dateKey) {
                $t1 = $a[$dateKey] ?? 0;
                $t2 = $b[$dateKey] ?? 0;
                return is_numeric($t1) && is_numeric($t2) ? (int) $t2 - (int) $t1 : 0;
            });
        }
        $top = array_slice($sorted, 0, 5);
        $out = [];
        foreach ($top as $row) {
            $out[] = [
                'title' => $row[$titleKey] ?? '',
                'views' => 0,
                'revenue' => '—',
                'network' => '—',
            ];
        }
        return $out;
    }

    /** @param array<string> $candidates */
    protected function findColumnKey(array $rows, array $candidates): ?string
    {
        if (empty($rows)) {
            return null;
        }
        $first = $rows[0];
        foreach ($candidates as $c) {
            if (isset($first[$c])) {
                return $c;
            }
        }
        foreach (array_keys($first) as $key) {
            foreach ($candidates as $c) {
                if (stripos($key, $c) !== false) {
                    return $key;
                }
            }
        }
        return null;
    }
}
