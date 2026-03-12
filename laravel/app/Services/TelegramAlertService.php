<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramAlertService
{
    /**
     * Send one message via Telegram Bot API (sendMessage).
     * Same channel as Phase 1 QC/publish_failed. No SDK required.
     *
     * @return bool true if sent successfully, false otherwise
     */
    public function sendMessage(string $message): bool
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $token || ! $chatId) {
            Log::warning('TelegramAlertService: TELEGRAM_BOT_TOKEN or TELEGRAM_CHAT_ID not set, skipping send.');
            return false;
        }

        $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';

        try {
            $response = Http::timeout(10)->post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if (! $response->successful()) {
                Log::error('TelegramAlertService: send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('TelegramAlertService: exception sending message', ['message' => $e->getMessage()]);
            return false;
        }
    }
}
