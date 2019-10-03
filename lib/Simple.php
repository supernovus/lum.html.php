<?php

namespace Lum\HTML;

class Simple
{
  /**
   * If set, this should be a Lum\UI\Strings object.
   */
  public $translate;

  /**
   * Build a new HTML helper object.
   *
   * @param array $opts  Reset the default 'translate' and 'include' values.
   */
  public function __construct ($opts=[])
  {
    if (isset($opts['translate']))
      $this->translate = $opts['translate'];
  }

  /**
   * Return the $translate property.
   *
   * @return (object|null)  Either a Lum\UI\Strings instance, or null.
   */
  public function getStrings ()
  {
    return $this->translate;
  }

  /**
   * Function that handles return values.
   *
   * We previously supported a bunch of different output formats.
   * Now we offer two. The default is to return the HTML string.
   * If you pass 'raw' => true to the opts, we will return the
   * raw object. Also, if the value passed is not a SimpleXML
   * element, we will return it as if 'raw' had been passed.
   *
   * @param mixed $value  A SimpleXML object, or a string.
   * @param array $opts   Options controlling the output.
   * @returns mixed       Output depends on the options sent.
   */
  public function return_value ($value, $opts)
  {
    if ($value instanceof \SimpleXMLElement && !isset($opts['raw']))
    {
      $string = $value->asXML();
      $string = preg_replace('/<\?xml .*?\?>\s*/', '', $string);
      if (isset($opts['trim']) && $opts['trim'])
        $string = trim($string);
      return $string;
    }
    else
    {
      return $value;
    }
  }

  /**
   * Generate an HTML Select structure.
   *
   * Generates a <select/> structure containing <option/>
   * elements for each item in an associative array, where the 
   * array key is the HTML value, and the array value is the option label.
   * 
   * @param mixed $attrs  HTML attributes for the select tag.
   *
   * The $attrs can either be an associative array of HTML attributes for
   * the select tag, or can be the value of the 'name' attribute.
   *
   * @param array $array  Array of items to add, where $value => $label.
   * 
   * @param array $opts   (Optional) Named options:
   *
   *  'selected'  (mixed)     The current selected value.
   *
   *  'mask'      (boolean)   If true, selected value is a bitmask.
   *
   *  'id'        (boolean)   If true, and no 'id' attrib exists,
   *                          we set the 'id' for the select to be the same
   *                          as the 'name' attribute.
   *
   *  'ns'        (string)    Translation prefix. This is only valid if the
   *                          HTML object has a 'translate' object set.
   *
   *  'ttns'      (string)    Tooltip translation prefix. If you set this,
   *                          and the HTML object has a 'translate' object,
   *                          tooltips can be added to your options.
   *
   * @return mixed   See return_value().
   */
  public function select ($attrs, $array, $opts=[])
  { // Let's build our select structure.
    $select = new \SimpleXMLElement('<select/>');
    if (is_string($attrs))
    {
      $attrs = ['name'=>$attrs];
    }
    if 
    (
      isset($opts['id']) && $opts['id'] 
      && !isset($attrs['id']) && isset($attrs['name'])
    )
    {
      $attrs['id'] = $attrs['name'];
    }

    // See if we're using bitmasks for the selected values.
    if (isset($opts['mask']))
      $is_mask = $opts['mask'];
    else
      $is_mask = False;

    // Check for a selected option.
    if (isset($opts['selected']))
    {
      $selected = $opts['selected'];

      if (is_array($selected))
      {
        // Get an identifier we can use.
        if (isset($attrs['id']))
        {
          $identifier = $attrs['id'];
        }
        elseif (isset($attrs['name']))
        {
          $identifier = $attrs['name'];
        }
        else
        {
          $identifier = Null;
        }
        if (isset($identifier) && isset($selected[$identifier]))
        {
          $selected = $selected[$identifier];
        }
      }
    }
    else
    {
      $selected = Null;
    }

    // Check for a 'ns' option to override translation prefix.
    if (isset($opts['ns']))
    {
      $prefix = $opts['ns'];
    }
    else
    {
      $prefix = '';
    }

    // Check for a 'ttns' option, specifying a tooltip translation prefix.
    if (isset($opts['ttns']))
    {
      $ttns = $opts['ttns'];
    }
    else
    {
      $ttns = null;
    }

    // For processing complex entities.
    if (isset($opts['labelkey']))
    {
      $label_key = $opts['labelkey'];
    }
    else
    {
      $label_key = 'text';
    }
    if (isset($opts['valuekey']))
    {
      $value_key = $opts['valuekey'];
    }
    else
    {
      $value_key = 'id';
    }

    // Add attributes.
    foreach ($attrs as $aname=>$aval)
    {
      $select->addAttribute($aname, $aval);
    }

    if (isset($opts['translate']))
    {
      $translate = $opts['translate'] && isset($this->translate);
    }
    else
    {
      $translate = isset($this->translate);
    }

    // Used only if translation service is enabled.
    $tooltips = [];

    // Add options, with potential translation processing.
    if ($translate)
    {
      $array = $this->translate->strArray($array, $prefix);
      if (isset($ttns))
      {
        $tooltips = $this->translate->strArray($array, $ttns);
      }
    }
    foreach ($array as $value=>$label)
    {
      if (isset($tooltips[$value]) && $tooltips[$value] != $value)
      {
        $tooltip = $tooltips[$value];
      }
      else
      {
        $tooltip = null;
      }

      if (is_array($label))
      { // Process complex entries.
        if (isset($label[$value_key]))
          $value = $label[$value_key];
        if (isset($label[$label_key]))
          $label = $label[$label_key];
      }

      $option = $select->addChild('option', $label);
      $option->addAttribute('value', $value);
      if (isset($tooltip))
      {
        $option->addAttribute('title', $tooltip);
      }
      if 
      (
        isset($selected)
        &&
        (
          ($is_mask && ($value & $selected))
          ||
          ($value == $selected)
        )
      )
      {
        $option->addAttribute('selected', 'selected');
      }
    }
    $html = $this->return_value($select, $opts);
    if (substr(trim($html), -2) == '/>')
    {
      $html = substr_replace(trim($html), '></select>', -2);
#      error_log("Correcting singleton select HTML: $html");
    }
    return $html;
  }

  // Backend function that powers the ul()/ol() methods.
  protected function build_list ($menu, $type, $parent=Null)
  {
    if (isset($parent))
    {
      $ul = $parent->addChild($type);
    }
    else
    {
      $ul = new \SimpleXMLElement("<$type/>");
    }
    $li = Null; // This will point at the last known <li/> item.
    foreach ($menu as $index=>$value)
    {
      if (is_numeric($index))
      { // No associative key.
        if (is_array($value))
        { // A raw array with no label is attributes.
          foreach ($value as $key=>$attr)
          {
            if (($key == 'ul' || $key == 'ol') && is_array($attr))
            { // Adding a specific type of sub-menu.
              if (isset($li))
              { // Add the new sublist to the current li.
                $this->build_list($attr, $key, $li);
              }
              else
              { // Create a new anonymous li and add the sub-list.
                $li = $ul->addChild('li');
                $this->build_list($attr, $key, $li);
              }
            }
            else
            { // Setting attributes.
              if (isset($li))
              {
                $li->addAttribute($key, $attr);
              }
              else
              {
                $ul->addAttribute($key, $attr);
              }
            }
          }
        }
        else
        { // A static text value.
          $li = $ul->addChild('li', $value);
        }
      }
      else
      { // An associative key was supplied.
        if (is_array($value))
        { // Adding a new li with the nested submenu.
          $li = $ul->addChild('li', $index);
          $this->build_list($value, $type, $li);
        }
        else
        { // Assume the value is the 'class' attribute.
          $li = $ul->addChild('li', $index);
          $li->addAttribute('class', $value);
        }
      }
    }
    return $ul;
  }

  /**
   * Generate a recursive list (<ul/>).
   *
   * Creates a <ul/> object with appropriate nesting.
   *
   * @param array  $menu  The array representing the menu.
   *
   * The array representing the menu can be quite complex.
   *
   * Flat members (i.e. ones where you did not specify the key)
   * that are strings will add new list items with the string as the
   * text content.
   *
   * Flat members that are arrays are attributes (with exceptions.)
   * If no list item has been added to the list yet, the attributes will
   * be applied to the parent list element, otherwise they will be
   * applied to the last defined list item.
   * If a key of 'ul' or 'ol' is found, and the value is an array, then it's
   * a sublist, and will be appended to the last defined list item (if there
   * is no defined list items, an anonymous one will be added.)
   *
   * Named members (i.e. ones where you specify a named key)
   * that have array values are also considered sub-menus, but unlike the
   * submenus added in flat arrays, they will always add a new list item with
   * the key as the name, and the value as the menu definition. They will
   * also always be of the same type as the parent menu.
   *
   * Named members with string values will create a new list item with the
   * key as the text content, and set the 'class' attribute to the value.
   *
   * @param array  $opts  Optional function-specific settings.
   *
   * In addition to the usual output options, this also
   * supports the following option:
   *
   *  'type'  (string)        The list type, defaults to 'ul'.
   *                          The only other type supported here is 'ol'.
   *
   * @return mixed  See return_value().
   */
  public function ul ($menu, $opts=[])
  {
    if (isset($opts['type']))
      $type = $opts['type'];
    else
      $type = 'ul';

    $list = $this->build_list($menu, $type);

    return $this->return_value($list, $opts);
  }

  /**
   * Generate a recursive list (<ol/>).
   *
   * Calls the ul() method, but defaults to 'ol' for the type.
   *
   * @param array $menu  See ul().
   * @param array $opts  See ul().
   *
   * @return mixed  See return_value().
   */
  public function ol ($menu, $opts=[])
  {
    if (!isset($opts['type']))
      $opts['type'] = 'ol';

    return $this->ul($menu, $opts);
  }

  /**
   * Generate a hidden input field element.
   *
   * @param string $name   Will be set to the 'name' and 'id' attributes.
   * @param string $value  Will be set to the 'value' attribute.
   * @param array  $opts   (Optional) Passed to return_value().
   *
   * @return mixed  See return_value().
   */
  public function hidden ($name, $value, $opts=[])
  {
    $input = new \SimpleXMLElement('<input/>');
    $input->addAttribute('type',   'hidden');
    $input->addAttribute('id',     $name);
    $input->addAttribute('name',   $name);
    $input->addAttribute('value',  $value);
    return $this->return_value($input, $opts);
  }

  /**
   * Generate a hidden input field representing a JSON object.
   *
   * @param string $name    The name/id of the field.
   * @param mixed  $struct  Value to be passed through json_encode().
   *
   *                        If the $struct is an object and has a to_json()
   *                        method, that will be used instead of json_encode().
   *                        The $opts will be passed to the to_json() method.
   *
   *                        If the $struct is an object and has a to_array()
   *                        method defined, that will be called before it is
   *                        passed to json_encode(). The $opts will be passed
   *                        to the to_array() method as well.
   *
   * @param array  $opts    (Optional) Passed to return_value().
   *
   * @return mixed  See return_value().
   */
  public function json ($name, $struct, $opts=[])
  {
    if (is_object($struct) && is_callable([$struct, 'to_json']))
    {
      $json = $struct->to_json($opts);
    }
    else
    {
      if (is_object($struct) && is_callable([$struct, 'to_array']))
      {
        $struct = $struct->to_array($opts);
      }
      $json = json_encode($struct);
    }
    return $this->hidden($name, $json, $opts);
  }

  /**
   * Build an '<input/>' element.
   *
   * @param mixed $attrs  Either an array of attributes, or the name/id of
   *                      the input element. If using an array, at least the
   *                      default attribute ('id' or 'name' usually) must be
   *                      specified, or an Exception will be thrown.
   *                      See below on how to set the default attribute.
   *
   * @param array $opts   (Optional) Named options:
   *
   * 'def'     (string)  If specified, sets the default attribute.
   *                     If not specified, the 'id' attribute is default.
   *
   * 'deftype' (string)  If specified, sets the default 'type' attribute.
   *                     If not specified, we use 'text' by default.
   *
   * 'add'     (array)   If specified, we add it's contents to $attrs.
   *
   * 'map'     (mixed)   If specified, it can be an associative array
   *                     of attributes to map to other attributes if not
   *                     set directly. It can also be boolean true, in which
   *                     case it's the same as passing:
   *                      ["id"=>"name", "name"=>"id"]
   *                     The latter is useful if you want to ensure 'id' and
   *                     'name' are both the same without passing both.
   *
   * @param mixed $map   (Optional) Another way to specify the 'map' option.
   *                     NOTE: If both $map and $opts['map'] are set, the
   *                     latter will take precedence.
   *
   * @return mixed  See return_value().
   */
  public function input ($attrs=[], Array $opts=[], $map=null)
  {
    if (isset($opts['def']))
    {
      $defAttr = $opts['def'];
    }
    else
    {
      $defAttr = 'id';
    }
    if (isset($opts['deftype']))
    {
      $defType = $opts['deftype'];
    }
    else
    {
      $defType = 'text';
    }
    if (is_string($attrs))
    {
      $attrs = [$defAttr=>$attrs, 'type'=>$defType];
    }
    elseif (!isset($attrs[$defAttr]))
    {
      throw new Exception("Cannot continue without primary field.");
    }
    elseif (!isset($attrs['type']))
    {
      $attrs['type'] = $defType;
    }

    // Add any fields specified in the options.
    if (isset($opts['add']) && is_array($opts['add']))
    {
      $attrs += $opts['add'];
    }

    if (isset($opts['map']))
    {
      $map = $opts['map'];
    }

    // Map missing fields to other existing fields.
    if (isset($map))
    { 
      if (is_bool($map) && $map)
      { // A default map for name to id and back again.
        $map = ['id'=>'name','name'=>'id'];
      }
      if (is_array($map))
      {
        foreach ($map as $target => $source)
        { 
          if (!isset($attrs[$target]) && isset($attrs[$source]))
          {
            $attrs[$target] = $attrs[$source];
          }
        }
      }
    }

    // Create our object.
    $input = new \SimpleXMLElement('<input/>');

    // Now some automated stuff based on our translation framework.
    if (isset($this->translate))
    { // First, if we have no value, let's get it from the translations.
      if (!isset($attrs['value']))
      {
        if (isset($opts['text_ns']))
        {
          $prefix = $opts['text_ns'];
        }
        else
        {
          $prefix = '';
        }
        $name = $attrs[$defAttr];
        $attrs['value'] = $this->translate[$prefix.$name];
      }
      // Next, if we have no title, and there is a tooltip prefix,
      // let's see if there is a tooltip for us.
      if (!isset($attrs['title']) && isset($opts['tooltip_ns']))
      {
        $prefix = $opts['tooltip_ns'];
        $name = $attrs[$defAttr];
        $tooltip = $this->translate[$prefix.$name];
        if ($tooltip != $prefix.$name)
        {
          $attrs['title'] = $tooltip;
        }
      }
    }
    foreach ($attrs as $name => $value)
    {
      $input->addAttribute($name, $value);
    }
    return $this->return_value($input, $opts);
  }

  /**
   * Build an '<input type="button"/>' element.
   *
   * This calls the input() method, but automatically sets the
   * 'deftype' option to 'button'.
   *
   * @param mixed $attrs  See input().
   * @param array $opts   See input().
   * @param mixed $map    See input().
   *
   * @return mixed  See return_value().
   */
  public function button ($attrs=[], Array $opts=[], $map=null)
  {
    $opts['deftype'] = 'button';
    return $this->input($attrs, $opts, $map);
  }

  /**
   * Build an '<input type="submit"/>' element.
   *
   * This calls the input() method, but automatically sets the
   * 'def' option to 'name' and the 'deftype' option to 'submit'.
   *
   * @param mixed $attrs  See input().
   * @param array $opts   See input().
   * @param mixed $map    See input().
   *
   * @return mixed  See return_value().
   */
  public function submit ($attrs=[], Array $opts=[], $map=null)
  {
    $opts['def'] = 'name';
    $opts['deftype'] = 'submit';
    return $this->input($attrs, $opts);
  }

  /**
   * Extract plain text from HTML.
   *
   * @param String $text      The input HTML text.
   * @param Array  $filters   An array of strip filters to apply, in order.
   *                          Default: ['R','E']
   *
   * Filters:
   *
   *   'T'         Pass through strip_tags()
   *   'E'         Pass through htmlspecialchars_decode()
   *   'EE'        Pass through html_entity_decode()
   *   'R'         Use a custom regex to strip HTML tags (including <script/>)
   *   'RR'        Extend the regex to strip even more.
   *
   * Any of the multiple letter tags are higher level extensions of the single
   * letter version, and thus you shouldn't ever have more than one in an
   * array, so ['E','EE'] or ['R','RR'] are redundant.
   */
  public static function strip ($text, $filters=['R','E'])
  {
    // Contains our various strip filter functions.
    $filter_functions =
    [
      'T' => function ($in, $lvl)
      {
        return strip_tags($in);
      },
      'E' => function ($in, $lvl)
      {
        if ($lvl > 1)
        {
          return html_entity_decode($in, ENT_QUOTES | ENT_HTML5);
        }
        else
        {
          return htmlspecialchars_decode($in, ENT_HTML5);
        }
      },
      'R' => function ($in, $lvl)
      {
        $search = 
        [
          "'<script[^>]*?>.*?</script>'si",     // Strip out javascript
          "'<[/!]*?[^<>]*?>'si",                // Strip out HTML tags
          "'([\r\n])[\s]+'",                    // Strip out white space
        ];

        if ($lvl > 1)
        {
          $search = array_merge($search,
          [
            "'&(quot|#34);'i",                  // Replace HTML entities
            "'&(amp|#38);'i",
            "'&(lt|#60);'i",
            "'&(gt|#62);'i",
            "'&(nbsp|#160);'i",
#            "'&(iexcl|#161);'i",
#            "'&(cent|#162);'i",
#            "'&(pound|#163);'i",
#            "'&(copy|#169);'i",
#            "'&#(\d+);'e",                        // Evaluate as PHP
          ]);
        }

        $replace = 
        [
          "",
          "",
          "\1",
        ];

        if ($lvl > 1)
        {
          $replace = array_merge($replace,
          [
            "\"",
            "&",
            "<",
            ">",
            " ",
#            chr(161),
#            chr(162),
#            chr(163),
#            chr(169),
#            "chr(\1)",
          ]);
        }

#        error_log(json_encode(['l'=>$lvl, 's'=>$search, 'r'=>$replace]));

        return preg_replace($search, $replace, $in);
      },
    ];

    foreach ($filters as $filter)
    {
      $lvl  = strlen($filter);
      $ftag = strtoupper(substr($filter, 0, 1));
#      error_log(json_encode(['lvl'=>$lvl, 'ftag'=>$ftag]));
      if (isset($filter_functions[$ftag]))
      {
        $text = $filter_functions[$ftag]($text, $lvl);
      }
    }

    return $text;
  }

}
