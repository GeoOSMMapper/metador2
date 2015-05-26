<?php

namespace WhereGroup\Plugin\ImportBundle\Component;

use WhereGroup\CoreBundle\Component\XmlParser;
use WhereGroup\CoreBundle\Component\XmlParserFunctions;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class MetadataImport
 * @package WhereGroup\Plugin\ImportBundle\Component
 * @author A.R.Pour
 */
class MetadataImport implements MetadataImportInterface
{
    /** @var KernelInterface  */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function __destruct()
    {
        unset($this->kernel);
    }

    /**
     * Convert a XML to Metador data object.
     * @param $xml
     * @param $conf
     * @return array
     */
    public function load($xml)
    {
        $profile = $this->getProfileString($xml);

        if (empty($profile)) {
            return false;
        }

        $parser = new XmlParser($xml, new XmlParserFunctions());
        $filename = $this->getSchemaFile('Profile' . ucfirst(strtolower($profile)) . 'Bundle');

        $parser->loadSchema(file_get_contents($filename));

        $array = $parser->parse();

        return isset($array['p']) ? $array['p'] : array();
    }

    /**
     * @param $bundle
     * @return array|string
     */
    public function getSchemaFile($bundle)
    {
        return $this->kernel->locateResource('@' . $bundle . '/Resources/import/metadata.xml.json');
    }

    /**
     * @param $string
     * @return mixed
     */
    public function getProfileString($string)
    {
        $dom = new \DOMDocument();
        $dom->loadXml($string);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('gmd', 'http://www.isotc211.org/2005/gmd');
        $xpath->registerNamespace('gco', 'http://www.isotc211.org/2005/gco');
        $xpath->registerNamespace('srv', 'http://www.isotc211.org/2005/srv');
        $xpath->registerNamespace('gml', 'http://www.opengis.net/gml');
        $xpath->registerNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        $profile = null;

        $result = $xpath->query("/*/gmd:hierarchyLevel/gmd:MD_ScopeCode/text()");
        if ($result->length === 1) {
            $profile = $result->item(0)->nodeValue;
        }

        $result = $xpath->query("/*/gmd:hierarchyLevel/gmd:MD_ScopeCode/@codeListValue");
        if ($result->length === 1) {
            $profile = $result->item(0)->value;
        }

        if ($profile === 'series') {
            return 'dataset';
        }

        return $profile;
    }
}