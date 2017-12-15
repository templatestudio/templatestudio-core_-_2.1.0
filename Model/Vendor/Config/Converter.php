<?php
/**
 * Copyright Â© 2016 Templatestudio UK. All rights reserved.
 */
namespace Templatestudio\Core\Model\Vendor\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{

    /**
     * Convert vendor configuration from dom node tree to array
     *
     * @param \DOMDocument $source
     * @return string[]
     */
    public function convert($source)
    {
        $xpath = new \DOMXPath($source);

        return [
            'org' => $this->convertOrganizationName($xpath),
            'address' => $this->convertAddress($xpath),
            'url' => $this->convertUrl($xpath),
            'email' => $this->convertEmail($xpath),
            'phone' => $this->convertPhone($xpath)
        ];
    }

    /**
     * Convert org xml tree to string
     *
     * @param \DOMXPath $xpath
     * @return string|null
     */
    protected function convertOrganizationName(\DOMXPath $xpath)
    {
        $org = $this->convertByQuery($xpath, '//config/org');

        if (is_array($org)) {
            $org = array_filter($org);
            return reset($org);
        }

        return $org;
    }

    /**
     * Convert organization address xml tree to array
     *
     * @param \DOMXPath $xpath
     * @return string[]
     */
    protected function convertAddress(\DOMXPath $xpath)
    {
        return $this->convertByQuery($xpath, '//config/address/adr');
    }

    /**
     * Convert xml tree to array
     *
     * @param \DOMXPath $xpath
     * @return string[]
     */
    protected function convertUrl(\DOMXPath $xpath)
    {
        return $this->convertByQuery($xpath, '//config/url/property');
    }

    /**
     * Convert phone xml tree to array
     *
     * @param \DOMXPath $xpath
     * @return string[]
     */
    protected function convertPhone(\DOMXPath $xpath)
    {
        return $this->convertByQuery($xpath, '//config/phone/property');
    }

    /**
     * Convert email xml tree to array
     *
     * @param \DOMXPath $xpath
     * @return string[]
     */
    protected function convertEmail(\DOMXPath $xpath)
    {
        return $this->convertByQuery($xpath, '//config/email/property');
    }

    /**
     * Convert xml tree to array by query
     *
     * @param \DOMXPath $xpath
     * @param string $query
     * @return string[]|null
     */
    protected function convertByQuery(\DOMXPath $xpath, $query)
    {
        $nodeList = $xpath->query($query);
        if ($nodeList instanceof \DOMNodeList) {
            return $this->getNodeListValues($nodeList);
        }

        return;
    }

    /**
     * Retrieve node values
     *
     * @param \DOMNodeList $nodeList
     * @return string[]
     */
    protected function getNodeListValues(\DOMNodeList $nodeList)
    {
        $data = [];

        foreach ($nodeList as $nodeElement) {
            if (XML_ELEMENT_NODE == $nodeElement->nodeType) {
                $key = $this->getNodeCode($nodeElement);

                if ($nodeElement->hasChildNodes()) {
                    if (1 == $nodeElement->childNodes->length
                        and in_array($nodeElement->childNodes->item(0)->nodeType, [
                        XML_CDATA_SECTION_NODE,
                        XML_TEXT_NODE
                    ])) {
                        $nodeData = $nodeElement->childNodes->item(0)->textContent;
                    } else {
                        $nodeData = $this->getNodeListValues($nodeElement->childNodes);
                    }
                } else {
                    $nodeData = $nodeElement->nodeValue;
                }

                if (empty($key)) {
                    $data[] = $nodeData;
                } else {
                    $data[$key] = $nodeData;
                }
                unset($nodeData, $key);
            }
            unset($nodeElement);
        }

        return $data;
    }

    /**
     * Retrieve node key/code
     *
     * @param \DOMElement $element
     * @return string|null
     */
    protected function getNodeCode(\DOMElement $element)
    {
        if (XML_ELEMENT_NODE == $element->nodeType && null !== $element->attributes->getNamedItem('code')) {
            return $element->attributes->getNamedItem('code')->nodeValue;
        }

        return;
    }
}