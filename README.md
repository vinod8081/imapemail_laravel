# Codebank/Imapemail

Laravel Imapemail is package for email clients using Imap.

# Table of Contents
* [Team Members](#team-members)
* [Requirements](#requirements)
* [Getting Started](#getting-started)
* [Documentation](#documentation)
* [Roadmap](#roadmap)


# <a name="team-members"></a>Team Members

* Vinod Pawar (vinod.pawar@silicus.com)

# <a name="requirements"></a>Requirements

* This package requires following things 
* PHP 5.5+
* MySQL 5.5+
* Laravel 5.1

# <a name="getting-started"></a>Getting Started

1. On root level create a folder named as "packages".

2. Copy and past "Codebank/Imapemail" into "packages" folder.

3. Require the package in your 'composer.json' and update your dependency with 'composer update':
```php
	"psr-4": {
			"App\\": "app/",
			"Codebank\\Imapemail\\": "packages/Codebank/Imapemail/src/" 
		}
```
4. Add the package to your application service providers in 'config/app.php'.
```php
	'providers' => [
		Codebank\Imapemail\ImapemailServiceProvider::class,
	],
```
5. Publish the package migrations to your application and run these with `php artisan migrate.
	$ php artisan vendor:publish --provider="Codebank\Imapemail\ImapemailServiceProvider"

```
# <a name="documentation"></a>Documentation

```
1. Adding rules into 'app/Http/routs.php'. You have to specify module name as well as action name inside 'permission'  
```php	
	Route::get('imapemail/list', 'Codebank\Imapemail\Http\ImapemailController@getUserImapemailList');
	Route::get('emaillist/{current_page}','Codebank\Imapemail\Http\ImapemailController@getEmailsList');
```
3. Change the user mail account details in Imapemail/src/Http/ImapemailController.php (email id and password). 
```php
	private $userEmail = 'user_email_id@gmail.com';
    private $userPass = 'password';
```
# <a name="roadmap"></a>Roadmap

Here's the TODO list for the next release (**2.0**).

* [ ] Refactoring the source code.
* [ ] Correct all issues.
* [ ] UI using bootstrap.
