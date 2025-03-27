<?php

namespace Jakyeru\Larascord\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larascord:install
                            {--composer=global : Absolute path to the Composer binary which should be used to install packages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use this command to install Larascord.';

    /*
     * The Discord application's client id.
     *
     * @var string|null
     */
    private ?string $clientId;

    /*
     * The Discord application's client secret.
     *
     * @var string|null
     */
    private ?string $clientSecret;

    /*
     * The route prefix.
     *
     * @var string|null
     */
    private ?string $prefix;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Getting the user's input
        $this->clientId = $this->ask('What is your Discord application\'s client id?');
        $this->clientSecret = $this->ask('What is your Discord application\'s client secret?');
        $this->prefix = $this->ask('What route prefix should Larascord use?', 'larascord');
        
        // Validating the user's input
        try {$this->validateInput();} catch (\Exception $e) {$this->error($e->getMessage()); return;}

        // Appending the secrets to the .env file
        $this->info('Appending the secrets to the .env file...');
        $this->appendToEnvFile();

        // Creating the user migration files
        $this->info('Creating the user migration files...');
        $this->createUserMigrationFiles();

        // Create the model files
        $this->info('Creating the user model files...');
        $this->createModelFiles();

        // Create the event files
        $this->info('Creating the event files...');
        $this->createEventFiles();

        // Asking the user to build the assets
        if ($this->confirm('Do you want to build the assets?', true)) {
            try {
                shell_exec('npm install --silent');
                shell_exec('npm run build --silent');
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                $this->comment('Please execute the "npm install && npm run build" command to build your assets.');
            }
        } else {
            $this->comment('Please execute the "npm install && npm run build" command to build your assets.');
        }

        // Asking the user to migrate the database
        if ($this->confirm('Do you want to run the migrations? This will delete all the data in the database.', true)) {
            try {
                $this->call('migrate:reset');
                $this->call('migrate:fresh');
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                $this->comment('You can run the migrations later by running the command:');
                $this->comment('php artisan migrate');
            }
        } else {
            $this->comment('You can run the migrations later by running the command:');
            $this->comment('php artisan migrate');
        }

        // Automatically publishing the configuration file
        $this->info('Publishing the configuration file...');
        $this->call('larascord:publish');

        $this->alert('Please make sure you add "' . env('APP_URL', 'http://localhost:8000') . '/' . env('LARASCORD_PREFIX', 'larascord') . '/callback' . '" to your Discord application\'s redirect urls in the OAuth2 tab.');
        $this->warn('If the domain doesn\'t match your current environment\'s domain you need to set it manually in the .env file. (APP_URL)');

        $this->info('Larascord has been successfully installed!');
    }

    /**
     * Validate the user's input.
     *
     * @throws \Exception
     */
    protected function validateInput(): void
    {
        $rules = [
            'clientId' => ['required', 'numeric'],
            'clientSecret' => ['required', 'string'],
            'prefix' => ['required', 'string'],
        ];

        $validator = Validator::make([
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'prefix' => $this->prefix,
        ], $rules);

        $validator->validate();
    }

    /**
     * Append the secrets to the .env file.
     */
    protected function appendToEnvFile(): void
    {

        (new Filesystem())->append('.env',PHP_EOL);

        (new Filesystem())->append('.env',PHP_EOL);
        (new Filesystem())->append('.env','LARASCORD_CLIENT_ID='.$this->clientId);

        (new Filesystem())->append('.env',PHP_EOL);
        (new Filesystem())->append('.env','LARASCORD_CLIENT_SECRET='.$this->clientSecret);

        (new Filesystem())->append('.env',PHP_EOL);
        (new Filesystem())->append('.env','LARASCORD_GRANT_TYPE=authorization_code');

        (new Filesystem())->append('.env',PHP_EOL);
        (new Filesystem())->append('.env','LARASCORD_PREFIX='.$this->prefix);

        (new Filesystem())->append('.env',PHP_EOL);
        (new Filesystem())->append('.env','LARASCORD_SCOPE=identify&email');
    }

    /**
     * Create the user migration files.
     */
    public function createUserMigrationFiles(): void
    {
        (new Filesystem())->ensureDirectoryExists(database_path('migrations'));
        (new Filesystem())->copyDirectory(__DIR__ . '/../../database/migrations/', database_path('migrations/'));
    }

    /**
     * Create the user model files.
     */
    public function createModelFiles(): void
    {
        (new Filesystem())->ensureDirectoryExists(app_path('Models'));
        (new Filesystem())->copy(__DIR__ . '/../../Models/User.php', app_path('Models/User.php'));
    }

    /**
     * Create the event files.
     */
    public function createEventFiles(): void
    {
        (new Filesystem())->ensureDirectoryExists(app_path('Events'));
        (new Filesystem())->copyDirectory(__DIR__ . '/../../Events/', app_path('Events/'));
    }
}