<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create 
                            {email? : Email address for the new administrator}
                            {--name= : Name of the administrator}
                            {--password= : Password for the administrator}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new administrator user or update an existing user with admin privileges';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Enter admin email');
        $email = trim($email);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Please provide a valid email address.');
            return Command::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $this->info("User [{$email}] already exists. Promoting to admin...");
            $user->role = 'admin';
            $user->status = 'active';

            $password = $this->option('password');
            if ($password) {
                $user->password = Hash::make($password);
            } elseif ($this->confirm('Do you want to change the password for this user?')) {
                $password = $this->secret('Enter new password');
                if ($password) {
                    $user->password = Hash::make($password);
                }
            }

            $user->save();
            $this->info("User [{$email}] is now an active ADMINISTRATOR!");
            return Command::SUCCESS;
        }

        $name = $this->option('name') ?? $this->ask('Enter admin name', 'Admin');
        $password = $this->option('password') ?? $this->secret('Enter password (min 8 chars)');

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long.');
            return Command::FAILURE;
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        $this->info("Administrator [{$user->email}] successfully created with ID #{$user->id}!");
        return Command::SUCCESS;
    }
}

