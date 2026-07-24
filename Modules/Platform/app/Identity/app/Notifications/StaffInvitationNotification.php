<?php

declare(strict_types=1);

namespace Modules\Identity\app\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Modules\Identity\app\Models\StoreStaff;

final class StaffInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly StoreStaff $staff
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Generates a temporary signed URL valid for 7 days
        $acceptUrl = URL::temporarySignedRoute(
            'api.v1.staff.invitations.accept',
            now()->addDays(7),
            [
                'staff' => $this->staff->id,
                'token' => $this->staff->invitation_token,
            ]
        );

        return (new MailMessage)
            ->subject('Invitation to join store staff')
            ->greeting("Hello {$notifiable->name}!")
            ->line("You have been invited to join the staff for store ID: {$this->staff->store_id}.")
            ->action('Accept Invitation', $acceptUrl)
            ->line('This invitation link will expire in 7 days.');
    }
}