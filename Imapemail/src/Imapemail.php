<?php 
namespace Codebank\Imapemail;
use Illuminate\Database\Eloquent\Model;

class Imapemail extends Model {
    protected $table = 'imapemails';
    protected $fillable = ['user_id','completed','imapemail'];
}