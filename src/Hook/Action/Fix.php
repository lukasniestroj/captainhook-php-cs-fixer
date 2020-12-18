<?php


namespace LukasNiestroj\CaptainHook\Hook\Action;


use CaptainHook\App\Config;
use CaptainHook\App\Console\IO;
use CaptainHook\App\Exception\ActionFailed;
use CaptainHook\App\Hook\Action;
use CaptainHook\App\Hook\Constrained;
use CaptainHook\App\Hook\Restriction;
use SebastianFeldmann\Cli\Processor\ProcOpen as Processor;
use SebastianFeldmann\Git\Repository;

/**
 * Class Fix
 * @package LukasNiestroj\CaptainHook\Hook\Action
 */
class Fix implements Action, Constrained
{

    public function execute(Config $config, IO $io, Repository $repository, Config\Action $action): void
    {
        $changedPHPFiles = $repository->getIndexOperator()->getStagedFilesOfType('php');

        if (\count($changedPHPFiles) === 0) {
            return;
        }

        $executable = $action->getOptions()->get('executable', 'vendor/bin/php-cs-fixer');
        $configFile = $action->getOptions()->get('config-file', '.php_cs');

        $process = new Processor();

        $files = \implode(' ', $changedPHPFiles);
        $result = $process->run($executable . ' fix --config=' . $configFile . ' ' . escapeshellarg($files));

        if (!$result->isSuccessful()) {
            throw new ActionFailed('<error>php-cs-fixer failed</error>');
        }

        $process->run('git add ' . escapeshellarg($files));
        $io->write('<info>done</info>');
    }

    public static function getRestriction(): Restriction
    {
        return new Restriction('pre-commit');
    }

}