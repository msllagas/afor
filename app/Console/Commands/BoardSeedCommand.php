<?php

namespace App\Console\Commands;

use App\Models\Board;
use Database\Seeders\BoardSeeder;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class BoardSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example: php artisan board:seed 0199c426-3231-7218-9fc2-20fb0ad98f5f --class=BoardSeeder
     *
     * @var string
     */
    protected $signature = 'board:seed
                            {board : The ID of the Board to seed}
                            {--class= : The seeder class to run}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed data related to a specific board';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $boardId = $this->argument('board');
        $board = Board::query()->find($boardId);

        if (! $board) {
            $this->error("Board with ID {$boardId} not found.");
            return self::FAILURE;
        }

        $className = $this->input->getOption('class') ?? BoardSeeder::class;

        if (! class_exists($className)) {
            $this->error("Seeder class {$className} does not exist.");
            return self::FAILURE;
        }
        $this->info("Seeding board #{$board->id} ({$board->name}) using [{$className}]...");

        $seeder = $this->laravel->make($className);

          $seeder->setContainer($this->laravel)
               ->setCommand($this)
               ->__invoke();

        $this->info("✅ Board seeded successfully.");

        return self::SUCCESS;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the seeder', BoardSeeder::class],
        ];
    }
}
