<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTodoTable extends Migration{
    
    /**
     * Run the migrations.
     * 
     * @return void 
     */
    public function up(){
        
        Schema::create('todos', function (Blueprint $table){
           $table->increments('id')->comment('Uinique') ;
           $table->integer('user_id')->comment('User who added the TODO');
           $table->boolean('completed')->comment('Status of the  TODO');
           $table->text('todo')->comment('The actual TODO');
           $table->timestamps();           
        });
    }
    /**
     * Reverse the migrations
     * 
     * @return void 
     */
    public function down(){
        Schema::drop('todos');
    }
}