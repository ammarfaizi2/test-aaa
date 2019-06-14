<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call('UsersTableSeeder');
        DB::table('users')->insert(
        	[
        		'username' => 'ammarfaizi2',
        		'token' => '1LQxW0CjRz8ZaY1GvOxoCuHlNS7oecmQxEYJ4V/Fpd+WmfeUOwRVhw==',
        		'created_at' => date('Y-m-d H:i:s')
        	]
        );
    }
}
