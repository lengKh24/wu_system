<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(500)->create();
        User::factory()->create([
            'name'  => 'Admin System',
            'email' => 'admin@system.me',
        ]);
        User::factory()->create([
            'name'     => 'User Test',
            'email'    => 'test@gmail.com',
            'password' => Hash::make('12345678'),
        ]);

        $this->call([
            LocationSeeder::class,
            StructureSeeder::class,
            TermSeeder::class,
            StudentSeeder::class,
            ExamStateSeeder::class,
            ExamTypeSeeder::class,
            PermissionSeeder::class,
        ]);
    }
}
