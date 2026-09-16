<!doctype html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color: #191c1e; line-height: 1.6;">
    <h2>Merchant Application Update</h2>
    <p>Hello <?= esc($firstName) ?>,</p>
    <p>Thank you for your interest in registering <strong><?= esc($shopName) ?></strong> with RHK General Merchandise.</p>
    <p>After reviewing your application, we regret to inform you that we are unable to approve your shop registration at this time.</p>
    <p><strong>Reason for decision:</strong></p>
    <blockquote style="background: #f1f5f9; border-left: 4px solid #ef4444; margin: 12px 0; padding: 12px 16px;">
        <?= nl2br(esc($reason)) ?>
    </blockquote>
    <p>If you have any questions or have corrected the issues mentioned above, please contact our support team.</p>
    <p>Thank you,<br>RHK General Merchandise Support</p>
</body>
</html>
