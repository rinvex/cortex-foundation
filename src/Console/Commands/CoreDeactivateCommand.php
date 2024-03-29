<?php

declare(strict_types=1);

namespace Cortex\Foundation\Console\Commands;

use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cortex:deactivate')]
class CoreDeactivateCommand extends AbstractModuleCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cortex:deactivate {--f|force : Force the operation to run when in production.} {--m|module=* : Specify which modules to deactivate.} {--e|extension=* : Specify which extensions to deactivate.} {--u|unload : Unload modules/extensions before deactivating.} {--all-modules : deactivate all modules.} {--all-extensions : deactivate all extensions.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate Cortex Modules/Extensions.';

    /**
     * Execute the console command.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        $this->option('unload') ? $this->process(['autoload' => false, 'active' => false]) : $this->process(['active' => false]);
    }
}
