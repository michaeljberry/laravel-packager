<?php

namespace MichaelJBerry\Packager;

use Illuminate\Console\Command;
use MichaelJBerry\Packager\PackagerHelper;

/**
 * Get an existing package from a remote Github repository.
 *
 * @package Packager
 * @author MichaelJBerry
 * 
 **/
class PackagerGetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packager:get
                            {url : The url of the Github repository}
                            {vendor? : The vendor part of the namespace}
                            {name? : The name of package for the namespace}
                            {--branch=master : The branch to download}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve an existing package from Github.';

    /**
     * Packager helper class.
     * @var object
     */
    protected $helper;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PackagerHelper $helper)
    {
        parent::__construct();
        $this->helper = $helper;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Start the progress bar
        $bar = $this->helper->barSetup($this->output->createProgressBar(4));
        $bar->start();

        // Common variables
        $origin = rtrim(strtolower($this->argument('url')), '/').'/archive/'.$this->option('branch').'.zip';
        $pieces = explode('/', $origin);
        if (is_null($this->argument('vendor')) || is_null($this->argument('name'))) {
            $vendor = $pieces[3];
            $name = $pieces[4];
        } else {
            $vendor = $this->argument('vendor');
            $name = $this->argument('name');
        }
        $path = getcwd().'/vendor/';
        $fullPath = $path.strtolower($vendor).'/'.strtolower($name);

        // Start creating the package
        $this->info('Creating package '.$vendor.'\\'.$name.'...');
        $this->helper->checkExistingPackage($path, strtolower($name));
        $bar->advance();

        // Create the package directory
        $this->info('Creating packages directory...');
        $this->helper->makeDir($path);
        $bar->advance();

        // Create the vendor directory
        $this->info('Creating vendor...');
        $this->helper->makeDir($path.strtolower($vendor));
        $bar->advance();

        // Get the skeleton repo from the PHP League
        $this->info('Downloading from Github...');
        $this->helper->download($zipFile = $this->helper->makeFilename(), $origin)
             ->extract($zipFile, $path.strtolower($vendor))
             ->cleanUp($zipFile);
        rename($path.strtolower($vendor).'/'.$pieces[4]. '-'.$this->option('branch'), $fullPath);
        $bar->advance();

        // Finished creating the package, end of the progress bar
        $bar->finish();
        $this->info('Package created successfully!');
        $this->output->newLine(2);
        $bar = null;

        // Composer dump-autoload to identify new MyPackageServiceProvider
        $this->helper->dumpAutoloads();
    }
}
