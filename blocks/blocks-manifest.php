<?php
// This file is generated. Do not modify it manually.
return array(
	'table' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'tablepress/table',
		'version' => '3.2.5',
		'title' => 'TablePress table',
		'category' => 'media',
		'icon' => 'list-view',
		'description' => 'Embed a TablePress table.',
		'keywords' => array(
			'table'
		),
		'textdomain' => 'tablepress',
		'attributes' => array(
			'id' => array(
				'type' => 'string',
				'default' => ''
			),
			'parameters' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'supports' => array(
			'align' => false,
			'html' => false,
			'customClassName' => false
		),
		'editorScript' => 'file:build/index.js',
		'editorStyle' => 'file:build/index.css'
	)
);
