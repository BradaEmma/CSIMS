<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class RosterShortageNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $startDate,
        private string $endDate,
        private array $shortages,
        private int $rosterGenerationLogId
    ) {}

    public function via(object $notifiable): array
    {
        // Database now; adding 'mail' here later is the only change needed
        // to also send email, once a mail provider is configured.
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Roster shortages need attention',
            'message' => sprintf(
                'Roster generated for %s to %s has %d unfilled shift(s).',
                $this->startDate,
                $this->endDate,
                count($this->shortages)
            ),
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'shortage_count' => count($this->shortages),
            'shortages' => $this->shortages,
            'roster_generation_log_id' => $this->rosterGenerationLogId,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('CSIMS: Roster shortages need attention')
            ->line(sprintf(
                'Roster generated for %s to %s has %d unfilled shift(s).',
                $this->startDate,
                $this->endDate,
                count($this->shortages)
            ))
            ->line('Please review and cover these shifts as soon as possible.');
    }
}