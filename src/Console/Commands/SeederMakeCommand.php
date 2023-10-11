<?php

declare(strict_types=1);

namespace Cortex\Foundation\Console\Commands;

use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Cortex\Foundation\Traits\ConsoleMakeModuleCommand;
use Illuminate\Database\Console\Seeds\SeederMakeCommand as BaseSeederMakeCommand;

#[AsCommand(name: 'make:seeder')]
class SeederMakeCommand extends BaseSeederMakeCommand
{
    use ConfirmableTrait;
    use ConsoleMakeModuleCommand;

    /**
     * Get the destination class path.
     *
     * @param string $name
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function getPath($name): string
    {
        $name = \Str::replaceFirst($this->rootNamespace(), $this->moduleName().DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'seeders'.DIRECTORY_SEPARATOR, $name);

        if (! $this->files->exists($path = $this->laravel['path'].DIRECTORY_SEPARATOR.$this->moduleName().DIRECTORY_SEPARATOR)) {
            throw new \Exception("Invalid path: $path");
        }

        return $this->laravel['path'].DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $name).'.php';
    }
}
