<?php

class sections extends Model
{

	protected $fields = array(
        "id" => "int primary_key auto_increment",
		"nombre" => "varchar(255) not_null l10n",
		"texto" => "text not_null l10n",
		"foto" => "image",
		'orden' => 'int hidden',
	);

}

