<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    public function __construct(
        protected ?string $sheetId = null,
        protected ?string $contentLogTab = null,
        protected ?string $revenueTrackerTab = null,
        protected ?string $credentialsPath = null
    ) {
        $this->sheetId = $sheetId ?? config('services.google.sheet_id');
        $this->contentLogTab = $contentLogTab ?? config('services.google.content_log_tab', 'Content Log');
        $this->revenueTrackerTab = $revenueTrackerTab ?? config('services.google.revenue_tracker_tab', 'Revenue Tracker');
        $this->credentialsPath = $credentialsPath ?? config('services.google.credentials');
    }

    /**
     * Read a range from the configured spreadsheet.
     * Returns array of rows as associative arrays with normalized (lowercase) header keys.
     * Returns [] if credentials/sheet not configured or on error.
     */
    public function readRange(string $range): array
    {
        if (! $this->sheetId || ! $this->credentialsPath) {
            return [];
        }

        try {
            $client = $this->makeClient();
            $service = new Sheets($client);
            $response = $service->spreadsheets_values->get($this->sheetId, $range);
            $values = $response->getValues();
            if (empty($values)) {
                return [];
            }
            $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $values[0]);
            $rows = [];
            for ($i = 1; $i < count($values); $i++) {
                $row = [];
                foreach ($headers as $j => $key) {
                    $row[$key] = $values[$i][$j] ?? '';
                }
                $rows[] = $row;
            }
            return $rows;
        } catch (\Throwable $e) {
            Log::warning('GoogleSheetsService readRange failed: '.$e->getMessage());
            return [];
        }
    }

    /**
     * Read Content Log tab (full columns).
     */
    public function readContentLog(): array
    {
        $range = "'".str_replace("'", "''", $this->contentLogTab)."'!A:Z";
        return $this->readRange($range);
    }

    /**
     * Read Revenue Tracker tab (full columns).
     */
    public function readRevenueTracker(): array
    {
        $range = "'".str_replace("'", "''", $this->revenueTrackerTab)."'!A:Z";
        return $this->readRange($range);
    }

    protected function makeClient(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setApplicationName('HowTo-Genie Laravel');
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);

        $credentialsPath = $this->credentialsPath;
        if (is_file($credentialsPath)) {
            $client->setAuthConfig($credentialsPath);
        } else {
            $decoded = json_decode($credentialsPath, true);
            if (is_array($decoded)) {
                $client->setAuthConfig($decoded);
            } else {
                throw new \InvalidArgumentException('Google credentials not configured (file path or JSON).');
            }
        }

        return $client;
    }
}
