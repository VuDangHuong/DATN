<?php

namespace App\Mail;

use App\Models\Evaluation\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmissionReviewed extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly Submission $submission,
        public readonly string     $recipientName,
    ) {}
 
    public function envelope(): Envelope
    {
        $subject = $this->submission->isApproved()
            ? '✅ Bài nộp của bạn đã được chấp nhận'
            : '❌ Bài nộp của bạn đã bị từ chối';
 
        return new Envelope(subject: $subject);
    }
 
    public function content(): Content
    {
        return new Content(view: 'emails.submission-reviewed');
    }
 
    public function attachments(): array
    {
        return [];
    }
}
