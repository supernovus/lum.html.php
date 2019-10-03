<?php

require_once 'vendor/autoload.php';

$t = new \Lum\Test();

$t->plan(2);

$comp = new \Lum\HTML\Components();

$output = trim($comp->load('test/views/hello.php', ['name'=>'world']));

$expected = '<div id="hello">world</div>';
$t->is($output, $expected, 'components with no view loader');

$core = \Lum\Core::getInstance();
if (!isset($core->components))
{
  $core->components = 'views';
  $core->components->addDir('test/views');
}

$comp->include_ns = 'components';

$output = trim($comp->load('goodbye', ['name'=>'universe']));

$expected = '<div id="goodbye">universe</div>';

$t->is($output, $expected, 'components with view loader');

echo $t->tap();
return $t;
