<?php

namespace Database\Seeders;


// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'eureka',
            'email' => 'eureka@hana-ci.com',
            'password' => bcrypt(env('HANA_UNIQUE_KEY')),
        ]);
    }
}
