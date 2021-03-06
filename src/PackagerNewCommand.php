<?php

namespace MichaelJBerry\Packager;

use Illuminate\Console\Command;
use MichaelJBerry\Packager\PackagerHelper;

/**
 * Create a brand new package.
 *
 * @package Packager
 * @author MichaelJBerry
 *
 **/
class PackagerNewCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packager:new {vendor} {name} {--i}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new package.';

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
        $bar = $this->helper->barSetup($this->output->createProgressBar(5));
        $bar->start();

        // Common variables
        // Starting with vendor/package, optionally defined interactively
        if ($this->option('i')) {
            $vendor = $this->ask('What will be the vendor name?', $this->argument('vendor'));
            $name = $this->ask('What will be the package name?', $this->argument('name'));
        } else {
            $vendor = $this->argument('vendor');
            $name = $this->argument('name');
    }
        $path = preg_replace( '~[/\\\\][^/\\\\]*[/\\\\]$~' , DIRECTORY_SEPARATOR , getcwd() . DIRECTORY_SEPARATOR
            ) . 'packages' . DIRECTORY_SEPARATOR;
        $fullPath = $path . strtolower($name);
        $requireSupport = '"illuminate/support": "~5.1",
        "php"';

        // Start creating the package
        $this->info('Creating package '.$vendor.'\\'.$name.'...');
        $this->helper->checkExistingPackage($path, $name);
        $bar->advance();

        // Create the package directory
        $this->info('Creating packages directory...');
        $this->helper->makeDir($path);
        $bar->advance();

        // Create the vendor directory
//        $this->info('Creating vendor...');
//        $this->helper->makeDir($path.$vendor);
//        $bar->advance();

        // Get the skeleton repo from the PHP League
        $this->info('Downloading skeleton...');
        $this->helper->download($zipFile = $this->helper->makeFilename(), 'http://github.com/michaeljberry/skeleton/archive/master.zip')
             ->extract($zipFile, $path)
             ->cleanUp($zipFile);
        rename($path . 'skeleton-master', $fullPath);
        $bar->advance();

        // Creating a Laravel Service Provider in the src directory
        $this->info('Creating service provider...');
        $newProvider = $fullPath. DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR .$name.'ServiceProvider.php';
        $this->helper->replaceAndSave(
            \Config::get('packager.service_provider_stub', __DIR__.'/ServiceProvider.stub'),
            ['{{vendor}}', '{{name}}'],
            [$vendor, $name],
            $newProvider
        );
        $bar->advance();

        // Replacing skeleton placeholders
        $this->info('Replacing skeleton placeholders...');
        $this->helper->replaceAndSave($fullPath. DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'SkeletonClass.php', 'namespace 
    League\Skeleton;', 'namespace '.$vendor.'\\'.$name.';');
        $search =   [
            ':vendorup',
            ':package_nameup',
            ':vendor',
            ':package_name',
            ':vendor\\\\:package_name\\\\',
            ':vendor/:package_name',
            'thephpleague/:package_name',
            'league/:package_name',
            '"php"',
            'League\\\\Skeleton\\\\',
            'League\\\\Skeleton\\\\Test\\\\'
        ];
        $replace =  [
            $vendor,
            $name,
            strtolower($vendor),
            strtolower($name),
            $vendor.'\\\\'.$name.'\\\\',
            $vendor.'/'.$name,
            $vendor.'/'.$name,
            $vendor.'/'.$name,
            $requireSupport,
            $vendor.'\\\\'.$name.'\\\\',
            $vendor.'\\\\'.$name.'\\\\Test\\\\'
        ];
        $this->helper->replaceAndSave($fullPath . DIRECTORY_SEPARATOR . 'composer.json', $search, $replace);
        if ($this->option('i')) {
            $this->interactiveReplace($vendor, $name, $fullPath);
        }
        $bar->advance();

        // Finished creating the package, end of the progress bar
        $bar->finish();
        $this->info('Package created successfully!');
        $this->output->newLine(2);
        $bar = null;

        // Composer dump-autoload to identify new MyPackageServiceProvider
        $this->helper->dumpAutoloads();
    }

    protected function interactiveReplace($vendor, $name, $fullPath)
    {
        $author = $this->ask('Who is the author?', \Config::get('packager.author'));
        $authorEmail = $this->ask('What is the author\'s e-mail?', \Config::get('packager.author_email'));
        $authorSite = $this->ask('What is the author\'s website?', \Config::get('packager.author_site'));
        $description = $this->ask('How would you describe the package?');
        $license = $this->ask('Under which license will it be released?', \Config::get('packager.license'));
        $homepage = $this->ask('What is going to be the package website?', 'https://github.com/'.$vendor.'/'.$name);

        $search =   [
                ':author_name',
                ':author_email',
                ':author_website',
                ':package_description',
                'MIT',
                'https://github.com/'.$vendor.'/'.$name,
            ];
        $replace =  [
            $author,
            $authorEmail,
            $authorSite,
            $description,
            $license,
            $homepage,
        ];
        $this->helper->replaceAndSave($fullPath.'/composer.json', $search, $replace);
    }
}
