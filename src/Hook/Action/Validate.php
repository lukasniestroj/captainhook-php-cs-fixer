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
 * Class Validate
 * @package LukasNiestroj\CaptainHook\Hook\Action
 */
class Validate implements Action, Constrained
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
        $result = $process->run($executable . ' fix --dry-run --diff --diff-format=udiff -v --stop-on-violation --config=' . $configFile . ' ' . escapeshellarg($files));

        if (!$result->isSuccessful()) {
            $io->writeError($result->getStdErr());
            throw new ActionFailed('<error>php-cs-fixer failed</error>');
        }
        $io->write($result->getStdOutAsArray());
    }

    public static function getRestriction(): Restriction
    {
        return new Restriction('pre-commit');
    }

}