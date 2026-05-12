<?php

namespace App\Mail;

use App\Models\Sign\DocumentSignRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignRequestRejected extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public DocumentSignRequest $signRequest)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[EduGroup] Yêu cầu ký số đã bị từ chối',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.sign-request-rejected',
            with: [
                'signRequest' => $this->signRequest,
                'student'     => $this->signRequest->requester,
                'lecturer'    => $this->signRequest->lecturer,
                'reason'      => $this->signRequest->reject_reason,
                'category'    => $this->signRequest->document_category_label,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
