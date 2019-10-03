<?php

namespace Lum\HTML;

require_once 'vendor/autoload.php';

$t = new \Lum\Test();

$t->plan(9); // from outer space.

$core = \Lum\Core::getInstance();
if (!isset($core->components))
{
  $core->components = 'views';
  $core->components->addDir('test/views');
}

$html = new Helper(['include'=>'components']);

$cmenu = $html->getClassicMenu();

$t->ok(($cmenu instanceof ClassicMenu), 'getClassicMenu returns ClassicMenu instance');

$rmenu = $html->getRouterMenu();

$t->ok(($rmenu instanceof RouterMenu), 'getRouterMenu returns RouterMenu instance');

// TODO: getStrings() tests.

$simple = $html->getSimple();

$t->ok(($simple instanceof Simple), 'getSimple returns Simple instance');

$comp = $html->getComponents();

$t->ok(($comp instanceof Components), 'getComponents returns Components instance');

$t->is($comp->include_ns, 'components', 'getComponents set proper include option');

$elem = $html->element('html');
$body = $elem->body();
$body->h1('test');
$div = $body->div();
$div->p('one line');
$div->p('two lines');
$body->footer('the end');

$expected = '<html><body><h1>test</h1><div><p>one line</p><p>two lines</p></div><footer>the end</footer></body></html>';

$t->is("$elem", $expected, 'element tag worked');

// TODO: menu() and routemenu() tests.

$output = $html->get('hello', ['name'=>'World']);

$expected = '<div id="hello">World</div>';

$t->is($output, $expected, 'get() passes to Components::load()');

$output = $html->goodbye(['name'=>'Universe']);

$expected = '<div id="goodbye">Universe</div>';

$t->is($output, $expected, 'Unhandled call passes to Components::load()');

$button = $html->button('foo', ['trim'=>true]);

$expected = '<input id="foo" type="button"/>';

$t->is($button, $expected, 'Simple methods pass through');

echo $t->tap();
return $t;