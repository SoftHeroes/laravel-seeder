<?php

namespace Eighty8\LaravelSeeder\Command;

use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;

class SeedRun extends AbstractSeedMigratorCommand
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database seeders';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        // Prepare the migrator.
        $this->prepareMigrator();

        // Execute the migrator.
        $pretend =  $this->input->getOption('pretend');
        if(!$pretend){
            $this->info('Seeding data for '.ucfirst($this->getEnvironment()).' environment...');
            $pretend = false;
            foreach($this->files as $path) {
                $this->migrator->runUp($path, 0, $pretend);
            }
            $this->info('Seeded data for '.ucfirst($this->getEnvironment()).' environment');
        }else {
            $this->info('Pretending to be seeding seeding data for '.ucfirst($this->getEnvironment()).' environment...');
            $pretend = true;
            foreach($this->files as $path) {
                $this->migrator->runUp($path, 0, $pretend);
            }
            $this->info('Pretended to be seeding data for '.ucfirst($this->getEnvironment()).' environment.');
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder'],
            ['env', null, InputOption::VALUE_OPTIONAL, 'The environment to use for the seeders.'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
        ];
    }
}
