<?php

namespace App\Console\Commands;

use App\Mail\WeeklySummaryMailable;
use App\Services\GoogleSheetsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class WeeklySummaryCommand extends Command
{
    protected $signature = 'weekly:summary';

    protected $description = 'Generate and send weekly summary email (posts published, top performer, revenue estimate)';

    public function handle(GoogleSheetsService $sheets): int
    {
        $contentLog = $sheets->readContentLog();
        $revenueTracker = $sheets->readRevenueTracker();

        $summary = $this->buildSummary($contentLog, $revenueTracker);

        $recipient = config('services.weekly_summary.recipient') ?: config('mail.from.address');
        if (empty($recipient)) {
            $this->error('No weekly summary recipient configured. Set WEEKLY_SUMMARY_RECIPIENT or MAIL_FROM_ADDRESS.');

            return self::FAILURE;
        }

        Mail::to($recipient)->send(new WeeklySummaryMailable($summary));
        $this->info('Weekly summary sent to '.$recipient);

        return self::SUCCESS;
    }

    /**
     * @param array<int, array<string, mixed>> $contentLog
     * @param array<int, array<string, mixed>> $revenueTracker
     * @return array{posts_published: int, top_performer_title: string, top_performer_url: string, revenue_estimate: float, streak: string|null}
     */
    protected function buildSummary(array $contentLog, array $revenueTracker): array
    {
        $cutoff = now()->subDays(7)->startOfDay();
        $dateKey = $this->findColumnKey($contentLog, ['date', 'published', 'published at']);
        $titleKey = $this->findColumnKey($contentLog, ['title', 'post title']);
        $urlKey = $this->findColumnKey($contentLog, ['wp url', 'url', 'link']);

        $thisWeek = [];
        foreach ($contentLog as $row) {
            $raw = $row[$dateKey] ?? '';
            $ts = $this->parseDateToTimestamp($raw);
            if ($ts !== null && $ts >= $cutoff->timestamp) {
                $thisWeek[] = $row;
            }
        }

        $postsPublished = count($thisWeek);

        $topPerformerTitle = '';
        $topPerformerUrl = '';
        if (! empty($thisWeek) && $titleKey !== null) {
            usort($thisWeek, function ($a, $b) use ($dateKey) {
                $t1 = $this->parseDateToTimestamp($a[$dateKey] ?? null);
                $t2 = $this->parseDateToTimestamp($b[$dateKey] ?? null);
                return ($t2 ?? 0) <=> ($t1 ?? 0);
            });
            $top = $thisWeek[0];
            $topPerformerTitle = trim((string) ($top[$titleKey] ?? ''));
            $topPerformerUrl = trim((string) ($top[$urlKey] ?? ''));
        }

        $revenueKey = $this->findColumnKey($revenueTracker, ['total', 'total revenue', 'revenue']);
        $revenueEstimate = 0.0;
        if ($revenueKey !== null) {
            foreach ($revenueTracker as $row) {
                $v = $row[$revenueKey] ?? 0;
                $revenueEstimate += (float) preg_replace('/[^0-9.]/', '', (string) $v);
            }
            $revenueEstimate = round($revenueEstimate, 2);
        }

        $streak = $postsPublished >= 7 ? '7-day streak!' : null;

        return [
            'posts_published' => $postsPublished,
            'top_performer_title' => $topPerformerTitle,
            'top_performer_url' => $topPerformerUrl,
            'revenue_estimate' => $revenueEstimate,
            'streak' => $streak,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $candidates
     */
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

    private function parseDateToTimestamp(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return (int) $raw;
        }
        $ts = strtotime((string) $raw);

        return $ts !== false ? $ts : null;
    }
}
