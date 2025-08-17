<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenrevNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Generic payload (title, message, status, action_url, etc.)
     * e.g. [
     *   'title' => 'Order #123 Ready for Pickup',
     *   'message' => 'Your order is now ready...',
     *   'status' => 'Ready for Pickup',
     *   'order_id' => 123,
     *   'action_text' => 'View Order',
     *   'action_url' => route('orders.show', 123),
     *   'subject' => 'GenRev: Order Update'
     * ]
     */
    public function __construct(public array $notification = [])
    {
        //
    }

    public function envelope(): Envelope
    {
        $subject  = $this->notification['subject']
            ?? $this->notification['title']
            ?? 'GenRev: Notification';

        $fromAddr = config('mail.from.address');
        $fromName = config('mail.from.name', config('app.name', 'GenRev'));

        return new Envelope(
            from: $fromAddr ? new Address($fromAddr, $fromName) : null,
            subject: $subject,
            tags: ['genrev', 'notification'],
            metadata: [
                'type'     => (string)($this->notification['type']     ?? 'notification'),
                'order_id' => (string)($this->notification['order_id'] ?? ''),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            // Create this view: resources/views/emails/genrev/notification.blade.php
            markdown: 'emails.genrev.notification',
            with: [
                'title'      => $this->notification['title']       ?? 'Notification',
                'message'    => $this->notification['message']     ?? '',
                'status'     => $this->notification['status']      ?? null,
                'actionText' => $this->notification['action_text'] ?? 'View details',
                'actionUrl'  => $this->notification['action_url']  ?? null,
                'createdAt'  => $this->notification['created_at']  ?? now(),
            ],
        );
    }

    public function attachments(): array
    {
        // Optionally return Attachment instances if needed.
        return [];
    }
}
