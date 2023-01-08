<?php

declare(strict_types=1);

namespace Cortex\Foundation\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cortex:publish:foundation')]
class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cortex:publish:foundation {--f|force : Overwrite any existing files.} {--r|resource=* : Specify which resources to publish.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Cortex Foundation Resources.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->alert($this->description);

        collect($this->option('resource') ?: ['config', 'lang', 'views', 'migrations'])->each(function ($resource) {
            $this->call('vendor:publish', ['--tag' => "cortex/foundation::{$resource}", '--force' => $this->option('force')]);
        });

        $this->line('');
    }
}
