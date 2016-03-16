<?php 
namespace Codebank\Imapemail;

use Illuminate\Support\ServiceProvider;

class ImapemailServiceProvider extends  ServiceProvider{
    
    public function register() {
        $this->app->bind('imapemail', function($app){
            return new Imapemail;
        }
            );
    }
    public function  boot(){
        // loading the routes file
        require __DIR__.'/Http/routes.php'; 
        
        //define the path for the view files
        $this->loadViewsFrom(__DIR__.'/../views', 'imapemail');
        // define the files which are going to be published.
        $this->publishes([
            __DIR__.'/migrations/2015_11_30_000000_create_imapemail_table.php'=> base_path('database/migrations/2015_11_30_000000_create_imapemail_table.php'),
        ]);
    }
    
}