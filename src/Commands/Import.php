<?php

namespace Adldap\Laravel\Commands;

use Adldap\Models\User;
use Adldap\Laravel\Traits\ImportsUsers;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Import extends Command
{
    use ImportsUsers;

    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'adldap:import';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Imports LDAP users into the local database with a random 16 character hashed password.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Retrieve the Adldap instance.
        $adldap = $this->getAdldap($this->option('connection'));

        if (!$adldap->getConnection()->isBound()) {
            // If the connection isn't bound yet, we'll connect to the server manually.
            $adldap->connect();
        }

        // Generate a new user search.
        $search = $adldap->search()->users();

        if ($filter = $this->getFilter()) {
            // If the filter option was given, we'll
            // insert it into our search query.
            $search->rawFilter($filter);
        }

        if ($user = $this->argument('user')) {
            $users = [$search->findOrFail($user)];

            $this->info("Found user '{$users[0]->getCommonName()}'. Importing...");
        } else {
            // Retrieve all users. We'll paginate our search in case we hit
            // the 1000 record hard limit of active directory.
            $users = $search->paginate()->getResults();

            $count = count($users);

            $this->info("Found {$count} user(s). Importing...");
        }

        $this->info("\nSuccessfully imported / synchronized {$this->import($users)} user(s).");
    }

    /**
     * Imports the specified users and returns the total
     * number of users successfully imported.
     *
     * @param array $users
     *
     * @return int
     */
    public function import(array $users = [])
    {
        $imported = 0;

        // We need to filter our results to make sure they are
        // only users. In some cases, Contact models may be
        // returned due the possibility of the
        // existing in the same scope.
        $users = collect($users)->filter(function($user) {
            return $user instanceof User;
        });

        $bar = $this->output->createProgressBar(count($users));

        foreach ($users as $user) {
            try {
                // Import the user and retrieve it's model.
                $model = $this->getModelFromAdldap($user);

                // Save the returned model.
                $this->save($user, $model);

                if ($this->isDeleting()) {
                    $this->delete($user, $model);
                }

                $imported++;
            } catch (\Exception $e) {
                // Log the unsuccessful import.
                if ($this->isLogging()) {
                    logger()->error("Unable to import user {$user->getCommonName()}. {$e->getMessage()}");
                }
            }

            $bar->advance();
        }

        return $imported;
    }

    /**
     * Returns true / false if the current import is being logged.
     *
     * @return bool
     */
    public function isLogging()
    {
        return $this->option('log') == 'true';
    }

    /**
     * Returns true / false if users are being deleted if they are disabled in AD.
     *
     * @return bool
     */
    public function isDeleting()
    {
        return $this->option('delete') == 'true';
    }

    /**
     * Returns the limitation filter for the user query.
     *
     * @return string
     */
    public function getFilter()
    {
        return $this->getLimitationFilter() ?: $this->option('filter');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    public function getArguments()
    {
        return [
            ['user', InputArgument::OPTIONAL, 'The specific user to import using ANR.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            ['filter', '-f', InputOption::VALUE_OPTIONAL, 'The raw filter for limiting users imported.'],

            ['log', '-l', InputOption::VALUE_OPTIONAL, 'Log successful and unsuccessful imported users.', 'true'],

            ['connection', '-c', InputOption::VALUE_OPTIONAL, 'The LDAP connection to use to import users.'],

            ['delete', '-d', InputOption::VALUE_OPTIONAL, 'Soft-delete the users model if the AD user is disabled.', 'false'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createModel()
    {
        $model = auth()->getProvider()->getModel();

        return new $model();
    }

    /**
     * Saves the specified user with its model.
     *
     * @param User  $user
     * @param Model $model
     *
     * @return bool
     */
    protected function save(User $user, Model $model)
    {
        $imported = false;

        if ($this->saveModel($model) && $model->wasRecentlyCreated) {
            $imported = true;

            // Log the successful import.
            if ($this->isLogging()) {
                logger()->info("Imported user {$user->getCommonName()}");
            }
        }

        return $imported;
    }

    /**
     * Soft deletes the specified model if the specified AD account is disabled.
     *
     * @param User  $user
     * @param Model $model
     */
    protected function delete(User $user, Model $model)
    {
        if (
            method_exists($model, 'trashed') &&
            ! $model->trashed() &&
            $user->isDisabled()
        ) {
            // If deleting is enabled, the model supports soft deletes, the model
            // isn't already deleted, and the AD user is disabled, we'll
            // go ahead and delete the users model.
            $model->delete();

            if ($this->isLogging()) {
                logger()->info("Soft-deleted user {$user->getCommonName()}. Their AD user account is disabled.");
            }
        }
    }
}
