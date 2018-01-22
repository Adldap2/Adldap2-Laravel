<?php

namespace Adldap\Laravel\Commands\Console;

use Exception;
use Adldap\Models\User;
use Adldap\Laravel\Events\Imported;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Laravel\Commands\SyncPassword;
use Adldap\Laravel\Commands\Import as ImportUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;

class Import extends Command
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adldap:import {user? : The specific user to import.}
            {--f|filter= : The raw LDAP filter for limiting users imported.}
            {--d|delete : Soft-delete the users model if their LDAP account is disabled.}
            {--r|restore : Restores soft-deleted models if their LDAP account is enabled.}
            {--no-log : Disables logging successful and unsuccessful imports.}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Imports LDAP users into the local database with a random 16 character hashed password.';

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws \Adldap\Models\ModelNotFoundException
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
    public function import(array $users = []) : int
    {
        $imported = 0;

        $this->output->progressStart(count($users));

        foreach ($users as $user) {
            try {
                // Get the users credentials array.
                $credentials = $this->getUserCredentials($user);

                // Import the user and retrieve it's model.
                $model = Bus::dispatch(
                    new ImportUser($user, $this->model(), $credentials)
                );

                // Set the users password.
                Bus::dispatch(new SyncPassword($model));

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
    public function isLogging() : bool
    {
        return !$this->option('no-log');
    }

    /**
     * Returns true / false if users are being deleted
     * if their account is disabled in LDAP.
     *
     * @return bool
     */
    public function isDeleting() : bool
    {
        return $this->option('delete') == 'true';
    }

    /**
     * Returns true / false if users are being restored
     * if their account is enabled in LDAP.
     *
     * @return bool
     */
    public function isRestoring() : bool
    {
        return $this->option('restore') == 'true';
    }

    /**
     * Retrieves users to be imported.
     *
     * @return array
     *
     * @throws \Adldap\Models\ModelNotFoundException
     */
    public function getUsers() : array
    {
        /** @var \Adldap\Query\Builder $query */
        $query = Resolver::query();

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
    protected function getUserCredentials(User $user) : array
    {
        $resolver = Resolver::getFacadeRoot();

        $username = $user->getFirstAttribute($resolver->getLdapDiscoveryAttribute());

        return [
            $resolver->getEloquentUsernameAttribute() => $username,
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
    protected function save(User $user, Model $model) : bool
    {
        $imported = false;

        if ($model->save() && $model->wasRecentlyCreated) {
            $imported = true;

            Event::fire(new Imported($user, $model));

            // Log the successful import.
            if ($this->isLogging()) {
                logger()->info("Imported user {$user->getCommonName()}");
            }
        }

        return $imported;
    }

    /**
     * Restores soft-deleted models if their LDAP account is enabled.
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
            // If the model has soft-deletes enabled, the model is
            // currently deleted, and the LDAP user account
            // is enabled, we'll restore the users model.
            $model->restore();

            if ($this->isLogging()) {
                $type = get_class($user->getSchema());

                logger()->info("Restored user {$user->getCommonName()}. Their {$type} user account has been re-enabled.");
            }
        }
    }

    /**
     * Soft deletes the specified model if their LDAP account is disabled.
     *
     * @param User  $user
     * @param Model $model
     *
     * @return void
     *
     * @throws Exception
     */
    protected function delete(User $user, Model $model)
    {
        if (
            $this->isUsingSoftDeletes($model) &&
            ! $model->trashed() &&
            $user->isDisabled()
        ) {
            // If deleting is enabled, the model supports soft deletes, the model
            // isn't already deleted, and the LDAP user is disabled, we'll
            // go ahead and delete the users model.
            $model->delete();

            if ($this->isLogging()) {
                $type = get_class($user->getSchema());

                logger()->info("Soft-deleted user {$user->getCommonName()}. Their {$type} user account is disabled.");
            }
        }
    }

    /**
     * Create a new instance of the configured authentication model.
     *
     * @return Model
     */
    protected function model() : Model
    {
        return Auth::getProvider()->createModel();
    }

    /**
     * Returns true / false if the model is using soft deletes
     * by checking if the model contains a `trashed` method.
     *
     * @param Model $model
     *
     * @return bool
     */
    protected function isUsingSoftDeletes(Model $model) : bool
    {
        return method_exists($model, 'trashed');
    }
}
