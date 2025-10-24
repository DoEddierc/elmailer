<?php

namespace App\Jobs;

use App\Notifications\CorreoInformativo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;   // <--- IMPORTANTE
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendOneEmailJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels; // <--- AGREGA Dispatchable

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public string $to,
        public string $subject,
        public string $body,
        public ?string $campaignId = null
    ) {}

    public function handle(): void
    {
        if (!filter_var($this->to, FILTER_VALIDATE_EMAIL)) return;

        Notification::route('mail', $this->to)
            ->notify(new CorreoInformativo($this->subject, $this->body));

        if ($this->campaignId) {
            cache()->increment("campaign:{$this->campaignId}:sent");
        }
    }
}
