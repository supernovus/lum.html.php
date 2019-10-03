<?php

namespace Lum\HTML;

/**
 * A magic all-on-one HTML helper.
 *
 * Can auto-load instances of Lum\HTML\Simple, Lum\HTML\Components,
 * Lum\HTML\ClassicMenu, Lum\HTML\RouteMenu, and Lum\HTML\Element.
 *
 * In addition, any unrecognized methods are handled by the magical
 * __call() method which first checks to see if the method exists in the
 * Lum\HTML\Simple instance, and if not, passes it to Lum\HTML\Components
 * as a view to load.
 */
class Helper
{
  // Options to pass to the instances.
  protected $instance_opts = [];

  // A cache for loaded library instances.
  protected $libcache = [];

  /**
   * Build a new HTML helper object.
   *
   * @param array $opts  (Optional) Parameters to pass to the instances.
   *                     We will automatically add one called 'parent' which
   *                     is a reference to this object.
   */
  public function __construct (Array $opts=[])
  {
    if (is_array($opts))
    {
      $this->instance_opts = $opts;
    }
    $this->instance_opts['parent'] = $this;
  }

  /**
   * Get a ClassicMenu library instance.
   *
   * Will be cached, so that further calls return the same instance.
   *
   * @return Lum\HTML\ClassicMenu
   */
  public function getClassicMenu ()
  {
    if (isset($this->libcache['cmenu']))
      $lib = $this->libcache['cmenu'];
    else
      $lib = $this->libcache['cmenu'] = new ClassicMenu($this->instance_opts);
    return $lib;
  }

   /**
   * Get a RouterMenu library instance.
   *
   * Will be cached, so that further calls return the same instance.
   *
   * @return Lum\HTML\RouterMenu
   */
  public function getRouterMenu ()
  {
    if (isset($this->libcache['rmenu']))
      $lib = $this->libcache['rmenu'];
    else
      $lib = $this->libcache['rmenu'] = new RouterMenu($this->instance_opts);
    return $lib;
  }

  /**
   * Get a Lum\UI\Strings instance.
   *
   * If the Lum\HTML\Simple instance has been created, we return it's
   * $translate property.
   *
   * Otherwise if there was a 'translate' property passed to the constructor,
   * we return it.
   *
   * If neither are true, we return null.
   *
   * @return (object|null)
   */
  public function getStrings ()
  {
    if (isset($this->libcache['simple']))
    {
      return $this->libcache['simple']->translate;
    }
    elseif (isset($this->instance_opts['translate']))
    {
      return $this->instance_opts['translate'];
    }
  }

  /**
   * Get a Simple library instance.
   *
   * Will be cached, so that further calls return the same instance.
   *
   * @return Lum\HTML\Simple
   */
  public function getSimple ()
  {
    if (isset($this->libcache['simple']))
      $lib = $this->libcache['simple'];
    else
      $lib = $this->libcache['simple'] = new Simple($this->instance_opts);
    return $lib;
  }

  /**
   * Get a Components library instance.
   *
   * Will be cached, so that further calls return the same instance.
   *
   * @return Lum\HTML\Components
   */
  public function getComponents()
  {
    if (isset($this->libcache['components']))
      $lib = $this->libcache['components'];
    else
      $lib = $this->libcache['components'] = new Components($this->instance_opts);
    return $lib;
  }

  /**
   * Build an element progmatically with the Element library.
   *
   * This is not cached, every time you call it, it returns a new instance.
   * 
   * @param mixed $xml   See Element::__construct()
   * @param array $opts  See Element::__construct()
   *
   * @return Lum\HTML\Element
   */
  public function element ($xml, $opts=[])
  {
    return new Element($xml, $opts);
  }

  /**
   * Build a menu, using the ClassicMenu library.
   *
   * @param mixed $menu  See ClassicMenu::buildMenu()
   * @param array $opts  See ClassicMenu::buildMenu()
   *
   * @return mixed  See Simple::return_value()
   */
  public function menu ($menu, $opts=[])
  {
    $lib = $this->getClassicMenu();
    $container = $lib->buildMenu($menu, $opts);
    return $this->return_value($container, $opts);
  }

  /**
   * Build a menu, using the RouterMenu library.
   *
   * @param mixed $menu  See ClassicMenu::buildMenu()
   * @param array $opts  See ClassicMenu::buildMenu()
   *
   * @return mixed  See Simple::return_value()
   */
  public function routemenu ($menu, $context, $opts=[])
  {
    $lib = $this->getRouterMenu();
    $container = $lib->buildMenu($menu, $context, $opts);
    return $this->return_value($container, $opts);
  }

  /**
   * Include a component.
   *
   * Pass through to Components::load(), see that method for more information.
   *
   * @param string $view  The name of the component we are loading.
   * @param array $data  Data to pass to the view.
   * @return string
   */
  public function get ($view, $data=[])
  {
    $components = $this->getComponents();
    return $components->load($view, $data);
  }

  /**
   * This is where the real magic happens.
   *
   * For any method call that isn't a method directly defined in this class,
   * first we check to see if the method is callable in the Simple class.
   * If it is, we pass it directly to the same named method in the Simple
   * class with all the same parameters.
   *
   * If it isn't found, then we assume it's the name of a view to load
   * using the Components class (via the get() method.)
   *
   * When passing a view to load to the get() method, any parameters passed
   * that are associative arrays will be added to the data array to be passed
   * to the get() function. The merging is done using
   * $finalArray += $addedArray so order matters, as keys already in the
   * $finalArray will not be overwritten by the same keys in each $addedArray.
   * Parameters that are not arrays are ignored entirely for calls to get().
   *
   */
  public function __call ($method, $params)
  {
    $simple = $this->getSimple();
    $callable = [$simple, $method];
    if (is_callable($callable))
    {
      return call_user_func_array($callable, $params);
    }

    // If we reached here, no callable was found in Simple, assume it's
    // a view to load, and handle accordingly.
    $data = [];
    if (count($params))
    {
      foreach ($params as $param)
      {
        if (is_array($param))
        {
          $data += $param;
        }
      }
    }
    return $this->get($method, $data);
  }

}

