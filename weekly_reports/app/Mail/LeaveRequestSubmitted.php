<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveRequestSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public LeaveRequest $leaveRequest)
    {
        $this->leaveRequest->loadMissing('user');
    }

    public function build(): self
    {
        return $this
            ->subject("New Leave Request — {$this->leaveRequest->user->name}")
            ->view('emails.leave-request-submitted');
    }
}
