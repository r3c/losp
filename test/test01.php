<?php

require ('../main/losp.php');

$losp = new Losp\Locale ('UTF-8', 'fr', 'res/test01');
$losp->assign ('add', function ($lhs, $rhs) { return $lhs + $rhs; });
$losp->assign ('date', function ($value, $format) { return date ($format, $value); });
$losp->assign ('gt', function ($value, $than) { return $value > $than; });
$losp->assign ('if', function ($test, $true, $false = '') { return $test ? $true : $false; });
$losp->assign ('pad', function ($value, $length, $char) { return str_pad ($value, abs ($length), $char, $length < 0 ? STR_PAD_LEFT : STR_PAD_RIGHT); });

assert ($losp->format ('test.01') === 'Bonjour sire ! Il fait beau, mais frais, mais beau !');
assert ($losp->format ('test.02', array ('count' => 3)) === 'Vous avez 3 nouveau(x) message(s)');
assert ($losp->format ('test.03', array ('account' => array ('name' => 'Admin'))) === 'Modifier le compte \'Admin\'');
assert ($losp->format ('test.04', array ('today' => 1428229942)) === 'Nous sommes le 05/04/15');
assert ($losp->format ('test.05', array ('count' => 1)) === 'Vous avez 1 nouveau message, avec accord s\'il vous plait !');
assert ($losp->format ('test.05', array ('count' => 2)) === 'Vous avez 2 nouveaux messages, avec accord s\'il vous plait !');
assert ($losp->format ('test.06', array ('a' => 0, 'b' => 1)) === '0 cochon, 1 poule, accordé également');
assert ($losp->format ('test.06', array ('a' => 1, 'b' => 0)) === '1 cochon, 0 poule, accordé également');
assert ($losp->format ('test.06', array ('a' => 1, 'b' => 1)) === '1 cochon, 1 poule, accordés également');
assert ($losp->format ('test.06', array ('a' => 2, 'b' => 2)) === '2 cochons, 2 poules, accordés également');
assert ($losp->format ('test.07', array ('count' => 42)) === 'Nombre de visites: 00000042 (mais on prévoit 8 caractères, au cas où)');
assert ($losp->format ('test.08') === 'À gauche###########, des canapés !');
assert ($losp->format ('test.09') === '###########À droite, des canapés !');
assert ($losp->format ('test.10', array ('x' => array ('y' => array ('z' => 42)))) === 'La réponse est 42');

?>
