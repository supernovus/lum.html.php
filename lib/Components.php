<?php

namespace Lum\HTML;

class Components
{
  /**
   * The Lum namespace where we'll load component views from.
   *
   * This is highly recommended, as without it, we can only load directly 
   * named PHP files. Pass the 'include' parameter to the constructor to
   * set this at that time.
   *
   * @var string
   */
  public $include_ns;

  /**
   * Build a new HTML component loader object.
   *
   * @param array $opts  Named options:
   *
   * 'include' (string)  The name of a Lum Core view loader to use to
   *                     load our components. This is highly recommended.
   */
  public function __construct ($opts=[])
  {
    if (isset($opts['include']))
      $this->include_ns = $opts['include'];
  }

  /**
   * Include a component.
   *
   * @param string $view  The name of the component we are loading.
   *
   * If $this->include_ns is set, then it's assumed to be the name of a
   * view loader plugin in the Lum Core object, and the $view will be loaded
   * from it.
   *
   * If $this->include_ns is NOT set, then the $view must be the path to the
   * file we want to parse using \Lum\Core::get_php_content().
   *
   * @param array $data  Data to pass to the view.
   *
   * @return string  The results from parsing the view.
   */
  public function load ($view, $data=[])
  {
    if (isset($this->include_ns))
    {
      $ns = $this->include_ns;
      $core = \Lum\Core::getInstance();
      if (!isset($core->$ns))
      {
        throw new \Exception("No such Lum namespace: $ns");
      }
      if (!($core->$ns instanceof \Lum\Plugins\Views))
      {
        throw new \Exception("Lum namespace '$ns' is not a view loader");
      }
      $content = $core->$ns->load($view, $data);
    }
    elseif (file_exists($view))
    {
      $content = \Lum\Core::get_php_content($view, $data);
    }
    else
    {
      throw new \Exception("Invalid view passed to HTML\Components::load()");
    }
    return $content;
  }

}
