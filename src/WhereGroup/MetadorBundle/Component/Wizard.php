<?php

namespace WhereGroup\MetadorBundle\Component;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Wizard
 * @package WhereGroup\MetadorBundle\Component
 * @author A. R. Pour
 */
class Wizard
{
    protected $container;
    private $path = null;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->path = __DIR__ . "/../Data/";
    }

    /**
     * @param $hierarchyLevel
     * @return array
     */
    public function getExamples($hierarchyLevel)
    {
        $folders = array('keywords/all/');
        $keywords = array();

        if (trim($hierarchyLevel) === 'service') {
            $folders = array_merge($folders, array('keywords/service/'));
        } elseif (trim($hierarchyLevel) === 'dataset') {
            $folders = array_merge($folders, array('keywords/dataset/'));
        }

        foreach ($folders as $folder) {
            foreach (scandir($this->path . $folder) as $file) {
                if (substr($file, -5) != ".json") {
                    continue;
                }

                $json = json_decode(file_get_contents($this->path . $folder . $file));
                $keywords = array_merge($keywords, (array)$json);
            }
        }

        return array(
            'keywords' => $keywords,
            'conformity' => (array)json_decode(file_get_contents($this->path . '/conformity.json')),
            'otherconstraints' => json_decode(file_get_contents($this->path . '/otherconstraints.json')),
            'bbox' => json_decode(file_get_contents($this->path . '/bbox.json')),
        );
    }
}
