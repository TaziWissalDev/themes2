<?php

namespace Caffeinated\Themes\Console;

use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateTheme extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:theme {slug} {from} {--quick}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new starter theme.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $options     = $this->getOptions();

        $root        = base_path('themes');
        $stubsPath   = __DIR__ . '/../../resources/stubs/theme';
        $slug        = $options['slug'];
        $from        = $options['from'];
        $name        = $this->format($slug);
        // $var = $this->format($from);

        if (File::isDirectory($root . '/' . $name)) {
            return $this->error('Theme already exists!');
        }

        if (!File::isDirectory($root)) {
            File::makeDirectory($root);
        }

        foreach (File::allFiles($stubsPath) as $file) {
            $contents = $this->replacePlaceholders($file->getContents(), $options);
            $subPath  = $file->getRelativePathname();
            $filePath = $root . '/' . $options['name'] . '/' . $subPath;
            $dir      = dirname($filePath);

            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            File::put($filePath, $contents, $options);
        }

        $this->cloneTheme($slug, $from, $options);

        $this->info("Theme created successfully.");
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        $slug   = Str::slug($this->argument('slug'));
        $from   = Str::slug($this->argument('from'));
        $name   = $this->format($slug);
        $from   = $this->format($from);
        $quick  = $this->option('quick');
        $vendor = config('themes.vendor');
        $author = config('themes.author');

        return [
            'slug'              => $slug,
            'from'              => $from,
            'namespace'         => "Themes\\$name",
            'escaped_namespace' => "Themes\\\\$name",
            'name'              => $quick ? $name : $this->ask('What is your theme\'s name?', $name),
            'app_name'          => $quick ? $name : $this->ask('What is your App name?', "Globale academy"),
            'database_name'              => $quick ? $name : $this->ask('What is your database name ?', "globalesecurite2"),
            'app_prefix'              => $quick ? $name : $this->ask('What is your app prefix  ?', "gs21"),
            'author'            => $quick ? $author : $this->ask('Who is the author of your theme?', $author),
            'version'           => $quick ? '1.0.0' : $this->ask('What is the version of your theme?', '1.0.0'),
            'description'       => $quick ? "$name theme." : $this->ask('Can you describe your theme?', "$name theme."),
            'package_name'      => $quick ? "{$vendor}/{$slug}" : $this->ask('What is the composer package name? [optional]', "{$vendor}/{$slug}")
        ];
    }

    /**
     * Replace placeholders with actual content.
     *
     * @param $contents
     * @param $options
     * @return mixed
     */
    protected function replacePlaceholders($contents, $options)
    {
        $find = [
            'DummyNamespace',
            'DummyEscapedNamespace',
            'DummyName',
            'DummySlug',
            'DummyVersion',
            'DummyDescription',
            'DummyPackageName',
            'DummyAuthor',
        ];

        $replace = [
            $options['namespace'],
            $options['escaped_namespace'],
            $options['name'],
            $options['slug'],
            $options['version'],
            $options['description'],
            $options['package_name'],
            $options['author'],
        ];

        return str_replace($find, $replace, $contents);
    }

    /**
     * Format the given name as the directory basename.
     *
     * @param  string  $name
     * @return string
     */
    private function format($name)
    {
        return ucfirst(Str::camel($name));
    }
    // function to copy folders from anothet theme
    protected function cloneTheme($slug, $from, $options)
    {
        $ressource_path = base_path('themes/' . $slug . '/resources');
        $public_path = base_path('themes/' . $slug . '/resources');
        $storage_path = base_path('themes/' . $slug . '/resources');
        $config_path = base_path('themes/' . $slug . '/resources');
        $slug = Str::ucfirst($slug);

        if (file_exists($ressource_path)) {
            File::deleteDirectory($ressource_path);
            File::copyDirectory(base_path('themes/' . $from . '/resources'), base_path('themes/' . $slug . '/resources'));
        } else {
            File::copyDirectory(base_path('themes/' . $from . '/resources'), base_path('themes/' . $slug . '/resources'));
        }
        if (file_exists($public_path)) {
            File::deleteDirectory($public_path);
            File::copyDirectory(base_path('themes/' . $from . '/public'), base_path('themes/' . $slug . '/public'));
        } else {
            File::copyDirectory(base_path('themes/' . $from . '/public'), base_path('themes/' . $slug . '/public'));
        }
        if (file_exists($storage_path)) {
            File::deleteDirectory($storage_path);
            File::copyDirectory(base_path('themes/' . $from . '/storage'), base_path('themes/' . $slug . '/storage'));
        } else {
            File::copyDirectory(base_path('themes/' . $from . '/storage'), base_path('themes/' . $slug . '/storage'));
        }
        if (file_exists($config_path)) {
            File::deleteDirectory($config_path);
            File::copyDirectory(base_path('themes/' . $from . '/Config'), base_path('themes/' . $slug . '/Config'));
        } else {
            File::copyDirectory(base_path('themes/' . $from . '/Config'), base_path('themes/' . $slug . '/Config'));
        }

        $json_path =  theme_path('theme.json', $slug);

        // if (file_exists($json_path)) {
        //     File::delete($json_path);
        //     File::copyDirectory(theme_path('theme.json', $from), theme_path('theme.json', $slug));
        // }
        $theme_json = json_decode(File::get(theme_path('theme.json', $slug)));;
        $theme_json->APP_NAME = $options['app_name'];
        $theme_json->DB_DATABASE = $options['database_name'];
        $theme_json->APP_PREFIX = $options['app_prefix'];
        $theme_json = File::put(theme_path('theme.json', $slug), json_encode($theme_json));
    }
}
