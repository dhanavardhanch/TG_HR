<?php
require_once __DIR__ . '/../app/Helpers/MailHelper.php';

echo "Testing MailHelper::send()...\n";

$res = MailHelper::send(
    'dailymails@troogood.in',
    'TrooGood HRMS System Test - ' . date('d-M-Y H:i:s'),
    MailHelper::wrapHtmlTemplate(
        'Outbound Mail Server Operational Test',
        '<p>This email confirms that the <strong>TrooGood HRMS Outbound Mail Server (troogood.in:465)</strong> is operational and properly configured for all automated system mail triggers.</p><ul><li>Host: troogood.in:465 (SSL)</li><li>Sender: Troo Good Alerts &lt;dailymails@troogood.in&gt;</li><li>Trigger Engine: Active</li></ul>'
    )
);

print_r($res);
