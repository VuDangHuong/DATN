<?php

namespace App\Mail;

use App\Models\Sign\DocumentSignRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignRequestCreated extends Mailable
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
            subject: '[EduGroup] Yêu cầu ký số mới từ ' . $this->signRequest->requester?->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.sign-request-created',
            with: [
                'signRequest' => $this->signRequest,
                'lecturer'    => $this->signRequest->lecturer,
                'student'     => $this->signRequest->requester,
                'class'       => $this->signRequest->classModel,
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
