# Auth Driver

#### Developing Locally without an LDAP connection

You can continue to develop and login to your application without a
connection to your LDAP server in the following scenario:

* You have `auto_connect` set to `false` in your `ldap.php` configuration
 > This is necessary so we don't automatically try and bind to your LDAP server when your application boots.

* You have `login_fallback` set to `true` in your `ldap_auth.php` configuration
 > This is necessary so we fallback to the standard `eloquent` auth driver.

* You have `password_sync` set to `true` in your `ldap_auth.php` configuration
 > This is necessary so we can login to the account with the last password that was used when an LDAP connection was present.

* You have logged into the synchronized LDAP account previously
 > This is necessary so the account actually exists in your local app's database.

If you have this configuration, you will have no issues developing an
application without a persistent connection to your LDAP server.