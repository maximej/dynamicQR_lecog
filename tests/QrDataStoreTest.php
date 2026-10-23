<?php
require_once __DIR__ . '/../controllers/QrDataStore.php';

use App\Controllers\QrDataStore;

$directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qr-data-test-' . bin2hex(random_bytes(6));
$store = new QrDataStore($directory);

function assertSameValue($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

try {
    assertSameValue('pantin-2026', QrDataStore::normalizeSlug(' Pantin-2026 '), 'Slug normalization failed.');

    foreach (['ab', 'ends-', 'has space', 'login'] as $invalidSlug) {
        try {
            QrDataStore::validateSlug($invalidSlug);
            throw new RuntimeException('Invalid slug was accepted: ' . $invalidSlug);
        } catch (InvalidArgumentException $expected) {
        }
    }

    $store->registerSlug('pantin', 'qr-1');
    assertSameValue('qr-1', $store->resolveSlug('pantin'), 'Slug lookup failed.');

    try {
        $store->registerSlug('pantin', 'qr-2');
        throw new RuntimeException('Duplicate slug was accepted.');
    } catch (RuntimeException $expected) {
        assertSameValue('This custom URL is already in use.', $expected->getMessage(), 'Unexpected duplicate error.');
    }

    $first = $store->recordView('qr-1');
    $second = $store->recordView('qr-1');
    assertSameValue(1, $first['views'], 'First view was not recorded.');
    assertSameValue(2, $second['views'], 'Second view was not recorded.');

    $stats = $store->getStatsForIds(['qr-1', 'never-seen']);
    assertSameValue(2, $stats['qr-1']['views'], 'Stored view total is incorrect.');
    assertSameValue(0, $stats['never-seen']['views'], 'Unknown QR should have zero views.');
    assertSameValue(null, $stats['never-seen']['last_viewed_at'], 'Unknown QR should have no last-viewed date.');

    assertSameValue(true, $store->isTrackingEnabled('qr-1'), 'Tracking should be enabled by default.');
    $store->setTrackingEnabled('qr-1', false);
    assertSameValue(false, $store->isTrackingEnabled('qr-1'), 'Tracking was not disabled.');
    assertSameValue(false, $store->getTrackingStates(['qr-1'])['qr-1'], 'Bulk tracking state is incorrect.');
    $store->setTrackingEnabled('qr-1', true);
    assertSameValue(true, $store->isTrackingEnabled('qr-1'), 'Tracking was not re-enabled.');

    $store->deleteQrData('qr-1');
    assertSameValue(null, $store->resolveSlug('pantin'), 'Slug cleanup failed.');
    assertSameValue(0, $store->getStatsForIds(['qr-1'])['qr-1']['views'], 'Statistics cleanup failed.');

    echo "QrDataStore tests passed.\n";
} finally {
    foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
        unlink($file);
    }
    if (is_dir($directory)) {
        rmdir($directory);
    }
}
