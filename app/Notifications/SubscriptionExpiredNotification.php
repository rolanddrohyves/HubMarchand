<?php

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiredNotification extends Notification
{
    public function __construct(public Store $store) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre abonnement HubMarchand a expiré')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('L’abonnement de votre boutique '.$this->store->name.' est arrivé à expiration.')
            ->line('Votre compte marchand est temporairement suspendu.')
            ->action('Voir les notifications', route('notifications.index'))
            ->line('Contactez HubMarchand pour renouveler votre abonnement.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Abonnement expiré',
            'message' => 'L’abonnement de votre boutique '.$this->store->name.' a expiré. Votre compte est suspendu jusqu’au renouvellement.',
            'store_id' => $this->store->id,
            'subscription_ends_at' => $this->store->subscription_ends_at?->toIso8601String(),
        ];
    }
}
