<!doctype html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color: #191c1e; line-height: 1.6;">
    <h2>Your account has been verified.</h2>
    <p>Hello <?= esc($firstName) ?>,</p>
    <p>Your tenant account for <strong><?= esc($shopName) ?></strong> has been verified by RHK General Merchandise.</p>
    <p>You can now log in and manage your shop:</p>
    <p><a href="<?= esc($loginUrl) ?>">Log in to your account</a></p>
    <p>Thank you,<br>RHK General Merchandise</p>
</body>
</html>
