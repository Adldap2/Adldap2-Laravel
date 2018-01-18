<?php

namespace Adldap\Laravel\Commands;

use Exception;
use Adldap\Models\User;
use Adldap\Laravel\Traits\UsesAdldap;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Import extends Command
{
    use UsesAdldap;

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

            ['delete', '-d', InputOption::VALUE_NONE, 'Soft-delete the users model if their AD account is disabled.'],

            ['restore', '-r', InputOption::VALUE_NONE, 'Restores soft-deleted models if their AD account is enabled.'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $users = $this->getUsers();

        $count = count($users);

        if ($count === 1) {
            $this->info("Found user '{$users[0]->getCommonName()}'.");
        } else {
            $this->info("Found {$count} user(s).");
        }

        if ($this->confirm('Would you like to display the user(s) to be imported / synchronized?', $default = false)) {
            $this->display($users);
        }

        if ($this->confirm('Would you like these users to be imported / synchronized?', $default = true)) {
            $imported = $this->import($users);

            $this->info("Successfully imported / synchronized {$imported} user(s).");
        } else {
            $this->info("Okay, no users were imported / synchronized.");
        }
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

        $this->output->progressStart(count($users));

        foreach ($users as $user) {
            try {
                // Get the users credentials array.
                $credentials = $this->getUserCredentials($user);

                // Import the user and retrieve it's model.
                $model = $this->getImporter()->run($user, $this->model(), $credentials);

                // Only set a new password if we are creating a new user.
                if (!isset($model->password) || empty($model->password)) {
                    $password = str_random();

                    // Set the models password.
                    $model->password = $model->hasSetMutator('password') ?
                        $password : bcrypt($password);
                }

                // Save the returned model.
                $this->save($user, $model);

                if ($this->isDeleting()) {
                    $this->delete($user, $model);
                }
                
                if ($this->isRestoring()) {
                    $this->restore($user, $model);
                }

                $imported++;
            } catch (Exception $e) {
                // Log the unsuccessful import.
                if ($this->isLogging()) {
                    logger()->error("Unable to import user {$user->getCommonName()}. {$e->getMessage()}");
                }
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        return $imported;
    }

    /**
     * Displays the given users in a table.
     *
     * @param array $users
     *
     * @return void
     */
    public function display(array $users = [])
    {
        $headers = ['Name', 'Account Name', 'UPN'];

        $data = [];

        array_map(function (User $user) use (&$data) {
            $data[] = [
                'name' => $user->getCommonName(),
                'account_name' => $user->getAccountName(),
                'upn' => $user->getUserPrincipalName(),
            ];
        }, $users);

        $this->table($headers, $data);
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
     * Returns true / false if users are being deleted
     * if their account is disabled in AD.
     *
     * @return bool
     */
    public function isDeleting()
    {
        return $this->option('delete') == 'true';
    }

    /**
     * Returns true / false if users are being restored
     * if their account is enabled in AD.
     *
     * @return bool
     */
    public function isRestoring()
    {
        return $this->option('restore') == 'true';
    }

    /**
     * Retrieves users to be imported.
     *
     * @return array
     */
    public function getUsers()
    {
        $query = $this->getResolver()->query();

        if ($filter = $this->option('filter')) {
            // If the filter option was given, we'll
            // insert it into our search query.
            $query->rawFilter($filter);
        }

        if ($user = $this->argument('user')) {
            $users = [$query->findOrFail($user)];
        } else {
            // Retrieve all users. We'll paginate our search in case we
            // hit the 1000 record hard limit of active directory.
            $users = $query->paginate()->getResults();
        }

        // We need to filter our results to make sure they are
        // only users. In some cases, Contact models may be
        // returned due the possibility of them
        // existing in the same scope.
        return array_filter($users, function ($user) {
            return $user instanceof User;
        });
    }

    /**
     * Returns the specified users credentials array.
     *
     * @param User $user
     *
     * @return array
     */
    protected function getUserCredentials(User $user)
    {
        $username = $user->getFirstAttribute($this->getResolver()->getLdapUsername());

        return [
            $this->getResolver()->getEloquentUsername() => $username,
        ];
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

        if ($model->save() && $model->wasRecentlyCreated) {
            $imported = true;

            // Log the successful import.
            if ($this->isLogging()) {
                logger()->info("Imported user {$user->getCommonName()}");
            }
        }

        return $imported;
    }

    /**
     * Restores soft-deleted models if their AD account is enabled.
     *
     * @param User  $user
     * @param Model $model
     *
     * @return void
     */
    protected function restore(User $user, Model $model)
    {
        if (
            $this->isUsingSoftDeletes($model) &&
            $model->trashed() &&
            $user->isEnabled()
        ) {
            // If the model has soft-deletes enabled, the model is currently deleted, and the
            // AD user account is enabled, we'll restore the users model.
            $model->restore();

            if ($this->isLogging()) {
                logger()->info("Restored user {$user->getCommonName()}. Their AD user account has been re-enabled.");
            }
        }
    }

    /**
     * Soft deletes the specified model if their AD account is disabled.
     *
     * @param User  $user
     * @param Model $model
     *
     * @return void
     */
    protected function delete(User $user, Model $model)
    {
        if (
            $this->isUsingSoftDeletes($model) &&
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

    /**
     * Create a new instance of the configured authentication model.
     *
     * @return Model
     */
    protected function model()
    {
        return auth()->getProvider()->createModel();
    }

    /**
     * Returns true / false if the model is using soft deletes.
     *
     * @param Model $model
     *
     * @return bool
     */
    protected function isUsingSoftDeletes(Model $model)
    {
        return method_exists($model, 'trashed');
    }
}
