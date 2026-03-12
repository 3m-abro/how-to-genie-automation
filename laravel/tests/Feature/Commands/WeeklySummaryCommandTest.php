<?php

namespace Tests\Feature\Commands;

use App\Mail\WeeklySummaryMailable;
use App\Services\GoogleSheetsService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WeeklySummaryCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.weekly_summary.recipient' => 'owner@example.com']);
        config(['mail.from.address' => 'noreply@example.com']);
    }

    public function test_command_exists_and_runs_without_throwing(): void
    {
        $this->mockSheets([], []);

        Mail::fake();

        $this->artisan('weekly:summary')
            ->assertSuccessful();
    }

    public function test_command_sends_one_email_with_summary_data(): void
    {
        $contentLog = [
            ['date' => now()->format('Y-m-d'), 'title' => 'My Post', 'wp url' => 'https://blog.example.com/my-post'],
        ];
        $revenueTracker = [
            ['month' => now()->format('Y-m'), 'total' => '150.50'],
        ];

        $this->mockSheets($contentLog, $revenueTracker);
        Mail::fake();

        $this->artisan('weekly:summary')
            ->assertSuccessful();

        Mail::assertSent(WeeklySummaryMailable::class, function (WeeklySummaryMailable $mailable) {
            $summary = $mailable->summary;
            return $summary['posts_published'] >= 1
                && $summary['top_performer_title'] === 'My Post'
                && $summary['top_performer_url'] === 'https://blog.example.com/my-post'
                && (float) $summary['revenue_estimate'] === 150.5;
        });
        Mail::assertSentCount(1);
    }

    public function test_command_fails_when_no_recipient_configured(): void
    {
        config(['services.weekly_summary.recipient' => null]);
        config(['mail.from.address' => null]);
        $this->mockSheets([], []);

        Mail::fake();

        $this->artisan('weekly:summary')
            ->assertFailed();

        Mail::assertNothingSent();
    }

    private function mockSheets(array $contentLog, array $revenueTracker): void
    {
        $sheets = $this->createMock(GoogleSheetsService::class);
        $sheets->method('readContentLog')->willReturn($contentLog);
        $sheets->method('readRevenueTracker')->willReturn($revenueTracker);
        $this->instance(GoogleSheetsService::class, $sheets);
    }
}
