<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffLoginCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $schoolName,
        public string $schoolSlug,
        public string $role,
        public string $email,
        public string $plainPassword
    ) {
        // Ensure notification is queued only after DB transaction commits.
        $this->afterCommit();
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = route('login', ['school' => $this->schoolSlug]);

        return (new MailMessage)
            ->subject('Your Staff Portal Login Details')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You have been granted access to the school portal.')
            ->line('School: '.$this->schoolName)
            ->line('Assigned Role: '.$this->role)
            ->line('Login Email: '.$this->email)
            ->line('Temporary Password: '.$this->plainPassword)
            ->line('Please sign in and change your password immediately.')
            ->action('Sign In To School Portal', $loginUrl)
            ->line('If you did not expect this access, contact your principal.');
    }
}