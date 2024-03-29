<?php

declare(strict_types=1);

namespace Cortex\Foundation\Console\Commands;

use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cortex:autoload')]
class CoreAutoloadCommand extends AbstractModuleCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cortex:autoload {--f|force : Force the operation to run when in production.} {--m|module=* : Specify which modules to autoload.} {--e|extension=* : Specify which extensions to autoload.} {--a|activate : Activate modules/extensions after autoloading.} {--all-modules : autoload all modules.} {--all-extensions : autoload all extensions.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Autoload Cortex Modules/Extensions.';

    /**
     * Execute the console command.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        $this->option('activate') ? $this->process(['autoload' => true, 'active' => true]) : $this->process(['autoload' => true]);
    }
}
