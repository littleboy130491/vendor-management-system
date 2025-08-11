<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorRegistrationReceived extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Vendor $vendor
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vendor Registration Confirmation')
            ->greeting('Hello ' . $this->vendor->contact_name . '!')
            ->line('Thank you for registering ' . $this->vendor->company_name . ' as a vendor with our organization.')
            ->line('We have received your registration and our procurement team will review your application.')
            ->line('**Registration Details:**')
            ->line('Company: ' . $this->vendor->company_name)
            ->line('Category: ' . $this->vendor->category->name)
            ->line('Contact Email: ' . $this->vendor->contact_email)
            ->line('**What happens next?**')
            ->line('• Our team will review your application within 1-2 business days')
            ->line('• You will receive an email notification once your application is approved or if we need additional information')
            ->line('• Once approved, you will receive login credentials to access our vendor portal')
            ->line('• You can then participate in Request for Quotes (RFQs) and manage your vendor profile')
            ->line('If you have any questions, please contact our procurement team.')
            ->salutation('Best regards, The Procurement Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'vendor_id' => $this->vendor->id,
            'company_name' => $this->vendor->company_name,
            'contact_email' => $this->vendor->contact_email,
            'message' => 'Vendor registration confirmation sent',
        ];
    }
}