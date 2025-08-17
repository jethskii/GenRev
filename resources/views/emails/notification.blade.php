<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'GenRev Notification' }}</title>
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Basic mobile tweaks -->
    <style>
        /* Prevent dark mode inversions in some clients */
        :root { color-scheme: light dark; supported-color-schemes: light dark; }
        /* Responsive */
        @media (max-width: 640px){
            .container { width: 100% !important; border-radius: 0 !important; }
            .px { padding-left: 20px !important; padding-right: 20px !important; }
        }
        /* Button hover (supported webmail/Apple Mail) */
        .btn:hover { filter: brightness(1.08); }
    </style>
</head>
<body style="margin:0; padding:0; background:#001C00; font-family: Arial, Helvetica, sans-serif; -webkit-font-smoothing:antialiased;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#001C00; padding:40px 0;">
    <tr>
        <td align="center">
            <!-- Outer container -->
            <table role="presentation" width="600" class="container" cellpadding="0" cellspacing="0"
                   style="width:600px; max-width:600px; background:linear-gradient(135deg,#1F1E1E 0%, #001C00 100%);
                          border:1px solid #ffffff; border-radius:20px; box-shadow:0 0 10px rgba(0,0,0,.3);">
                <!-- Header -->
                <tr>
                    <td class="px" style="padding:28px 30px 10px 30px; text-align:center;">
                        <div style="font-size:24px; font-weight:700; color:#ffffff; margin:0; text-shadow:-2px 1px 0 #047705;">
                            🔔 GenRev Notifications
                        </div>
                        <div style="height:1px; background:#ffffff; opacity:.9; margin-top:18px;"></div>
                    </td>
                </tr>

                <!-- Title -->
                @isset($title)
                <tr>
                    <td class="px" style="padding:22px 30px 0 30px;">
                        <h1 style="margin:0; font-size:20px; line-height:1.35; color:#FFFFFF; font-weight:700;">
                            {{ $title }}
                        </h1>
                    </td>
                </tr>
                @endisset

                <!-- Body -->
                <tr>
                    <td class="px" style="padding:16px 30px 0 30px; color:#EAEAEA; font-size:15px; line-height:1.6;">
                        <p style="margin:0 0 10px 0;">Hello,</p>
                        @if(!empty($message))
                            <p style="margin:0 0 10px 0;">{{ $message }}</p>
                        @else
                            <p style="margin:0 0 10px 0;">You have a new update regarding your order.</p>
                        @endif

                        @isset($status)
                            <p style="margin:0 0 6px 0;">Status:
                                <strong style="color:#EDD100;">{{ $status }}</strong>
                            </p>
                        @endisset

                        <p style="margin:0 0 6px 0;">Updated by: <strong>GenRev Team</strong></p>

                        <p style="margin:0 0 0 0;">
                            Date:
                            <strong>
                                @php
                                    $dt = isset($createdAt) ? \Illuminate\Support\Carbon::parse($createdAt) : now();
                                @endphp
                                {{ $dt->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                            </strong>
                        </p>
                    </td>
                </tr>

                <!-- Call to action -->
                @isset($actionUrl)
                <tr>
                    <td class="px" align="center" style="padding:24px 30px 0 30px;">
                        <a href="{{ $actionUrl }}"
                           class="btn"
                           style="display:inline-block; text-decoration:none; padding:12px 22px; border-radius:999px;
                                  background:#047705; color:#FFFFFF; font-weight:700; font-size:14px;">
                            {{ $actionText ?? 'View details' }}
                        </a>
                    </td>
                </tr>
                @endisset

                <!-- Spacer -->
                <tr><td style="height:24px; line-height:24px; font-size:0;">&nbsp;</td></tr>

                <!-- Footer -->
                <tr>
                    <td class="px" align="center" style="padding:16px 30px 26px 30px; color:#BDBDBD; font-size:12px;">
                        &copy; {{ now()->year }} GenRev. All rights reserved.
                    </td>
                </tr>
            </table>
            <!-- /container -->
        </td>
    </tr>
</table>
</body>
</html>
