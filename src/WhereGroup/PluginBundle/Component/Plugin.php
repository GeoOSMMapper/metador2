<?php

namespace WhereGroup\PluginBundle\Component;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;

class Plugin
{
    protected $container;
    protected $rootDir;
    protected $configurationFile;
    protected $pluginPaths = array();
    protected $plugins = array();

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        // get plugin path's
        $this->rootDir           = $this->container->get('kernel')->getRootDir() . '/';
        $this->configurationFile = $this->rootDir . 'config/plugins.yml';
        $this->pluginPaths       = array(
            $this->rootDir . '../src/WhereGroup/Plugin/',
            $this->rootDir . '../src/User/Plugin/'
        );

        $this->updateConfiguration();
    }

    public function __destruct()
    {
        unset($this->container);
    }


    public function getPlugins()
    {
        return $this->plugins;
    }

    public function update($request, $redirect)
    {
        $plugins = empty($request['plugin']) ? array() : $request['plugin'];

        foreach ($this->plugins as $key => $plugin) {
            // enable
            if (isset($plugins[$key])) {
                $this->plugins[$key]['active'] = true;
            // disable
            } else {
                $this->plugins[$key]['active'] = false;
            }
        }

        $this->writeYaml(
            $this->configurationFile,
            array('plugins' => $this->plugins)
        );

        $this->clearCache($redirect);
    }

    public function updateConfiguration()
    {
        $configuration = $this->getConfiguration();
        $plugins       = $this->findPlugins();

        foreach ($configuration['plugins'] as $key => $plugin) {
            if (!isset($plugins[$key])) {
                unset($configuration['plugins'][$key]);
            }
        }

        foreach ($plugins as $name => $plugin) {
            if (!isset($configuration['plugins'][$name])) {
                $plugin['active'] = false;
                $plugin['new'] = true;
                $configuration['plugins'][$name] = $plugin;
            } elseif (isset($configuration['plugins'][$name]['new'])) {
                unset($configuration['plugins'][$name]['new']);
            } else {
                $configuration['plugins'][$name]
                    = array_merge($configuration['plugins'][$name], $plugin);
            }
        }

        $this->plugins = $configuration['plugins'];
        $this->writeYaml($this->configurationFile, $configuration);
    }

    public function getConfiguration()
    {
        if (!file_exists($this->configurationFile)) {
            $this->writeYaml($this->configurationFile, array('plugins' => array()));
        }

        return $this->readYaml($this->configurationFile);
    }

    public function findPlugins()
    {
        $plugins = array();
        $finder = new Finder();

        $finder->files()
            ->in($this->pluginPaths)
            ->path('Resources/config/')
            ->name('plugin.yml');

        foreach ($finder as $file) {
            $pluginDefinition = $this->readYaml($file);

            $plugins[key($pluginDefinition)]
                = $pluginDefinition[key($pluginDefinition)];
        }

        return $plugins;
    }

    public function readYaml($file)
    {
        return Yaml::parse($file);
    }

    public function writeYaml($file, $array)
    {
        file_put_contents($file, Yaml::dump($array, 2));

        return $this;
    }

    public function clearCache($redirect)
    {
        $env     = '--env=' . $this->container->get('kernel')->getEnvironment();
        $console = $this->rootDir . 'console';

        $fs  = new Filesystem();
        $fs->remove($this->container->getParameter('kernel.cache_dir'));

        sleep(4);

        $commands = array(
            "$console assets:install --no-debug $env $this->rootDir../web/",
            "$console cache:warmup --no-debug $env"
        );

        foreach ($commands as $command) {
            $process = new Process($command);
            $process->run();
        }


    }
}