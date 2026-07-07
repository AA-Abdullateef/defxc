<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveRequestDecided extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public LeaveRequest $leaveRequest)
    {
        $this->leaveRequest->loadMissing('decidedBy');
    }

    public function build(): self
    {
        $status = ucfirst($this->leaveRequest->status);

        return $this
            ->subject("Your Leave Request Has Been {$status}")
            ->view('emails.leave-request-decided');
    }
}
