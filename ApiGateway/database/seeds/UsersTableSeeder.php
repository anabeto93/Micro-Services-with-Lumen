<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(app()->environment('local')) {
            Log::info('Loading Users on Local Env');
            factory(App\Models\User::class, 2)->create()->each(function ($user) {
                Log::info('Created User'); Log::debug($user);
            });

            //create passport upon reseed
            \Illuminate\Support\Facades\Artisan::call('passport:install');
        }
    }
}
