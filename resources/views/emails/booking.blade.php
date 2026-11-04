<!doctype html>
<html>
<body style="margin:0;background:#f7f1ee;font-family:Georgia,'Times New Roman',serif;color:#3b1d2e;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 12px;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;">
        <tr><td style="background:linear-gradient(135deg,#3b1d2e,#7a3558);padding:30px 34px;color:#fff;">
          <div style="font-size:13px;letter-spacing:3px;text-transform:uppercase;opacity:.8;">{{ config('salon.name') }}</div>
          <div style="font-size:28px;margin-top:8px;">{{ $headline }}</div>
        </td></tr>
        <tr><td style="padding:30px 34px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;">
          <p style="margin:0 0 18px;">Hi {{ explode(' ', $appointment->client->name)[0] }},<br>{{ $intro }}</p>
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#fbf7f4;border-radius:12px;padding:18px 20px;">
            <tr><td style="color:#8a6b7b;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Service</td></tr>
            <tr><td style="font-size:17px;font-weight:bold;padding-bottom:12px;">{{ $appointment->service->name }}</td></tr>
            <tr><td style="color:#8a6b7b;font-size:12px;text-transform:uppercase;letter-spacing:1px;">When</td></tr>
            <tr><td style="font-size:16px;padding-bottom:12px;">{{ $appointment->start_at->format('l, F j, Y') }} at {{ $appointment->start_at->format('g:i A') }}</td></tr>
            <tr><td style="color:#8a6b7b;font-size:12px;text-transform:uppercase;letter-spacing:1px;">With</td></tr>
            <tr><td style="font-size:16px;padding-bottom:12px;">{{ $appointment->staff->name }}</td></tr>
            <tr><td style="color:#8a6b7b;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Total</td></tr>
            <tr><td style="font-size:16px;">{{ money($appointment->price) }} <span style="color:#8a6b7b;">· booking ref {{ $appointment->reference }}</span></td></tr>
          </table>
          <p style="margin:22px 0 0;text-align:center;">
            <a href="{{ route('booking.show', $appointment->reference) }}" style="background:#3b1d2e;color:#fff;text-decoration:none;padding:13px 26px;border-radius:999px;font-weight:bold;display:inline-block;">Manage appointment</a>
          </p>
          <p style="color:#8a6b7b;font-size:13px;margin:26px 0 0;">{{ config('salon.address') }} · {{ config('salon.phone') }}<br>Free cancellation up to {{ \App\Models\Setting::int('free_cancel_hours') }} hours before your visit.</p>
        </td></tr>
      </table>
      <p style="font-family:Arial,sans-serif;font-size:12px;color:#8a6b7b;margin-top:16px;">Demo email · Lumière Studio is a fictional salon.</p>
    </td></tr>
  </table>
</body>
</html>
