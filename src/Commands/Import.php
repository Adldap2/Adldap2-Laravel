<?php

namespace Adldap\Laravel\Commands;

use Adldap\Laravel\Traits\ImportsUsers;
use Adldap\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Console\Command;

class Import extends Command
{
    use ImportsUsers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adldap:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports users into the local database with a random 16 character hashed password.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Retrieve the Adldap instance.
        $adldap = $this->getAdldap();

        if (!$adldap->getConnection()->isBound()) {
            // If the connection isn't bound yet, we'll connect to the server manually.
            $adldap->connect();
        }

        // Retrieve all users.
        $users = $adldap->search()->users()->get();

        $imported = $this->import($users);

        $this->info("Successfully imported {$imported} user(s).");
    }

    /**
     * Imports the specified users and returns the total
     * number of users successfully imported.
     *
     * @param mixed $users
     *
     * @return int
     */
    public function import($users = [])
    {
        $imported = 0;

        foreach ($users as $user) {
            if ($user instanceof User) {
                try {
                    // Import the user and then save the model.
                    $model = $this->getModelFromAdldap($user);

                    $this->saveModel($model);

                    $imported++;

                    // Log the successful import.
                    Log::info("Imported user {$user->getCommonName()}");
                } catch (\Exception $e) {
                    // Log the unsuccessful import.
                    Log::error("Unable to import user {$user->getCommonName()}. {$e->getMessage()}");
                }
            }
        }

        return $imported;
    }

    /**
     * {@inheritdoc}
     */
    public function createModel()
    {
        $model = Auth::getProvider()->getModel();

        return new $model();
    }
}
