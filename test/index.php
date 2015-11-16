<?php

require ('../main/losp.php');

function assert_throw ($callback, $pattern)
{
	try
	{
		$callback ();

		assert (false, 'no exception thrown');
	}
	catch (Exception $exception)
	{
		assert (preg_match ($pattern, $exception->getMessage ()) === 1, 'exception message "' . $exception->getMessage () . '" doesn\'t match pattern "' . $pattern . '"');
	}
}

$losp = new Losp\Locale ('UTF-8', 'fr', 'res/valid');
$losp->assign ('add', function ($lhs, $rhs) { return $lhs + $rhs; });

// Test regular strings
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

// Test aliased strings
assert ($losp->format ('alias.01') === 'Bonjour sire ! Il fait beau, mais frais, mais beau !');
assert ($losp->format ('alias.02', array ('count' => 3)) === 'Vous avez 3 nouveau(x) message(s)');

// Test modifiers
assert ($losp->format ('modifier.case.01', array ('value' => 1)) === 'Un');
assert ($losp->format ('modifier.case.01', array ('value' => 2)) === 'Deux');
assert ($losp->format ('modifier.case.01', array ('value' => 3)) === '');
assert ($losp->format ('modifier.case.02', array ('value' => 1)) === 'Un');
assert ($losp->format ('modifier.case.02', array ('value' => 2)) === 'Deux');
assert ($losp->format ('modifier.case.02', array ('value' => 3)) === 'Autre');
assert ($losp->format ('modifier.def.01', array ('value' => '')) === 'default');
assert ($losp->format ('modifier.def.01', array ('value' => '1')) === '1');
assert ($losp->format ('modifier.if.01', array ('condition' => null)) === 'Faux');
assert ($losp->format ('modifier.if.01', array ('condition' => '0')) === 'Faux');
assert ($losp->format ('modifier.if.01', array ('condition' => '1')) === 'Vrai');
assert ($losp->format ('modifier.ifset.01', array ('condition' => null)) === 'Faux');
assert ($losp->format ('modifier.ifset.01', array ('condition' => '0')) === 'Vrai');
assert ($losp->format ('modifier.ifset.01', array ('condition' => '1')) === 'Vrai');
assert ($losp->format ('modifier.pad.01', array ('string' => 'ABCDEF')) === 'ABCDEF--');
assert ($losp->format ('modifier.pad.01', array ('string' => 'ABCDEFGH')) === 'ABCDEFGH');
assert ($losp->format ('modifier.pad.02', array ('string' => 'ABCD')) === '----ABCD');
assert ($losp->format ('modifier.pad.02', array ('string' => 'AB')) === '------AB');

// Test formatting errors
assert_throw (function () use ($losp) { $losp->format ('error.formatter'); }, '/unknown formatter.*error\.formatter/');
assert_throw (function () use ($losp) { $losp->format ('error.modifier'); }, '/unknown modifier.*error\.modifier/');

// Test parsing errors
assert_throw (function () { new Losp\Locale ('UTF-8', 'fr', 'res/invalid/missing.xml'); }, '/doesn\'t exist/');
assert_throw (function () { new Losp\Locale ('UTF-8', 'fr', 'res/invalid/root-language.xml'); }, '/missing "language" attribute/');
assert_throw (function () { new Losp\Locale ('UTF-8', 'fr', 'res/invalid/root-name.xml'); }, '/must be named/');
assert_throw (function () { new Losp\Locale ('UTF-8', 'en', 'res/invalid/string-alias.xml'); }, '/invalid reference/');
assert_throw (function () { new Losp\Locale ('UTF-8', 'en', 'res/invalid/string-key.xml'); }, '/missing "key" attribute/');

?>