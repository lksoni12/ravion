<?php

$requiredExtensions = [
    'mysqli' => 'MySQL database driver used by this project.',
    'fileinfo' => 'File upload MIME detection for product PDF and logo uploads.',
];

$missingRequired = [];
foreach ($requiredExtensions as $extension => $reason) {
    if (! extension_loaded($extension)) {
        $missingRequired[$extension] = $reason;
    }
}

if (! empty($missingRequired)) {
    if (! headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error', true, 500);
        header('Content-Type: text/plain; charset=UTF-8');
    }

    echo "Ravion Pharma Admin cannot start because required PHP extensions are missing:\n\n";
    foreach ($missingRequired as $extension => $reason) {
        echo "- {$extension}: {$reason}\n";
    }
    echo "\nEnable these extensions in hosting/cPanel and reload.\n";

    exit(1);
}

if (! extension_loaded('mbstring')) {
    require __DIR__ . '/mbstring_polyfill.php';
}

if (! extension_loaded('intl')) {
    require __DIR__ . '/intl_polyfill.php';
}
