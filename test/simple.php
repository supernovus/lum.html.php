<?php

require_once 'vendor/autoload.php';

$t = new \Lum\Test();

$t->plan(29);

$html = new \Lum\HTML\Simple();

$items =
[
  'first' => 'First',
  'second' => 'Second',
  'third' => 'Third',
];
$opts = ['trim'=>true];

$select = $html->select('test', $items);

$expected = '<select name="test"><option value="first">First</option><option value="second">Second</option><option value="third">Third</option></select>';

$t->is($select, $expected."\n", 'simple select statement');

$select = $html->select('test', $items, $opts);

$t->is($select, $expected, 'select statement with trim');

$opts['selected'] = 'second';

$select = $html->select('test', $items, $opts);

$expected = substr_replace($expected, ' selected="selected"', 78, 0);

$t->is($select, $expected, 'select statement with selected item');

$items =
[
  1 => 'First',
  2 => 'Second',
  4 => 'Third',
  8 => 'Fourth',
];

$opts = ['trim'=>true, 'selected'=>2, 'mask'=>true];

$expected = str_replace('first', '1', $expected);
$expected = str_replace('second', '2', $expected);
$expected = str_replace('third', '4', $expected);
$expected = str_replace('</select>', '<option value="8">Fourth</option></select>', $expected);

$select = $html->select('test', $items, $opts);

$t->is($select, $expected, 'select with single mask selection');

$opts['selected'] = 3;

$expected = substr_replace($expected, ' selected="selected"', 37, 0);

$select = $html->select('test', $items, $opts);

$t->is($select, $expected, 'select with multiple mask selections');

$expected = str_replace('name="test"', 'id="foo"', $expected);

$select = $html->select(['id'=>'foo'], $items, $opts);

$t->is($select, $expected, 'select with attributes passed');

// TODO: test with translation strings, will require the Nano\UI\Strings
// library to be in the "require-dev".

$opts = ['trim'=>true];

$menu =
[
  'first',
  'second',
];

$expected = "<ul><li>first</li><li>second</li></ul>";

/*
function show () 
{
  global $menu;
  global $expected;
  error_log(json_encode($menu))."\n";
  error_log($expected);
}
 */

$ul = $html->ul($menu, $opts);

$t->is($ul, $expected, 'ul basic element');

array_unshift($menu, ['id'=>'test']);

$expected = str_replace('<ul>', '<ul id="test">', $expected);

$ul = $html->ul($menu, $opts);

$t->is($ul, $expected, 'ul with top level attributes');

array_splice($menu, 2, 0, [['id'=>'f']]);

$expected = str_replace('<li>first</li>','<li id="f">first</li>', $expected);

$ul = $html->ul($menu, $opts);

$t->is($ul, $expected, 'ul li with attributes');

$menu['third'] = 'added';

$expected = str_replace('</ul>', '<li class="added">third</li></ul>', $expected);

$ul = $html->ul($menu, $opts);

$t->is($ul, $expected, 'ul li with class');

array_splice($menu, 1, 0, [['ol'=>['one','two']]]);

$expected = str_replace('<ul id="test">', '<ul id="test"><li><ol><li>one</li><li>two</li></ol></li>', $expected);

$ul = $html->ul($menu, $opts);

$t->is($ul, $expected, 'ul with anonymous nested submenu');

array_splice($menu, 5, 0, [['ol'=>['two','one']]]);

$expected = str_replace('<li>second</li>', '<li>second<ol><li>two</li><li>one</li></ol></li>', $expected);

$ul = $html->ul($menu, $opts);

$t->is($ul, $expected, 'ul with directly assigned submenu');

$menu['last'] = ['a','b'];

$expected = str_replace('</ul>', '<li>last<ul><li>a</li><li>b</li></ul></li></ul>', $expected);

$ul = $html->ul($menu, $opts);

$t->is($ul, $expected, 'ul with named submenu');

$expected = str_replace('ul', 'zl', $expected); // Temporary for swap.
$expected = str_replace('ol', 'ul', $expected); // ol => ul
$expected = str_replace('zl', 'ol', $expected); // ul => ol

$menu[1]['ul'] = $menu[1]['ol'];
unset($menu[1]['ol']);
$menu[5]['ul'] = $menu[5]['ol'];
unset($menu[5]['ol']);

$ol = $html->ol($menu, $opts);

$t->is($ol, $expected, 'ol with full set of options');

$opts = ['trim'=>true];

$hidden = $html->hidden('foo', 'bar', $opts);

$expected = '<input type="hidden" id="foo" name="foo" value="bar"/>';
$oldval = 'value="bar"';

$t->is($hidden, $expected, 'hidden element');

$json = $html->json('foo', ['bar'=>true], $opts);

$newval = 'value="{&quot;bar&quot;:true}"';
$expected = str_replace($oldval, $newval, $expected);
$oldval = $newval;

$t->is($json, $expected, 'json with PHP array works');

$obj = new StdClass();
$obj->hello = 'world';

$json = $html->json('foo', $obj, $opts);

$newval = 'value="{&quot;hello&quot;:&quot;world&quot;}"';
$expected = str_replace($oldval, $newval, $expected);
$oldval = $newval;

$t->is($json, $expected, 'json with StdClass object works');

class TestClass1
{
  public $name;
  public function __construct ($name)
  {
    $this->name = $name;
  }
}

$obj = new TestClass1('Bar');

$json = $html->json('foo', $obj, $opts);

$newval = 'value="{&quot;name&quot;:&quot;Bar&quot;}"';
$expected = str_replace($oldval, $newval, $expected);
$oldval = $newval;

$t->is($json, $expected, 'json with simple class object');

class TestClass2 extends TestClass1
{
  public function to_array ($opts=[])
  {
    $arr = [];
    if (isset($opts['uppercase']) && $opts['uppercase'])
    {
      $arr['name'] = strtoupper($this->name);
    }
    else
    {
      $arr['name'] = strtolower($this->name);
    }
    return $arr;
  }
}

$obj = new TestClass2('Bar');

$json = $html->json('foo', $obj, $opts);

$newval = str_replace('Bar','bar', $oldval);
$expected = str_replace($oldval, $newval, $expected);
$oldval = $newval;

$t->is($json, $expected, 'json with custom to_array() and no options');

$opts['uppercase'] = true;

$newval = str_replace('bar','BAR', $oldval);
$expected = str_replace($oldval, $newval, $expected);
$oldval = $newval;

$json = $html->json('foo', $obj, $opts);

$t->is($json, $expected, 'json with custom to_array() with options');

class TestClass3 extends TestClass2
{
  public function to_json ($opts=[])
  {
    $arr = $this->to_array($opts);
    if (isset($opts['uppercase']) && $opts['uppercase'])
    {
      $arr['__class'] = strtoupper(get_class($this));
    }
    else
    {
      $arr['__class'] = get_class($this);
    }
    return json_encode($arr);
  }
}

$obj = new TestClass3('Bar');

$newval = str_replace('}"',',&quot;__class&quot;:&quot;TESTCLASS3&quot;}"', $oldval);
$expected = str_replace($oldval, $newval, $expected);
$oldval = $newval;

$json = $html->json('foo', $obj, $opts);

$t->is($json, $expected, 'json with custom to_json() with options');

unset($opts['uppercase']);

$newval = str_replace('TESTCLASS3', 'TestClass3', $oldval);
$newval = str_replace('BAR', 'bar', $newval);
$expected = str_replace($oldval, $newval, $expected);
$oldval = $newval;

$json = $html->json('foo', $obj, $opts);

$t->is($json, $expected, 'json with custom to_json() and no options');

$opts = ['trim'=>true];

$input = $html->input('foo', $opts);

$expected = '<input id="foo" type="text"/>';

$t->is($input, $expected, 'input with a string passed');

$input = $html->input(['id'=>'foo', 'class'=>'bar'], $opts);

$expected = '<input id="foo" class="bar" type="text"/>';

$t->is($input, $expected, 'input with attributes passed');

$input = $html->input('foo', $opts, true);

$expected = '<input id="foo" type="text" name="foo"/>';

$t->is($input, $expected, 'input with map option passed');

// TODO: more input options.
// TODO: translation stuff, needs Lum\UI\Strings.

$button = $html->button('foo', $opts);

$expected = '<input id="foo" type="button"/>';

$t->is($button, $expected, 'button with an id string');

// TODO: more button tests.

$submit = $html->submit('foo', $opts);

$expected = '<input name="foo" type="submit"/>';

$t->is($submit, $expected, 'submit with an id string');

// TODO: more submit tests.

// TODO: test strip() function.

$opts = ['raw'=>true];

$hidden = $html->hidden('foo', 'bar', $opts);

$t->ok(($hidden instanceof SimpleXMLElement), 'raw output is SimpleXMLElement');

$t->is((string)$hidden['id'], 'foo', 'raw output has right id');

echo $t->tap();
return $t;
