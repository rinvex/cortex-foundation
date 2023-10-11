<?php

declare(strict_types=1);

namespace Cortex\Foundation\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:module')]
class ModuleMakeCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:module {name : The name of the module.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module structure';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Module';

    /**
     * Create a new controller creator command instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $name = $this->getNameInput();

        $path = app_path($name);

        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ($this->files->exists($path)) {
            $this->error($this->type.' already exists!');

            return;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $stubs = __DIR__.'/../../../resources/stubs/module';
        $this->processStubs($stubs, $path);
        $this->processOtherStubFiles($path);
        $this->generateSamples();

        $this->info($this->type.' created successfully.');
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param string $path
     *
     * @return string
     */
    protected function makeDirectory($path): string
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0777, true, true);
        }

        return $path;
    }

    /**
     * Generate code samples.
     *
     * @return void
     */
    protected function generateSamples(): void
    {
        $module = Str::after($this->getNameInput(), '/');

        $this->call('make:config', ['name' => 'config', '--module' => $this->getNameInput()]);
        $this->call('make:model', ['name' => 'Example', '--module' => $this->getNameInput()]);
        $this->call('make:observer', ['name' => 'ExampleObserver', '--module' => $this->getNameInput()]);
        $this->call('make:policy', ['name' => 'ExamplePolicy', '--module' => $this->getNameInput()]);
        $this->call('make:provider', ['name' => ucfirst($module).'ServiceProvider', '--module' => $this->getNameInput()]);
        $this->call('make:command', ['name' => 'ExampleCommand', '--module' => $this->getNameInput()]);
        $this->call('make:controller', ['name' => 'ExampleController', '--module' => $this->getNameInput()]);
        $this->call('make:request', ['name' => 'ExampleRequest', '--module' => $this->getNameInput()]);
        $this->call('make:middleware', ['name' => 'ExampleMiddleware', '--module' => $this->getNameInput()]);
        $this->call('make:transformer', ['name' => 'ExampleTransformer', '--model' => 'Example', '--module' => $this->getNameInput()]);
        $this->call('make:datatable', ['name' => 'ExampleDatatable', '--model' => 'Example', '--transformer' => 'ExampleTransformer', '--module' => $this->getNameInput()]);
        $this->call('make:seeder', ['name' => 'ExampleSeeder', '--module' => $this->getNameInput()]);
    
        $this->warn('Optionally create migrations and seeds (it may take some time):');
        $this->warn("artisan make:migration create_{$module}_example_table --module {$this->getNameInput()}");
        $this->warn("artisan make:seeder ExampleSeeder --module {$this->getNameInput()}");
    }

    /**
     * Process stubs placeholders.
     *
     * @param string $stubs
     * @param string $path
     *
     * @return void
     */
    protected function processStubs($stubs, $path): void
    {
        $this->makeDirectory($path);
        $this->files->copyDirectory($stubs, $path);

        $files = [
            ($phpunit = $path.DIRECTORY_SEPARATOR.'phpunit.xml.dist') => $this->files->get($phpunit),
            ($composer = $path.DIRECTORY_SEPARATOR.'composer.json') => $this->files->get($composer),
            ($changelog = $path.DIRECTORY_SEPARATOR.'CHANGELOG.md') => $this->files->get($changelog),
            ($readme = $path.DIRECTORY_SEPARATOR.'README.md') => $this->files->get($readme),
        ];

        $module = ucfirst(Str::after($this->getNameInput(), '/'));
        $name = implode(' ', array_map('ucfirst', explode('/', $this->getNameInput())));
        $jsonNamespace = implode('\\\\', array_map('ucfirst', explode('/', $this->getNameInput())));

        foreach ($files as $key => &$file) {
            $file = str_replace('DummyModuleName', $name, $file);
            $file = str_replace('Dummy\\\\Module', $jsonNamespace, $file);
            $file = str_replace('DummyModuleServiceProvider', $jsonNamespace."\\\\Providers\\\\{$module}ServiceProvider", $file);

            $file = str_replace('dummy/module', $this->getNameInput(), $file);
            $file = str_replace('dummy-module', str_replace('/', '-', $this->getNameInput()), $file);
            $file = str_replace('dummy:module', str_replace('/', ':', $this->getNameInput()), $file);
            $file = str_replace('dummy.module', str_replace('/', '.', $this->getNameInput()), $file);

            $this->files->put($key, $file);
        }
    }

    /**
     * Get the desired class name from the input.
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function getNameInput(): string
    {
        $name = trim($this->argument('name'));

        if (mb_strpos($name, '/') === false) {
            throw new \Exception('Module name must consist of two segments: vendor/module');
        }

        return $name;
    }
    
    protected function processOtherStubFiles($path)
    {
        $files = [
            'resources'.DIRECTORY_SEPARATOR.'views' => ['home.blade.php' => __DIR__.'/../../../resources/stubs/blade.stub'],
            'resources'.DIRECTORY_SEPARATOR.'lang' => ['common.php' => __DIR__.'/../../../resources/stubs/lang.common.stub'],
            'resources'.DIRECTORY_SEPARATOR.'js' => ['webpack.mix.js' => __DIR__.'/../../../resources/stubs/webpack.js.stub'],
            'routes'.DIRECTORY_SEPARATOR.'web' => [
                'adminarea.php' => __DIR__.'/../../../resources/stubs/route.adminarea.stub',
                'frontarea.php' => __DIR__.'/../../../resources/stubs/route.frontarea.stub',
                'managerarea.php' => __DIR__.'/../../../resources/stubs/route.managerarea.stub'
            ],
            'routes'.DIRECTORY_SEPARATOR.'menus' => [
                'adminarea.php' => __DIR__.'/../../../resources/stubs/menus.adminarea.stub',
                'frontarea.php' => __DIR__.'/../../../resources/stubs/menus.frontarea.stub',
                'managerarea.php' => __DIR__.'/../../../resources/stubs/menus.managerarea.stub'
            ],
            'routes'.DIRECTORY_SEPARATOR.'breadcrumbs' => ['adminarea.php' => __DIR__.'/../../../resources/stubs/breadcrumbs.adminarea.stub'],
            'routes'.DIRECTORY_SEPARATOR.'broadcasts' => ['channels.php' => __DIR__.'/../../../resources/stubs/broadcasts.channels.stub'],
            'bootstrap' => [
                'module.php' => __DIR__.'/../../../resources/stubs/bootstrap.module.stub',
                'schedule.php' => __DIR__.'/../../../resources/stubs/bootstrap.schedule.stub'
            ],
            
        ];
        
        collect($files)->each( function ($stubs, $folder) use ($path) {
            $folderPath = $path.DIRECTORY_SEPARATOR.$folder;
            $this->files->makeDirectory($folderPath, 0755, true);
            collect($stubs)->each( function ($stub, $fileName) use ($folderPath) {
                $this->files->put($folderPath.DIRECTORY_SEPARATOR.$fileName, $this->files->get($stub));
            });
        });
    }
}
