<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Invitation $invitation,
        public string $plainToken,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('invitations.show', ['token' => $this->plainToken]);

        return (new MailMessage)
            ->subject('You have been invited to '.$this->invitation->farm->name.' on Farmers')
            ->greeting('You have been invited to Farmers')
            ->line($this->invitation->inviter->name.' invited you to join '.$this->invitation->farm->name.'.')
            ->line('Role: '.($this->invitation->role?->name ?? 'Staff'))
            ->action('Accept Invitation', $url)
            ->line('This invitation expires on '.$this->invitation->expires_at->format('d M Y H:i').'.')
            ->line('If you were not expecting this invitation, you can ignore this email.');
    }
}
