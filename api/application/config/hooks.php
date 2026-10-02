<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/userguide3/general/hooks.html
|
*/

// Valida el token de sesión antes de cada método (salvo las rutas permitidas del hook)
$hook['post_controller_constructor'][] = [
	"class"    => "Inicio",
	"function" => "validarSesion",
	"filename" => "Inicio.php",
	"filepath" => "hooks"
];
