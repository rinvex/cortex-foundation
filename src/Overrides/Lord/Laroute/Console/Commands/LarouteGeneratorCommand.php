<?php

declare(strict_types=1);

namespace Cortex\Foundation\Overrides\Lord\Laroute\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Lord\Laroute\Console\Commands\LarouteGeneratorCommand as BaseLarouteGeneratorCommand;

#[AsCommand(name: 'laroute:generate')]
class LarouteGeneratorCommand extends BaseLarouteGeneratorCommand
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->line('');

        try {
            $filePath = $this->generator->compile(
                $this->getTemplatePath(),
                $this->getTemplateData(),
                $this->getFileGenerationPath()
            );

            $this->info("Created: {$filePath}");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->line('');
    }
}
