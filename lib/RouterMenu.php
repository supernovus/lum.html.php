<?php

namespace Lum\HTML;

/**
 * A menu generator that integrates with the Router plugin.
 */
class RouterMenu
{
  protected $parent;

  public function __construct ($opts=[])
  {
    if (isset($opts['parent']))
      $this->parent = $opts['parent'];
  }

  public function getStrings ()
  {
    if (isset($this->parent) && is_callable([$this->parent, 'getStrings']))
    {
      return $this->parent->getStrings();
    }
  }

  /**
   * Build a menu.
   *
   * Given some information and a menu definition, this will
   * generate an HTML menu using a specific layout.
   *
   * @param Array  $menu      The menu definition.
   * @param Object $context   The RouteContext object for the request.
   * @param Array  $opts      Options to change the behavior.
   *
   * @return SimpleXMLElement  An HTML structure representing the menu.
   *
   * Menu Formats
   *
   * There are a few accepted menu formats. We detect them
   * automatically as we build the menu.
   *
   * If you use a flat (non-associative) array, then you will
   * need to supply the 'route' and 'name' parameters within the
   * item definition (which is an associative array).
   * 
   * Otherwise, if the menu itself is an associate array, the key
   * will be used as the 'url' or 'name' tags if one or both of
   * them is missing, but only if it is a string.
   *
   * Options
   *
   *   root        A SimpleXMLElement object, or an HTML string.
   *               This represents the menu root object.
   *               Default: <div class="menu" />
   *
   *   itemel      If specified, menu items will be wrapped in this container,
   *               and any applicable CSS classes will be applied to it.
   *               If not specified, items are added as <a/> tags directly.
   *
   *   show        A list of show rules. Show rules can be applied to menu
   *               items, and can be closures or callables that return true
   *               or false, or can be a value that must match in the menu
   *               item definition.
   *
   *   builders    Menu items that are actually generated by a closure or
   *               callable that is passed the menu XML object.
   *
   *   handlers    Menu items can be passed to closures/callables that will
   *               can apply classes or perform other manipulations.
   *
   *   current_class  The class name for the "current" menu item.
   *                  Default: 'current'.
   *
   *   item_class     An optional class name for any menu items.
   *
   */
  public function buildMenu ($menu, $context, $opts=[])
  { 
    // The root element.
    if (isset($opts['parent']))
    {
      $parent = $opts['parent'];
      if (isset($opts['element']))
        $elname = $opts['element'];
      else
        $elname = 'div';

      $container = $parent->addChild($elname);
      
      if (isset($opts['attrs']))
      {
        $attrs = $opts['attrs'];
        foreach ($attrs as $attrname => $attrval)
        {
          $container->addAttribute($attrname, $attrval);
        }
      }
    }
    elseif (isset($opts['root']))
    {
      $container = $opts['root'];
      if (is_string($container))
      {
        $container = new \SimpleXMLElement($container);
      }
    }
    else
    {
      $container = new \SimpleXMLElement('<div class="menu" />');
    }

    // A wrapper element.
    $item_el = isset($opts['item_el']) ? strtolower($opts['item_el']) : Null;

    // An inner wrapper element.
    $inner_el = isset($opts['inner_el']) ? strtolower($opts['inner_el']) : null;

    // Custom rules to filter out certain menu items.
    $show_rules = isset($opts['show']) ? $opts['show'] : [];

    // Custom rules to build the items rather than using the built-in logic.
    $build_rules = isset($opts['builders']) ? $opts['builders'] : [];

    // Custom rules to apply styles or other modifications.
    $post_rules = isset($opts['handlers']) ? $opts['handlers'] : [];

    // Label element name.
    $label_el = isset($opts['label_class']) ? $opts['label_class'] : 'span';

    // Current class
    $current_class = isset($opts['current_class']) ? $opts['current_class'] :
      'current';

    // Item class
    $item_class = isset($opts['item_class']) ? $opts['item_class'] : null;

    // Inner class
    $inner_class = isset($opts['inner_class']) ? $opts['inner_class'] : null;

    // Icon element (must be used with inner_el)
    $icon_el = isset($opts['icon_el']) ? $opts['icon_el'] : 'i';
    $icon_class = isset($opts['icon_class']) ? $opts['icon_class'] : 'icon';
    $icon_pos = isset($opts['icon_pos']) ? $opts['icon_pos'] : 0;

    // Okay, let's do this.
    foreach ($menu as $key => $def)
    {
      if (is_string($def))
      { // Assume it's the name.
        $def = ['name'=>$def];
      }

      // Let's see if there are any filters that apply.
      $filtered = False;
      foreach ($show_rules as $rkey => $rule)
      { 
        // We only perform the checks on defs that have the rule.
        if (isset($def[$rkey]))
        {
          if (is_callable($rule))
          { // Our closure will return True or False.
            if (!$rule($def, $key, $context))
            { // If we get False, we skip this menu item.
              $filtered = True;
              break;
            }
          }
          else
          { // We check to see if the values match up.
            if ($def[$rkey] != $rule)
            { // Our show rule did not match, skip this menu item.
              $filtered = True;
              break;
            }
          }
        }
      }
      if ($filtered) 
      { // One of the show rules did not match, skip this item.
        continue; 
      }

      // There are several things that can build items, let's keep track.
      $built = false;

      // Check for sub-menus
      if (isset($def['submenu']) && isset($def['items']))
      {
        $subopts = $def['submenu'];
        foreach ($opts as $opt => $val)
        {
          if (!isset($subopts[$opt]))
            $subopts[$opt] = $val;
        }
        $subopts['parent'] = $container;
        $submenu = $def['items'];
        $item = $this->buildMenu($submenu, $context, $subopts);
        $built = true;
      }

      // Next, deal with custom builders.
      if (!$built)
      {
        foreach ($build_rules as $rkey => $rule)
        {
          if (isset($def[$rkey]))
          {
            if (is_callable($rule))
            {
              $item = $rule($def, $key, $context, $container);
            }
            else
            {
              $text = $rule;
              $strings = $this->getStrings();
              if (isset($strings))
              {
                $text = $strings[$text];
              }
              $item = $container->addChild($label_class, $text);
            }
            $built = True;
          }
        }
      }

      if (!$built)
      {
        if (isset($def['route']))
        {
          $mroute = $def['route'];
        }
        elseif (isset($def['url']))
        {
          $url   = $def['url'];
          $mroute = Null;
        }
        elseif (is_string($key) && substr($key, 0, 1) != '#')
        {
          $mroute = $key;
        }
        else
        { // skip it.
          continue;
        }

        $current = False;
        if (isset($mroute))
        {
          $router = $context->router;
          $croute = $context->route;
          $url = 
            $router->build($mroute, $context->path_params, ['strict'=>False]);

          if (is_null($url))
          {
            $url = '#';
          }

          if ($croute->name == $mroute)
            $current = True;
        }
        elseif (isset($url, $def['matchPath']) 
          && is_array($def['matchPath'])
          && count($def['matchPath']) == 2)
        {
          $offset   = $def['matchPath'][0];
          $findpath = $def['matchPath'][1];
          if ($context->path[$offset] == $findpath)
          {
            $current = true;
          }
        }

        // Get our item name/label.
        if (isset($def['name']))
        {
          $name = $def['name'];
        }
        elseif (is_string($key))
        {
          $name = $key;
        }
        else
        {
          continue;
        }

        $strings = $this->getStrings();
        if (isset($strings))
        {
          $name = $strings[$name];
        }

        $icon_name = isset($def['icon']) ? $def['icon'] : null;

        $inner = $link = $icon = null;
        if (isset($item_el) && $item_el != 'a')
        { // We're using a custom container. We put an <a/> within it.
          $item = $container->addChild($item_el);
          if (isset($inner_el))
          {
            $link = $item->addChild('a');
            if (isset($icon_name) && $icon_pos == 0)
              $icon = $link->addChild($icon_el, ' ');
            $inner = $link->addChild($inner_el, $name);
            if (isset($icon_name) && $icon_pos == 1)
              $icon = $link->addChild($icon_el, ' ');
          }
          else
          {
            $link = $item->addChild('a', $name);
          }
          if (isset($def['attrs']))
          {
            foreach ($def['attrs'] as $ak => $av)
            {
              $link->addAttribute($ak, $av);
            }
          }
          $link->addAttribute('href',  $url);
        }
        else
        { // We're using a raw <a/> tag (my preference.)
          if (isset($inner_el))
          {
            $item = $container->addChild('a');
            if (isset($icon_name) && $icon_pos == 0)
              $icon = $item->addChild($icon_el, ' ');
            $inner = $item->addChild($inner_el, $name);
            if (isset($icon_name) && $icon_pos == 1)
              $icon = $item->addChild($icon_el, ' ');
          }
          else
          {
            $item = $container->addChild('a', $name);
          }
          if (isset($def['attrs']))
          {
            foreach ($def['attrs'] as $ak => $av)
            {
              $item->addAttribute($ak, $av);
            }
          }
          $item->addAttribute('href', $url);
        }

        if (isset($inner_el, $inner, $inner_class))
        {
          $inner->addChild('class', $inner_class);
        }

        if (isset($inner, $icon))
        {
          $icon->addAttribute('class', $icon_name.' '.$icon_class);
        }

        $classes = [];
        // Add the current class if applicable.
        if ($current)
        {
          $classes[] = $current_class;
        }
        // Add the item class if defined.
        if (isset($item_class))
        {
          $classes[] = $item_class;
        }
        if (isset($def['class']))
        {
          $classes[] = $def['class'];
        }
        if (count($classes) > 0)
        {
          $item->addAttribute('class', join(' ', $classes));
        }
      }

      // Now deal with post-build handlers.
      foreach ($post_rules as $rkey => $rule)
      {
#        error_log("Testing for post rule '$rkey'");
        if (isset($def[$rkey]))
        {
#          error_log(" -- found");
          if (is_callable($rule))
          {
#            error_log("calling $rkey rule for $key");
#            $res = 
            $rule($def, $key, $context, $item, $link, $inner);
#            error_log(" -- result: ".json_encode($res));
          }
        }
      }
    }

    // One last sanity check.
    if ($container->count() == 0)
    {
      $container->addChild('span', '&nbsp;');
    }

    return $container;
  }

}
