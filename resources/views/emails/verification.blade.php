<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; background: #f5f7fa; margin: 0; padding: 0; }
    .container { max-width: 480px; margin: 40px auto; background: #fff; border-radius: 16px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    .logo { text-align: center; font-size: 22px; font-weight: 700; color: #0066ff; margin-bottom: 8px; }
    h2 { text-align: center; color: #1e293b; margin-bottom: 8px; }
    p { color: #64748b; text-align: center; line-height: 1.6; }
    .code-box { text-align: center; margin: 32px 0; }
    .code { display: inline-block; font-size: 42px; font-weight: 800; letter-spacing: 12px; color: #0066ff; background: #eff6ff; padding: 16px 32px; border-radius: 12px; }
    .expiry { text-align: center; color: #94a3b8; font-size: 13px; margin-top: -16px; }
    .footer { text-align: center; margin-top: 32px; color: #cbd5e1; font-size: 12px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">🍽️ Flavors of Bantayan</div>
    <h2>Verify Your Email</h2>
    <p>Use the code below to complete your registration. It expires in <strong>2 minutes</strong>.</p>
    <div class="code-box">
      <span class="code">{{ $code }}</span>
    </div>
    <p class="expiry">⏱ Expires in 2 minutes</p>
    <p>If you didn't create an account, you can safely ignore this email.</p>
    <div class="footer">© {{ date('Y') }} Flavors of Bantayan. All rights reserved.</div>
  </div>
</body>
</html>