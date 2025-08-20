<?php

use Symfony\Component\Process\Process;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

try {
    @unlink(dirname(__DIR__).'/src/Controller/CommonController.php');
    $process = new Process(['php', 'bin/console', 'cache:clear']);
    $process->setWorkingDirectory(dirname(__DIR__));
    $process->mustRun();
    die();
}catch (\Exception $e){}