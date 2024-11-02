<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| User Settings
	| for user_id, first_name, last_name - set the db column name that corresponds the field
	|  -> the user_id field is a VARCHAR(50) so an email could be stored
	| protected_fields: array that holds sensitive data fields to strip out of the input dump to the db
	| protected_fields: array that holds URL strings that would be placed in the "target_url" field, if match then don't log in DB
	|--------------------------------------------------------------------------
	*/

	'user_id' => null,
        'first_name' => null,
        'last_name' => null,
	'protected_fields' => ['_token','password'],
	'ignored_urls' => [],
	'ignored_route_names' => ['users.show'],
    
);
