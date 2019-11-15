
<?php

namespace Adldap\Laravel\Traits;

use Adldap\Laravel\Commands\Import as ImportUser;
use Adldap\Laravel\Commands\SyncPassword;
use Adldap\Laravel\Events\Imported;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Models\User;
use Adldap\Query\Builder;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use UnexpectedValueException;

trait ImportUser {

    private $model;

    public function getUsers() : array
    {
        /** @var Builder $query */
        $query = Resolver::query();

        // Retrieve all users. We'll paginate our search in case we
        // hit the 1000 record hard limit of active directory.
        $users = $query->paginate()->getResults();

        // We need to filter our results to make sure they are
        // only users. In some cases, Contact models may be
        // returned due the possibility of them
        // existing in the same scope.
        return array_filter($users, function ($user) {
            return $user instanceof User;
        });
    }

    public function getUser($user) : User
    {
        /** @var Builder $query */
        $query = Resolver::query();

        $user = $query->findOrFail($user);

        // We need to filter our results to make sure they are
        // only users. In some cases, Contact models may be
        // returned due the possibility of them
        // existing in the same scope.
        return $user;
    }

    public function display(array $users = [])
    {
        $headers = ['Name', 'Account Name', 'UPN'];

        $data = [];

        array_map(function (User $user) use (&$data) {
            $data[] = [
                'name'         => $user->getCommonName(),
                'account_name' => $user->getAccountName(),
                'upn'          => $user->getUserPrincipalName(),
            ];
        }, $users);

        return $data;
    }

    public function import(array $users = []) : int
    {
        $imported = 0;

        foreach ($users as $user) {
            try {
                // Import the user and retrieve it's model.
                $model = Bus::dispatch(
                    new ImportUser($user, $this->model())
                );

                // Set the users password.
                Bus::dispatch(new SyncPassword($model));

                // Save the returned model.
                $this->save($user, $model);

                $imported++;
            } catch (Exception $e) {
                // Log the unsuccessful import.
                logger()->error("Unable to import user {$user->getCommonName()}. {$e->getMessage()}");
            }
        }

        return $imported;
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
        if ($model->save() && $model->wasRecentlyCreated) {
            Event::dispatch(new Imported($user, $model));

            // Log the successful import.
            logger()->info("Imported user {$user->getCommonName()}");

            return true;
        }

        return false;
    }

    /**
     * Set and create a new instance of the eloquent model to use.
     *
     * @return Model
     */
    protected function model() : Model
    {
        if (! $this->model) {
            $this->model = config('ldap_auth.model') ?: $this->determineModel();
        }

        return new $this->model();
    }

    protected function determineModel()
    {
        // Retrieve all of the configured authentication providers that
        // use the LDAP driver and have a configured model.
        $providers = Arr::where(config('auth.providers'), function ($value, $key) {
            return $value['driver'] == 'ldap' && array_key_exists('model', $value);
        });

        // Pull the first driver and return a new model instance.
        if ($ldap = reset($providers)) {
            return $ldap['model'];
        }

        throw new UnexpectedValueException(
            'Unable to retrieve LDAP authentication driver. Did you forget to configure it?'
        );
    }

}
