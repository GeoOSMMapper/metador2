<?php
namespace WhereGroup\MetadorBundle\Component;

/**
 * Class XmlParser
 * @package WhereGroup\MetadorBundle\Component
 * @author A. R. Pour
 */
class XmlParser
{
    /**
     * @var \DOMXPath
     */
    private $xp;

    /**
     * @var \DOMDocument
     */
    private $doc;

    /**
     * @var XmlParserFunctions
     */
    private $functions;

    /**
     * @var mixed
     */
    private $schema = null;

    /**
     * @var boolean
     */
    private $removeEmptyValues = true;

    /**
     * @var array
     */
    private $cache = null;
    private $sort = false;

    /**
     * Constructor
     * @param string $xml XML
     */
    public function __construct($xml, XmlParserFunctions $functions)
    {
        $this->doc = new \DOMDocument();

        if (!@$this->doc->loadXML($xml) || !@$this->xp = new \DOMXPath($this->doc)) {
            throw new \RuntimeException('Error while loading the XML.');
        }

        $this->functions = $functions;
    }

    /**
     *Load JSON schema
     * @param  string $json schema
     */
    public function loadSchema($json)
    {
        $array = $this->objectToArray(json_decode($json));

        if (!is_array($array) || empty($array)) {
            throw new \RuntimeException('Empty or invalid schema.');
        }

        $this->schema = json_decode(json_encode(
            $this->mergeRecursive(
                $this->objectToArray($this->schema),
                $array
            )
        ));
    }

    /**
     * Register namespaces
     * @param  array $namespaces namespaces
     */
    public function registerNamespaces($namespaces)
    {
        foreach ($namespaces as $namespaceKey => $namespaceValue) {
            $this->xp->registerNamespace($namespaceKey, $namespaceValue);
        }
    }

    /**
     * Convert XML to array by schema definition.
     */
    public function parse()
    {
        if (isset($this->schema->cmd)) {
            foreach ($this->schema->cmd as $key => $val) {
                if ($key === "addNamespaces") {
                    $this->registerNamespaces($val);
                } elseif ($key === "removeEmptyValues") {
                    $this->removeEmptyValues = $val ? true : false;
                } elseif ($key === "sortResult") {
                    $this->sort = $val ? true : false;
                }
            }
        }

        $array = $this->parseRecursive($this->schema);

        if ($this->sort) {
            ksort($array);
        }

        return $array;
    }

    /**
     * @param $object
     * @param string $name
     * @param null $context
     * @param string $path
     * @param array $commands
     * @return array|mixed
     */
    private function parseRecursive($object, $name = "", $context = null, $path = "", $commands = array())
    {
        $result = array();

        $commands['recursive'] = isset($commands['recursive']) ? $commands['recursive'] : false;
        $commands['asArray']   = isset($commands['asArray']) ? $commands['asArray'] : false;
        $commands['function']  = isset($commands['function']) ? $commands['function'] : null;

        foreach ($object as $key => $val) {
            switch($key) {
                case "cmd":
                    break;
                case "path": $path .= $val;
                    break;
                case "asArray": $commands['asArray'] = $val;
                    break;
                case "recursive": $commands['recursive'] = $val;
                    break;
                case "function": $commands['function'] = $val;
                    break;
                case "data":
                    if (is_string($val) && isset($this->cache[$val])) {
                        $result = $this->parseData($this->cache[$val], $name, $path, $context, $commands);
                    } else {
                        if (!isset($this->cache[$name])) {
                            $this->cache[$name] = $val;
                        }
                        $result = $this->parseData($val, $name, $path, $context, $commands);
                    }

                    if (!is_null($commands['function'])) {
                        $result = $this->functions->get($commands['function'], $result);
                    }

                    break;
                default:
                    if (is_object($val)) {
                        $tmp = $this->parseRecursive($val, $key, $context, $path, $commands);
                    } elseif (is_array($val) && count($val) >= 2) {

                        if (is_null($val[0])) {
                            $tmp = $this->functions->get($val[1], null, array_slice($val, 2));
                        } else {
                            $tmp = $this->getValue($path . $val[0], $context);
                            $tmp = $this->functions->get($val[1], $tmp, array_slice($val, 2));
                        }
                    } else {
                        $tmp = $this->getValue($path.$val, $context);
                    }

                    if ($this->removeEmptyValues
                        && ($tmp === "" || $tmp === array())
                        && (is_array($val) && isset($val[1]) && $val[1] !== 'asArray')) {
                        continue;
                    }

                    $result[$key] = $tmp;
            }
        }

        return $result;
    }

    /**
     * @param $data
     * @param $name
     * @param $path
     * @param $context
     * @param $commands
     * @return array
     */
    private function parseData($data, $name, $path, $context, $commands)
    {
        if (!is_object($data)) {
            return array();
        }

        $tmp = array();

        if ($context == null) {
            $nodes = $this->xp->query($path);
        } else {
            $nodes = $this->xp->query($path, $context);
        }

        if ($nodes) {
            foreach ($nodes as $node) {
                $dataRecTmp = array();

                if ($commands['recursive']) {
                    $dataRecTmp = $this->parseData($data, $name, $path, $node, $commands);
                }

                $dataTmp = $this->parseRecursive($data, $name, $node);

                $tmp[] = empty($dataRecTmp) ? $dataTmp : array_merge(
                    $dataTmp,
                    array($name => $dataRecTmp)
                );
            }
        }

        return $commands['asArray'] ? $tmp : $this->getCleanArray($tmp);
    }

    /**
     * Returns value.
     *
     * @param string $xpath
     * @param \DOMNode $context
     * @return mixed
     */
    private function getValue($xpath, $context = null)
    {
        $result = array();
        $nodes = $this->xp->query($xpath, $context);

        if ($nodes) {
            foreach ($nodes as $node) {
                switch($node->nodeType) {
                    case XML_ATTRIBUTE_NODE:
                        $result[] = $node->value;
                        break;
                    case XML_TEXT_NODE:
                        $result[] = $node->wholeText;
                        break;
                    case XML_CDATA_SECTION_NODE:
                        $result[] = $node->textContent;
                        break;
                    default:
                }
            }
        }

        return empty($result)
            ? ''
            : $this->getCleanArray($result);
    }

    /**
     * Returns array content if count is one.
     *
     * @param array $array
     * @return array
     */
    private function getCleanArray($array)
    {
        return count($array) === 1 ? $array[0] : $array;
    }

    /**
     * Casts object to array recursive.
     *
     * @param object $object
     * @return array
     */
    private function objectToArray($object)
    {
        $array = array();
        $object = (array)$object;

        if (!empty($object)) {
            foreach ($object as $key => $val) {
                $array[$key] = (is_array($val) || is_object($val)) ? $this->objectToArray($val) : $val;
            }
        }

        return $array;
    }

    /**
     * Merge two arrays recursive.
     *
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    private function mergeRecursive($arr1, $arr2)
    {
        foreach (array_keys($arr2) as $key) {
            if (isset($arr1[$key]) && is_array($arr1[$key]) && is_array($arr2[$key])) {
                $arr1[$key] = $this->mergeRecursive($arr1[$key], $arr2[$key]);
            } else {
                $arr1[$key] = $arr2[$key];
            }
        }

        return $arr1;
    }
}
