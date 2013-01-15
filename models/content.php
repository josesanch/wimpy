<?php

class models_content extends Model
{
	protected $fields = array(
        "id"           => "int primary_key auto_increment",
        "name"       => "varchar(255) not_null l10n",
        "title"       => "varchar(255) not_null l10n",
        "subtitle"    => "varchar(255) l10n",

        'section_id' => "int label='Sección a la que pertenece'",
        "text"        => "text not_null html l10n",

        'orden'        => 'int hidden',
        'files'     => "files title='Archivos asociados'",

        "Others"        => "--- accordion collapsed",
        'template'    => "varchar(255) title='For intern use'",

        "SEO"          => "--- accordion collapsed",
        'descripcion'  => 'varchar(255) l10n',
        'keywords'     => 'text l10n',
        "url"          => "varchar(255) l10n"
    );
}


