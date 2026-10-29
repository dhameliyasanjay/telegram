<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AssignAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:assign 
                            {email : The email of the user to assign or revoke admin role}
                            {--demote : Revoke admin role and set to regular user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign or revoke the admin role for a user by email';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = trim($this->argument('email'));
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email [{$email}] not found.");
            return Command::FAILURE;
        }

        $demote = $this->option('demote');
        $targetRole = $demote ? 'user' : 'admin';

        $user->role = $targetRole;
        $user->save();

        if ($demote) {
            $this->info("User [{$user->email}] is now a standard USER.");
        } else {
            $this->info("User [{$user->email}] has been successfully granted the ADMIN role!");
        }

        return Command::SUCCESS;
    }
}
