<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotifyProductDepletion extends Notification implements ShouldQueue
{
    use Queueable;

    public $afterCommit = true;
    public $title;
    public $description;
    public $url;
    public $depletions;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($title, $description, $url, $depletions = [])
    {
        //
        $this->title = $title;
        $this->description = $description;
        $this->url = $url;
        $this->depletions = $depletions;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $title = $this->title;
        $description = $this->description;
        $url = $this->url;
        $depletions = $this->depletions;
        $user = $notifiable;
        return (new MailMessage)->subject($title)->view(
            'emails.minimum_threshold',
            compact('title', 'description', 'url', 'user', 'depletions')
        );
        // return (new MailMessage)
        //     ->greeting('Hello!')
        //     ->line($this->title)
        //     ->line($this->description)
        //     ->action('Check it out', $this->url)
        //     ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
        ];
    }
}
