<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invitation->coach->name }} invited you to Throughline</title>
</head>
<body style="margin:0;background:#f7f4ed;font-family:Arial,sans-serif;color:#1c1917;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f4ed;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid #e7e5e4;border-radius:24px;padding:32px;">
                    <tr>
                        <td>
                            <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;color:#78716c;">{{ $senderLabel ?: 'Throughline coaching invite' }}</p>
                            <h1 style="margin:0 0 16px;font-size:28px;line-height:1.1;color:#111827;">{{ $invitation->coach->name }} invited you to join their athlete roster.</h1>
                            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#57534e;">{{ $body ?: 'Create your athlete account, accept the coach connection, and start tracking training, recovery, progress, and assigned workouts in one place.' }}</p>
                            @if($invitation->goal)
                                <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#57534e;"><strong>Coach goal:</strong> {{ $invitation->goal }}</p>
                            @endif
                            <p style="margin:0 0 24px;font-size:14px;line-height:1.7;color:#78716c;">This link expires {{ $invitation->expires_at?->toDayDateTimeString() ?? 'soon' }}.</p>
                            <p style="margin:0 0 28px;">
                                <a href="{{ $acceptUrl }}" style="display:inline-block;border-radius:999px;background:#0f766e;color:#ffffff;text-decoration:none;font-weight:700;padding:14px 22px;">Accept invitation</a>
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.7;color:#a8a29e;">If the button does not work, copy this link into your browser:<br>{{ $acceptUrl }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
