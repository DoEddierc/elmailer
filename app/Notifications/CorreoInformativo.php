<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorreoInformativo extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $subject, public string $body) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = (new MailMessage)->subject($this->subject);

        foreach (preg_split("/\r\n|\n|\r/", $this->body) as $line) {
            if (trim($line) !== '') {
                $m->line($line);
            }
        }

        return $m->salutation('— Sistema de Notificaciones');
    }
}
