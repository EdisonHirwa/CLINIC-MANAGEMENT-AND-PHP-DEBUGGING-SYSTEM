#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Group 6 Clinic Management CLI Entrypoint
 * Executable command-line runner.
 */

// 1. Check for Composer autoloader (two levels up: src/bin -> src -> root)
$composerAutoload = __DIR__ . '/../../vendor/autoload.php';

if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    // 2. Built-in PSR-4 Autoloader Fallback
    spl_autoload_register(function (string $class): void {
        $prefix = 'ClinicManagement\\';
        $baseDir = __DIR__ . '/../'; // src/ directory

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });
}

use ClinicManagement\Models\Clinic;
use ClinicManagement\Console\CliApplication;

// Verify CLI invocation
if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Error: This application can only be executed in a CLI environment.\n");
    exit(1);
}

// Bootstrap application
$clinic = new Clinic("HopeCare General Clinic", "Medical District Tower B");
$app = new CliApplication($clinic);
$app->run();

