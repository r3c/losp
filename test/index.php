<?php

require('../src/losp.php');

function assert_throw($callback, $pattern)
{
    try {
        $callback();

        assert(false, 'no exception thrown');
    } catch (Exception $exception) {
        assert(preg_match($pattern, $exception->getMessage()) === 1, 'exception message "' . $exception->getMessage() . '" doesn\'t match pattern "' . $pattern . '"');
    }
}

class Container
{
    public function __construct($value, $key, $child)
    {
        $this->value = $value;
        $this->$key = $child;
    }

    public function __toString()
    {
        return $this->value;
    }
}

$locale = new Losp\Locale('UTF-8', 'fr', 'res/valid');
$locale->declare('custom', function ($lhs, $rhs = '') {
    return strrev($rhs) . strrev($lhs);
});

// Test plain strings
assert($locale->format('plain.01') === 'Hello, World!');
assert($locale->format('plain.02') === 'Bonjour sire ! Il fait beau, mais frais, mais beau !');

// Test strings with variables
assert($locale->format('variable.single', array('count' => 3)) === 'Vous avez 3 nouveau(x) message(s)');
assert($locale->format('variable.double', array('a' => 'A', 'b' => 'B')) === 'Variables A et B');
assert($locale->format('variable.nested.01', array('account' => array('name' => 'Admin'))) === 'Modifier le compte \'Admin\'');
assert($locale->format('variable.nested.02', array('x' => array('y' => array('z' => 42)))) === 'La réponse est 42');
assert($locale->format('variable.nested.03', array('x' => new Container('xxx', 'y', new Container('yyy', 'z', 'zzz')))) === 'X = xxx, Y = yyy, Z = zzz');
assert($locale->format('variable.twice', array('count' => 1)) === 'Vous avez 1 nouveau message, avec accord s\'il vous plait !');
assert($locale->format('variable.twice', array('count' => 2)) === 'Vous avez 2 nouveaux messages, avec accord s\'il vous plait !');
assert($locale->format('variable.rich', array('a' => 0, 'b' => 1)) === '0 cochon, 1 poule, accordé également');
assert($locale->format('variable.rich', array('a' => 1, 'b' => 0)) === '1 cochon, 0 poule, accordé également');
assert($locale->format('variable.rich', array('a' => 1, 'b' => 1)) === '1 cochon, 1 poule, accordés également');
assert($locale->format('variable.rich', array('a' => 2, 'b' => 2)) === '2 cochons, 2 poules, accordés également');

// Test aliased strings
assert($locale->format('alias.basic.early') === 'Hello, World!');
assert($locale->format('alias.basic.late') === 'Bonjour sire ! Il fait beau, mais frais, mais beau !');
assert($locale->format('alias.basic.variable', array('count' => 3)) === 'Vous avez 3 nouveau(x) message(s)');
assert($locale->format('alias.remap.complex', array('var' => 'B')) === 'Vous avez ABC nouveau(x) message(s)');
assert($locale->format('alias.remap.constant') === 'Vous avez 59 nouveau(x) message(s)');
assert($locale->format('alias.remap.multiple') === 'Variables X et Y');
assert($locale->format('alias.remap.nested.middle', array('x' => new Container('xxx', 'y', new Container('yyy', 'z', 'zzz')))) === 'X = xxx, Y = remap, Z = zzz');
assert($locale->format('alias.remap.nested.tail', array('account' => array('name' => 'User'))) === 'Modifier le compte \'Someone\'');
assert($locale->format('alias.remap.twice') === 'Vous avez 3 nouveaux messages, avec accord s\'il vous plait !');
assert($locale->format('alias.remap.variable', array('other' => '17')) === 'Vous avez 17 nouveau(x) message(s)');

// Test modifiers
assert($locale->format('modifier.add.01', array('lhs' => 1, 'rhs' => 3)) === '4');
assert($locale->format('modifier.add.01', array('lhs' => 5, 'rhs' => -9)) === '-4');
assert($locale->format('modifier.case.01', array('value' => 1)) === 'Un');
assert($locale->format('modifier.case.01', array('value' => 2)) === 'Deux');
assert($locale->format('modifier.case.01', array('value' => 3)) === '');
assert($locale->format('modifier.case.02', array('value' => 1)) === 'Un');
assert($locale->format('modifier.case.02', array('value' => 2)) === 'Deux');
assert($locale->format('modifier.case.02', array('value' => 3)) === 'Autre');
assert($locale->format('modifier.custom.01') === 'abc');
assert($locale->format('modifier.custom.02') === 'abcdef');
assert($locale->format('modifier.date.01', array('today' => 1428229942)) === 'Nous sommes le 05/04/15');
assert($locale->format('modifier.def.01', array('value' => '')) === 'default');
assert($locale->format('modifier.def.01', array('value' => '1')) === '1');
assert($locale->format('modifier.def.02', array('v1' => '1', 'v2' => 2)) === '1');
assert($locale->format('modifier.def.02', array('v2' => '2')) === '2');
assert($locale->format('modifier.def.02', array()) === 'default');
assert($locale->format('modifier.div.01', array('lhs' => 6, 'rhs' => 2)) === '3');
assert($locale->format('modifier.div.01', array('lhs' => 5, 'rhs' => 2)) === '2');
assert($locale->format('modifier.if.01', array('condition' => null)) === 'Faux');
assert($locale->format('modifier.if.01', array('condition' => '0')) === 'Faux');
assert($locale->format('modifier.if.01', array('condition' => '1')) === 'Vrai');
assert($locale->format('modifier.ifset.01', array('condition' => null)) === 'Faux');
assert($locale->format('modifier.ifset.01', array('condition' => '0')) === 'Vrai');
assert($locale->format('modifier.ifset.01', array('condition' => '1')) === 'Vrai');
assert($locale->format('modifier.mod.01', array('lhs' => 6, 'rhs' => 2)) === '0');
assert($locale->format('modifier.mod.01', array('lhs' => 5, 'rhs' => 2)) === '1');
assert($locale->format('modifier.mul.01', array('lhs' => 3, 'rhs' => 4)) === '12');
assert($locale->format('modifier.mul.01', array('lhs' => 5, 'rhs' => -2)) === '-10');
assert($locale->format('modifier.pad.01', array('string' => 'ABCDEF')) === 'ABCDEF--');
assert($locale->format('modifier.pad.01', array('string' => 'ABCDEFGH')) === 'ABCDEFGH');
assert($locale->format('modifier.pad.02', array('string' => 'ABCD')) === '----ABCD');
assert($locale->format('modifier.pad.02', array('string' => 'AB')) === '------AB');
assert($locale->format('modifier.sub.01', array('lhs' => 1, 'rhs' => 3)) === '-2');
assert($locale->format('modifier.sub.01', array('lhs' => 5, 'rhs' => 2)) === '3');

// Test charset output
foreach (array('ISO-8859-1', 'UTF-8') as $charset) {
    $locale_charset = new Losp\Locale($charset, 'fr', 'res/valid');

    assert($locale_charset->format('charset.accent') === mb_convert_encoding('Àéç©', $charset, 'UTF-8'), "formatting with charset $charset");
}

// Test formatting errors
assert_throw(function () use ($locale) {
    $locale->format('error.formatter');
}, '/unknown formatter.*error\.formatter/');
assert_throw(function () use ($locale) {
    $locale->format('error.modifier');
}, '/unknown modifier.*error\.modifier/');

// Test parsing errors
assert_throw(function () {
    new Losp\Locale('UTF-8', 'en', 'res/invalid/alias-reference-duplicate.xml');
}, '/string node has duplicated key "dupe"/');
assert_throw(function () {
    new Losp\Locale('UTF-8', 'en', 'res/invalid/alias-reference-unknown.xml');
}, '/invalid alias "some" to unknown key "string"/');
assert_throw(function () {
    new Losp\Locale('UTF-8', 'en', 'res/invalid/alias-variable-unknown.xml');
}, '/no variable "b" to remap in alias "test" to key "ref"/');
assert_throw(function () {
    new Losp\Locale('UTF-8', 'fr', 'res/invalid/missing.xml');
}, '/doesn\'t exist/');
assert_throw(function () {
    new Losp\Locale('UTF-8', 'fr', 'res/invalid/root-language-missing.xml');
}, '/missing "language" attribute/');
assert_throw(function () {
    new Losp\Locale('UTF-8', 'fr', 'res/invalid/root-name.xml');
}, '/must be named/');
assert_throw(function () {
    new Losp\Locale('UTF-8', 'en', 'res/invalid/section-prefix-missing.xml');
}, '/section node is missing "prefix" attribute/');
assert_throw(function () {
    new Losp\Locale('UTF-8', 'en', 'res/invalid/string-key-duplicate.xml');
}, '/string node has duplicated key "dupe"/');
assert_throw(function () {
    new Losp\Locale('UTF-8', 'en', 'res/invalid/string-key-missing.xml');
}, '/string node is missing "key" attribute/');
