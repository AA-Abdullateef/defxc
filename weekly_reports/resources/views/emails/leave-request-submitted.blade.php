<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; color:#111827; background:#f6f7fb; padding:24px;">
    <div style="max-width:520px; margin:0 auto; background:#fff; border-radius:12px; padding:28px; border:1px solid #e5e7eb;">
        <p style="font-size:11px; letter-spacing:0.1em; text-transform:uppercase; color:#16a34a; margin:0 0 6px;">Zeltechnologies — Staff Portal</p>
        <h2 style="margin:0 0 20px; font-size:18px;">New Leave Request</h2>

        <p style="margin:0 0 16px;">
            <strong>{{ $leaveRequest->user->name }}</strong> ({{ $leaveRequest->user->email }}) has submitted a leave request.
        </p>

        <table style="width:100%; border-collapse:collapse; font-size:14px; margin-bottom:20px;">
            <tr>
                <td style="padding:6px 0; color:#64748b;">Start Date</td>
                <td style="padding:6px 0; text-align:right;">{{ $leaveRequest->start_date->format('d M Y') }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0; color:#64748b;">End Date</td>
                <td style="padding:6px 0; text-align:right;">{{ $leaveRequest->end_date->format('d M Y') }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0; color:#64748b;">Days Requested</td>
                <td style="padding:6px 0; text-align:right;">{{ $leaveRequest->daysRequested() }}</td>
            </tr>
        </table>

        <p style="margin:0 0 6px; color:#64748b; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Reason</p>
        <p style="margin:0 0 20px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px;">
            {{ $leaveRequest->reason }}
        </p>

        <a href="{{ route('admin.leave.index') }}"
           style="display:inline-block; background:#16a34a; color:#fff; text-decoration:none; padding:10px 18px; border-radius:8px; font-size:14px;">
            Review Leave Requests
        </a>
    </div>
</body>
</html>
