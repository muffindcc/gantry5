<?php
namespace Gantry\Admin\Blocks;

use Gantry\Component\Config\Blueprints;
use Gantry\Component\Config\CompiledBlueprints;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Blocks
{
    protected $container;
    protected $files;
    protected $blocks;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function all()
    {
        if (!$this->blocks)
        {
            $files = $this->locateBlocks();

            $this->blocks = [];
            foreach ($files as $key => $file) {
                $filename = key($file);
                $this->blocks[$key] = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content();
            }
        }

        return $this->blocks;
    }

    public function group()
    {
        $blocks = $this->all();

        $list = [];
        foreach ($blocks as $name => $style) {
            $type = isset($style['type']) ? $style['type'] : 'block';
            $list[$type][$name] = $style;
        }

        return $list;
    }

    public function get($id)
    {
        if ($this->blocks[$id]) {
            return $this->blocks[$id];
        }

        $files = $this->locateBlocks();

        if (empty($files[$id])) {
            throw new \RuntimeException("Settings for '{$id}' not found.", 404);
        }

        $filename = key($files[$id]);
        $particle = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content();

        return $particle;
    }

    protected function locateBlocks()
    {
        if (!$this->files) {
            /** @var UniformResourceLocator $locator */
            $locator = $this->container['locator'];
            $paths = $locator->findResources('gantry-blocks://');

            $this->files = (new ConfigFileFinder)->listFiles($paths);
        }

        return $this->files;
    }
}