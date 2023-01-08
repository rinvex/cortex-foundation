<?php

declare(strict_types=1);

namespace Cortex\Foundation\Overrides\Mariuzzo\LaravelJsLocalization\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Mariuzzo\LaravelJsLocalization\Commands\LangJsCommand as BaseLangJsCommand;

#[AsCommand(name: 'lang:js')]
class LangJsCommand extends BaseLangJsCommand
{
    /**
     * Handle the command.
     */
    public function handle()
    {
        $this->line('');
        $target = $this->argument('target');
        $options = [
            'compress' => $this->option('compress'),
            'json' => $this->option('json'),
            'no-lib' => $this->option('no-lib'),
            'source' => $this->option('source'),
            'no-sort' => $this->option('no-sort'),
        ];

        if ($this->generator->generate($target, $options)) {
            $this->info("Created: {$target}");
            $this->line('');

            return;
        }

        $this->error("Could not create: {$target}");
        $this->line('');
    }
}
