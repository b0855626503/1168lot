<?php

/**
 * Worktree test bootstrap — loads the Composer autoloader from the main project and
 * prepends the worktree's app directory ONLY for specific command classes that were
 * added or modified in this worktree (PR-C).
 *
 * Usage (from main project directory):
 *   vendor/bin/phpunit \
 *     --bootstrap .claude/worktrees/crazy-edison-b3c985/tests/worktree-bootstrap.php \
 *     .claude/worktrees/crazy-edison-b3c985/tests/Feature/Lotto/ValidateLottoRiskCurrentCommandTest.php
 */
$mainProjectRoot = '/home/boat/projects/1168lot';
$worktreeAppPath = realpath(dirname(__DIR__).'/app');

// Load the main project's Composer autoloader.
$loader = require $mainProjectRoot.'/vendor/autoload.php';

// Intercept ONLY the classes added/modified in this worktree before the main project's
// autoloader resolves them. This avoids conflicts with deleted classes in the worktree.
$worktreeOnlyClasses = [
    'App\\Console\\Commands\\ValidateLottoRiskCurrentCommand',
    'App\\Console\\Commands\\Dashboard\\LottoRiskCurrentCleanupCommand',
];

if ($worktreeAppPath) {
    spl_autoload_register(function (string $class) use ($worktreeAppPath, $worktreeOnlyClasses): void {
        if (! in_array($class, $worktreeOnlyClasses, true)) {
            return;
        }

        $relative = str_replace(['App\\', '\\'], ['', DIRECTORY_SEPARATOR], $class);
        $file = $worktreeAppPath.DIRECTORY_SEPARATOR.$relative.'.php';

        if (file_exists($file)) {
            require $file;
        }
    }, true, true); // prepend = true, throw = true
}
