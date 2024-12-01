<?php

namespace Eighty8\LaravelSeeder\Command;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;

class SeedRefresh extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'seed:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and re-run all seeders';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $env = $this->input->getOption('env');
        $force = $this->input->getOption('force');
        $database = $this->input->getOption('database');
        $database_name = $this->input->getOption('database-name');

        $this->call('seed:reset', [
            '--database-name' => $database_name,
            '--database' => $database,
            '--force' => $force,
            '--env' => $env,
        ]);

        // The refresh command is essentially just a brief aggregate of a few other of
        // the migration commands and just provides a convenient wrapper to execute
        // them in succession. We'll also see if we need to re-seed the database.
        $this->call('seed', [
            '--database-name' => $database_name,
            '--database' => $database,
            '--force' => $force,
            '--env' => $env,
        ]);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['env', null, InputOption::VALUE_OPTIONAL, 'The environment to use for the seeders.'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['database-name', null, InputOption::VALUE_OPTIONAL, 'The database name to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
        ];
    }
}
