<?php

declare(strict_types=1);

namespace Cortex\Foundation\Overrides\Livewire\Commands;

use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Cortex\Foundation\Traits\ConsoleMakeModuleCommand;
use Livewire\Commands\ComponentParserFromExistingComponent;

#[AsCommand(name: 'livewire:copy')]
class CopyCommand extends FileManipulationCommand
{
    use ConsoleMakeModuleCommand;

    protected $signature = 'livewire:copy {name} {new-name} {--inline} {--force} {--test} {--m|module= : The module name to generate the file within.} {--a|accessarea= : The accessarea to generate the file within.}';

    protected $description = 'Copy a Livewire component';

    public function handle()
    {
        $this->parser = new ComponentParser(
            $rootNamespace = $this->rootNamespace(),
            $this->getResourcePath($rootNamespace, 'src/Http/Components'),
            $this->getResourcePath($rootNamespace, 'resources/views'),
            $this->getAccessareaName(),
            $this->argument('name')
        );

        $this->newParser = new ComponentParserFromExistingComponent(
            $rootNamespace = $this->rootNamespace(),
            $this->getResourcePath($rootNamespace, 'src/Http/Components'),
            $this->getResourcePath($rootNamespace, 'resources/views'),
            $this->argument('new-name'),
            $this->parser
        );

        $force = $this->option('force');
        $inline = $this->option('inline');
        $test = $this->option('test');

        $class = $this->copyClass($force, $inline);

        if (! $inline) {
            $view = $this->copyView($force);
        }

        if ($test) {
            $test = $this->copyTest($force);
        }

        $this->refreshComponentAutodiscovery();

        $this->line("<options=bold,reverse;fg=green> COMPONENT COPIED </> 🤙\n");
        $class && $this->line("<options=bold;fg=green>CLASS:</> {$this->parser->relativeClassPath()} <options=bold;fg=green>=></> {$this->newParser->relativeClassPath()}");

        if (! $inline) {
            $view && $this->line("<options=bold;fg=green>VIEW:</>  {$this->parser->relativeViewPath()} <options=bold;fg=green>=></> {$this->newParser->relativeViewPath()}");
        }

        if ($test) {
            $test && $this->line("<options=bold;fg=green>Test:</>  {$this->parser->relativeTestPath()} <options=bold;fg=green>=></> {$this->newParser->relativeTestPath()}");
        }
    }

    protected function copyTest($force)
    {
        if (File::exists($this->newParser->testPath()) && ! $force) {
            $this->line("<options=bold,reverse;fg=red> WHOOPS-IE-TOOTLES </> 😳 \n");
            $this->line("<fg=red;options=bold>Test already exists:</> {$this->newParser->relativeTestPath()}");

            return false;
        }

        $this->ensureDirectoryExists($this->newParser->testPath());

        return File::copy("{$this->parser->testPath()}", $this->newParser->testPath());
    }

    protected function copyClass($force, $inline)
    {
        if (File::exists($this->newParser->classPath()) && ! $force) {
            $this->line("<options=bold,reverse;fg=red> WHOOPS-IE-TOOTLES </> 😳 \n");
            $this->line("<fg=red;options=bold>Class already exists:</> {$this->newParser->relativeClassPath()}");

            return false;
        }

        $this->ensureDirectoryExists($this->newParser->classPath());

        return File::put($this->newParser->classPath(), $this->newParser->classContents($inline));
    }

    protected function copyView($force)
    {
        if (File::exists($this->newParser->viewPath()) && ! $force) {
            $this->line("<fg=red;options=bold>View already exists:</> {$this->newParser->relativeViewPath()}");

            return false;
        }

        $this->ensureDirectoryExists($this->newParser->viewPath());

        return File::copy("{$this->parser->viewPath()}", $this->newParser->viewPath());
    }
}
