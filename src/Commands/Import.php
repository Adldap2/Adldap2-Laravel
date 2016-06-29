<?php

namespace Adldap\Laravel\Commands;

use Adldap\Laravel\Traits\ImportsUsers;
use Adldap\Models\User;
use Illuminate\Console\Command;

class Import extends Command
{
    use ImportsUsers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adldap:import
                            {user?}
                            {--log=true : Log successful and unsuccessful imported users.}';

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

        $user = $this->argument('user');

        if ($user) {
            $users = [$adldap->search()->users()->findOrFail($user)];
        } else {
            // Retrieve all users.
            $users = $adldap->search()->users()->get();
        }

        $this->info("Successfully imported {$this->import($users)} user(s).");
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

                    if ($this->saveModel($model) && $model->wasRecentlyCreated) {
                        // Only increment imported for new models.
                        $imported++;

                        // Log the successful import.
                        if ($this->option('log') == 'true') {
                            logger()->info("Imported user {$user->getCommonName()}");
                        }
                    }
                } catch (\Exception $e) {
                    $message = "Unable to import user {$user->getCommonName()}. {$e->getMessage()}";

                    // Log the unsuccessful import.
                    if ($this->option('log') == 'true') {
                        logger()->error($message);
                    }
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
        $model = auth()->getProvider()->getModel();

        return new $model();
    }
}
