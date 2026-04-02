<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Password Reset Code</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f5f7fa; margin: 0; padding: 0; }
    .wrapper { max-width: 480px; margin: 40px auto; background: #ffffff; border-radius: 16px;
               padding: 40px 32px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .logo { text-align: center; margin-bottom: 24px; }
    h1 { font-size: 22px; color: #1e293b; text-align: center; margin-bottom: 8px; }
    p  { font-size: 15px; color: #64748b; line-height: 1.6; text-align: center; }
    .code-box {
      display: block; width: fit-content; margin: 28px auto;
      background: #eff6ff; border: 2px dashed #0066ff;
      border-radius: 12px; padding: 16px 40px;
      font-size: 36px; font-weight: 700; letter-spacing: 10px;
      color: #0066ff; text-align: center;
    }
    .note { font-size: 13px; color: #94a3b8; margin-top: 24px; }
    .footer { margin-top: 32px; border-top: 1px solid #e2e8f0; padding-top: 20px;
              font-size: 12px; color: #94a3b8; text-align: center; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="logo">🔐</div>
    <h1>Password Reset Request</h1>
    <p>We received a request to reset your admin account password.<br>Use the code below — it expires in <strong>2 minutes</strong>.</p>

    <span class="code-box">{{ $code }}</span>

    <p class="note">If you did not request a password reset, you can safely ignore this email. Your password will not be changed.</p>

    <div class="footer">
      This is an automated message — please do not reply.<br>
      &copy; {{ date('Y') }} Your App Name
    </div>
  </div>
</body>
</html>