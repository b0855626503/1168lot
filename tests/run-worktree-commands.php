<?php

use App\Console\Commands\Dashboard\LottoRiskCurrentCleanupCommand;
use App\Console\Commands\ValidateLottoRiskCurrentCommand;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

/**
 * Helper script: load worktree command classes first, then run both artisan commands
 * to produce proof output for the PR body.
 *
 * Run from main project directory:
 *   php .claude/worktrees/crazy-edison-b3c985/tests/run-worktree-commands.php
 */
$worktreeRoot = dirname(__DIR__);
$mainProject = '/home/boat/projects/1168lot';

// 1. Load worktree bootstrap (prepends worktree classes to autoloader).
require $worktreeRoot.'/tests/worktree-bootstrap.php';

// 2. Boot Laravel from main project.
$app = require $mainProject.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// 3. Register the worktree's command instances (overrides any already registered version).
$kernel->registerCommand(new LottoRiskCurrentCleanupCommand);
$kernel->registerCommand(new ValidateLottoRiskCurrentCommand);

// 4. Run --dry-run cleanup.
echo "=== dashboard:lotto-risk-current-cleanup --dry-run ===\n";
Artisan::call('dashboard:lotto-risk-current-cleanup', ['--dry-run' => true]);
echo Artisan::output();

// 5. Run --strict validate.
echo "\n=== dashboard:lotto-risk-current-validate --strict ===\n";
Artisan::call('dashboard:lotto-risk-current-validate', ['--strict' => true]);
echo Artisan::output();
