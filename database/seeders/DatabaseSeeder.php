<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            VenueSeeder::class,
            ClubDemoSeeder::class,
            ScoringDemoSeeder::class,
        ]);

        if ($this->command) {
            $this->command->newLine();
            $this->command->info('Demo accounts seeded (password: 12345678)');
            $this->command->table(
                ['Panel', 'Email', 'URL'],
                [
                    ['Admin', 'admin@cricket-os.test', url('/admin/login')],
                    ['Club', 'club@cricket-os.test', url('/club/login')],
                    ['Player', 'oliver.clarke@greenwoodrangers.test', 'API / mobile login'],
                ]
            );
        }
    }
}
