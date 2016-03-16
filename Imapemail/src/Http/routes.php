<?php

Route::get('imapemail/list', 'Codebank\Imapemail\Http\ImapemailController@getUserImapemailList');
Route::get('emaillist/{current_page}','Codebank\Imapemail\Http\ImapemailController@getEmailsList');
Route::get('emaillist/','Codebank\Imapemail\Http\ImapemailController@getEmailsList');