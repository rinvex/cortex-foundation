<?php

declare(strict_types=1);

namespace Cortex\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Illuminate\Foundation\Console\StorageLinkCommand as BaseStorageLinkCommand;

#[AsCommand(name: 'storage:link')]
class StorageLinkCommand extends BaseStorageLinkCommand
{
    /**
     * Just decrease level or alert from error to warning!
     *
     * We do not need to scare people over this.
     *
     * Write a string as error output.
     *
     * @param string          $string
     * @param int|string|null $verbosity
     *
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        $this->warn($string);
    }
}
