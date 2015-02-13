<?php
namespace Gantry\Component\Menu;

use RocketTheme\Toolbox\ArrayTraits\ArrayAccessWithGetters;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\Iterator;

abstract class AbstractMenu implements \ArrayAccess, \Iterator
{
    use ArrayAccessWithGetters, Iterator, Export;

    protected $default;
    protected $base;
    protected $active;
    protected $params;
    protected $config;

    /**
     * @var array|Item[]
     */
    protected $items;

    protected $defaults = [
        'menu' => 'mainmenu',
        'base' => '/',
        'startLevel' => 1,
        'endLevel' => 0,
        'showAllChildren' => true,
        'highlightAlias' => true,
        'highlightParentAlias' => true,
        'window_open' => null
    ];

    abstract public function __construct();
    abstract public function getMenus();
    abstract public function getMenuItems();
    abstract public function config();

    /**
     * Get base menu item.
     *
     * If itemid is not specified or does not exist, return active menu item.
     * If there is no active menu item, fall back to home page for the current language.
     * If there is no home page, return null.
     *
     * @param   string  $path
     *
     * @return  string
     */
    abstract protected function calcBase($path);

    /**
     * Get a list of the menu items.
     *
     * Logic has been mostly copied from Joomla 3.4 mod_menu/helper.php (joomla-cms/staging, 2014-11-12).
     * We should keep the contents of the function similar to Joomla in order to review it against any changes.
     *
     * @param  array  $params
     *
     * @return array
     */
    abstract protected function getList(array $params);

    public function instance(array $params = [])
    {
        if (!isset($params['config'])) {
            $params = $this->defaults;
        }   else {
            $params = $params['config'] + $this->defaults;
        }

        $menus = $this->getMenus();

        if (!in_array($params['menu'], $menus)) {
            throw new \RuntimeException('Menu not found', 404);
        }

        $instance = clone $this;
        $instance->params = $params;

        $instance->items = $instance->getList($params);

        return $instance;
    }

    public function name()
    {
        return $this->params['menu'];
    }

    public function root()
    {
        return $this->offsetGet('');
    }

    public function ordering()
    {
        $list = [];
        foreach ($this->items as $name => $item) {
           foreach ($item->groups() as $col => $children) {
                foreach ($children as $child) {
                    $list[$name][$col][] = $child->path;
                }
            }
        }

        return $list;
    }

    public function items()
    {
        $list = [];
        foreach ($this->items as $key => $item) {
            if ($key !== '') {
                $list[$item->path] = $item->toArray();
            }
        }

        return $list;
    }

    /**
     * @return object
     */
    public function getBase()
    {
        return $this->offsetGet($this->base);
    }

    /**
     * @return object
     */
    public function getDefault()
    {
        return $this->offsetGet($this->default);
    }

    /**
     * @return object
     */
    public function getActive()
    {
        return $this->offsetGet($this->active);
    }

    public function isActive($item)
    {
        if ($item->path && strpos($this->base, $item->path) === 0) {
            return true;
        }

        return false;
    }

    public function isCurrent($item)
    {
        return $item->path == $this->getActive()->path;
    }

    /**
     * @param array|Item[] $items
     * @param array $ordering
     * @param string $path
     */
    protected function sortAll(array &$items, array &$ordering, $path = '')
    {
        if (!isset($items[$path]) || !$items[$path]->hasChildren()) {
            return;
        }

        /** @var Item $item */
        $item = $items[$path];
        if ($this->isAssoc($ordering)) {
            $item->sortChildren($ordering);
        } else {
            $item->groupChildren($ordering);
        }

        foreach ($ordering as $key => &$value) {
            if (is_array($value)) {
                if ((string) $key === (string)(int) $key) {
                    $newPath = $path;
                } else {
                    $newPath = $path ? $path . '/' . $key : $key;
                }
                $this->sortAll($items, $value, $newPath);
            }
        }
    }

    protected function isAssoc(array $array)
    {
        return (array_values($array) !== $array);
    }
}