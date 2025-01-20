<?php

namespace Eighty8\LaravelSeeder\Command;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class SeedStatus extends AbstractSeedMigratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'seed:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each seeder';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Connect to the seeder repository.
        $this->connectToRepository();

        // Resolve the environment.
        $this->resolveEnvironment();

        // Resolve the migration paths.
        $this->resolveMigrationPaths($this->argument('paths'));

        // Print the status of the seeders.
        $this->printStatus();
    }

    /**
     * Get the status for the given ran migrations.
     *
     * @param array $ran
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getStatusFor(array $ran)
    {
        return Collection::make($this->getAllMigrationFiles())
            ->map(function ($migration) use ($ran) {
                $migrationName = $this->migrator->getMigrationName($migration);

                return in_array($migrationName, $ran)
                    ? ['<info>Y</info>', $migrationName]
                    : ['<fg=red>N</fg=red>', $migrationName];
            });
    }

    /**
     * Get an array of all of the migration files.
     *
     * @return array
     */
    protected function getAllMigrationFiles()
    {
        return $this->migrator->getMigrationFiles($this->getMigrationPaths());
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['env', null, InputOption::VALUE_OPTIONAL, 'The environment to use for the seeders.'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['database-name', null, InputOption::VALUE_OPTIONAL, 'The database name to use.'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'The path of seeder files to use.'],
        ];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The paths to the seeders.', null],
        ];
    }

    /**
     * Prints the status of the seeders in the database.
     */
    protected function printStatus(): void
    {
        $ran = $this->migrator->getRepository()->getRan();

        if (count($migrations = $this->getStatusFor($ran)) > 0) {
            $this->table(['Ran?', 'Seeder'], $migrations);
        } else {
            $this->error('No seeders found');
        }
    }
}
