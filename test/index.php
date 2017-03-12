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

// Test plain strings
assert ($losp->format ('plain.01') === 'Hello, World!');
assert ($losp->format ('plain.02') === 'Bonjour sire ! Il fait beau, mais frais, mais beau !');

// Test strings with variables
assert ($losp->format ('variable.double', array ('a' => 'A', 'b' => 'B')) === 'Variables A et B');
assert ($losp->format ('variable.single', array ('count' => 3)) === 'Vous avez 3 nouveau(x) message(s)');
assert ($losp->format ('variable.nested.01', array ('account' => array ('name' => 'Admin'))) === 'Modifier le compte \'Admin\'');
assert ($losp->format ('variable.nested.02', array ('x' => array ('y' => array ('z' => 42)))) === 'La réponse est 42');
assert ($losp->format ('variable.occurrence', array ('count' => 1)) === 'Vous avez 1 nouveau message, avec accord s\'il vous plait !');
assert ($losp->format ('variable.occurrence', array ('count' => 2)) === 'Vous avez 2 nouveaux messages, avec accord s\'il vous plait !');
assert ($losp->format ('variable.rich', array ('a' => 0, 'b' => 1)) === '0 cochon, 1 poule, accordé également');
assert ($losp->format ('variable.rich', array ('a' => 1, 'b' => 0)) === '1 cochon, 0 poule, accordé également');
assert ($losp->format ('variable.rich', array ('a' => 1, 'b' => 1)) === '1 cochon, 1 poule, accordés également');
assert ($losp->format ('variable.rich', array ('a' => 2, 'b' => 2)) === '2 cochons, 2 poules, accordés également');

// Test aliased strings
assert ($losp->format ('alias.basic.early') === 'Hello, World!');
assert ($losp->format ('alias.basic.late') === 'Bonjour sire ! Il fait beau, mais frais, mais beau !');
assert ($losp->format ('alias.basic.variable', array ('count' => 3)) === 'Vous avez 3 nouveau(x) message(s)');
assert ($losp->format ('alias.remap.complex', array ('var' => 'B')) === 'Vous avez ABC nouveau(x) message(s)');
assert ($losp->format ('alias.remap.constant') === 'Vous avez 59 nouveau(x) message(s)');
assert ($losp->format ('alias.remap.multiple') === 'Variables X et Y');
assert ($losp->format ('alias.remap.variable', array ('other' => '17')) === 'Vous avez 17 nouveau(x) message(s)');

// Test modifiers
assert ($losp->format ('modifier.add.01', array ('lhs' => 1, 'rhs' => 3)) === '4');
assert ($losp->format ('modifier.add.01', array ('lhs' => 5, 'rhs' => -9)) === '-4');
assert ($losp->format ('modifier.case.01', array ('value' => 1)) === 'Un');
assert ($losp->format ('modifier.case.01', array ('value' => 2)) === 'Deux');
assert ($losp->format ('modifier.case.01', array ('value' => 3)) === '');
assert ($losp->format ('modifier.case.02', array ('value' => 1)) === 'Un');
assert ($losp->format ('modifier.case.02', array ('value' => 2)) === 'Deux');
assert ($losp->format ('modifier.case.02', array ('value' => 3)) === 'Autre');
assert ($losp->format ('modifier.date.01', array ('today' => 1428229942)) === 'Nous sommes le 05/04/15');
assert ($losp->format ('modifier.def.01', array ('value' => '')) === 'default');
assert ($losp->format ('modifier.def.01', array ('value' => '1')) === '1');
assert ($losp->format ('modifier.def.02', array ('v1' => '1', 'v2' => 2)) === '1');
assert ($losp->format ('modifier.def.02', array ('v2' => '2')) === '2');
assert ($losp->format ('modifier.def.02', array ()) === 'default');
assert ($losp->format ('modifier.div.01', array ('lhs' => 6, 'rhs' => 2)) === '3');
assert ($losp->format ('modifier.div.01', array ('lhs' => 5, 'rhs' => 2)) === '2');
assert ($losp->format ('modifier.if.01', array ('condition' => null)) === 'Faux');
assert ($losp->format ('modifier.if.01', array ('condition' => '0')) === 'Faux');
assert ($losp->format ('modifier.if.01', array ('condition' => '1')) === 'Vrai');
assert ($losp->format ('modifier.ifset.01', array ('condition' => null)) === 'Faux');
assert ($losp->format ('modifier.ifset.01', array ('condition' => '0')) === 'Vrai');
assert ($losp->format ('modifier.ifset.01', array ('condition' => '1')) === 'Vrai');
assert ($losp->format ('modifier.mod.01', array ('lhs' => 6, 'rhs' => 2)) === '0');
assert ($losp->format ('modifier.mod.01', array ('lhs' => 5, 'rhs' => 2)) === '1');
assert ($losp->format ('modifier.mul.01', array ('lhs' => 3, 'rhs' => 4)) === '12');
assert ($losp->format ('modifier.mul.01', array ('lhs' => 5, 'rhs' => -2)) === '-10');
assert ($losp->format ('modifier.pad.01', array ('string' => 'ABCDEF')) === 'ABCDEF--');
assert ($losp->format ('modifier.pad.01', array ('string' => 'ABCDEFGH')) === 'ABCDEFGH');
assert ($losp->format ('modifier.pad.02', array ('string' => 'ABCD')) === '----ABCD');
assert ($losp->format ('modifier.pad.02', array ('string' => 'AB')) === '------AB');
assert ($losp->format ('modifier.sub.01', array ('lhs' => 1, 'rhs' => 3)) === '-2');
assert ($losp->format ('modifier.sub.01', array ('lhs' => 5, 'rhs' => 2)) === '3');

// Test formatting errors
assert_throw (function () use ($losp) { $losp->format ('error.formatter'); }, '/unknown formatter.*error\.formatter/');
assert_throw (function () use ($losp) { $losp->format ('error.modifier'); }, '/unknown modifier.*error\.modifier/');

// Test parsing errors
assert_throw (function () { new Losp\Locale ('UTF-8', 'en', 'res/invalid/alias-reference-duplicate.xml'); }, '/string node has duplicated key "dupe"/');
assert_throw (function () { new Losp\Locale ('UTF-8', 'en', 'res/invalid/alias-reference-unknown.xml'); }, '/invalid alias "some" to unknown key "string"/');
assert_throw (function () { new Losp\Locale ('UTF-8', 'en', 'res/invalid/alias-variable-unknown.xml'); }, '/no variable "b" to remap in alias "test" to key "ref"/');
assert_throw (function () { new Losp\Locale ('UTF-8', 'fr', 'res/invalid/missing.xml'); }, '/doesn\'t exist/');
assert_throw (function () { new Losp\Locale ('UTF-8', 'fr', 'res/invalid/root-language-missing.xml'); }, '/missing "language" attribute/');
assert_throw (function () { new Losp\Locale ('UTF-8', 'fr', 'res/invalid/root-name.xml'); }, '/must be named/');
assert_throw (function () { new Losp\Locale ('UTF-8', 'en', 'res/invalid/section-prefix-missing.xml'); }, '/section node is missing "prefix" attribute/');
assert_throw (function () { new Losp\Locale ('UTF-8', 'en', 'res/invalid/string-key-duplicate.xml'); }, '/string node has duplicated key "dupe"/');
assert_throw (function () { new Losp\Locale ('UTF-8', 'en', 'res/invalid/string-key-missing.xml'); }, '/string node is missing "key" attribute/');

?>
