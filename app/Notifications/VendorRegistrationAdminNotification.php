<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorRegistrationAdminNotification extends Notification implements ShouldQueue
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
        $adminUrl = config('app.url') . '/admin/vendors/' . $this->vendor->id;

        return (new MailMessage)
            ->subject('New Vendor Registration - ' . $this->vendor->company_name)
            ->greeting('Hello Admin!')
            ->line('A new vendor has registered and requires your review.')
            ->line('**Vendor Details:**')
            ->line('Company: ' . $this->vendor->company_name)
            ->line('Category: ' . $this->vendor->category->name)
            ->line('Contact: ' . $this->vendor->contact_name)
            ->line('Email: ' . $this->vendor->contact_email)
            ->line('Phone: ' . ($this->vendor->contact_phone ?: 'Not provided'))
            ->when($this->vendor->tax_id, function ($message) {
                return $message->line('Tax ID: ' . $this->vendor->tax_id);
            })
            ->when($this->vendor->company_description, function ($message) {
                return $message->line('Description: ' . $this->vendor->company_description);
            })
            ->line('Status: Pending Approval')
            ->action('Review Vendor', $adminUrl)
            ->line('Please review this vendor registration and approve or reject as appropriate.')
            ->salutation('Best regards, Vendor Management System');
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
            'status' => $this->vendor->status,
            'message' => 'New vendor registration requires review',
        ];
    }
}