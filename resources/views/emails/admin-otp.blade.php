<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GenRev Registration OTP</title>
</head>
<body>
    <p>Hello Masters Admin,</p>

    <p>A new GenRev account registration was requested with the following details:</p>

    <ul>
        <li><strong>Name:</strong> {{ $name }}</li>
        <li><strong>Email:</strong> {{ $email }}</li>
        <li><strong>Requested Role:</strong> {{ ucfirst($role) }}</li>
    </ul>

    <p>Use this OTP to approve the registration:</p>

    <h2 style="font-size: 24px; letter-spacing: 4px; margin: 10px 0;">
        {{ $otp }}
    </h2>

    <p>This code is valid for a short time. Share it only with the person you are approving.</p>

    <p>– GenRev System</p>
</body>
</html>
