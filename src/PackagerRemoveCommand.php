<?php

namespace MichaelJBerry\Packager;

use Illuminate\Console\Command;
use MichaelJBerry\Packager\PackagerHelper;

/**
 * remove an existing package.
 *
 * @package Packager
 * @author MichaelJBerry
 * 
 **/
class PackagerRemoveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packager:remove {vendor} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove an existing package.';

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
        $bar = $this->helper->barSetup($this->output->createProgressBar(3));
        $bar->start();

        // Common variables
        $vendor = $this->argument('vendor');
        $name = $this->argument('name');
        $path = getcwd().'/vendor/';
        $fullPath = $path.strtolower($vendor).'/'.strtolower($name);

        // Start removing the package
        $this->info('Removing package '.$vendor.'\\'.$name.'...');
        $bar->advance();

        // remove the package directory
        $this->info('Removing packages directory...');
        $this->helper->removeDir($fullPath);
        $bar->advance();

        // Remove the vendor directory, if agreed to
        if ($this->confirm('Do you want to remove the vendor directory? [y|N]')) {
            $this->info('removing vendor directory...');
            $this->helper->removeDir($path.strtolower($vendor));
        } else {
            $this->info('Continuing...');
        }
        $bar->advance();

        // Finished removing the package, end of the progress bar
        $bar->finish();
        $this->info('Package removed successfully!');
        $this->output->newLine(2);
        $bar = null;

        // Composer dump-autoload to identify new MyPackageServiceProvider
        $this->helper->dumpAutoloads();
    }
}
