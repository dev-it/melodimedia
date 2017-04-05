<?php

namespace DevIT\MelodiMedia\Parsers;

use SimpleXMLElement;
use DevIT\MelodiMedia\Exceptions\ContentCouldNotBeParsed;

class XMLApiParser implements MelodiApiParser
{

    protected $debug = true;

    /**
     * Parse pasted API response.
     *
     * @param string $content
     *
     * @return array|null
     * @throws \RobinFranssen\MelodiMedia\Exceptions\ContentCouldNotBeParsed
     */
    public function parse(string $content): ?array
    {
        try {
            $content = new SimpleXMLElement($content, LIBXML_NOCDATA);
            $parsedContent = $this->xmlToArray($content);
        } catch (\Exception $e) {
            throw new ContentCouldNotBeParsed($e);
        }

        return $parsedContent;
    }

    public function xmlToArray($xml)
    {
        $output = [];
        foreach($xml as $type => $element) {
            if (count($element->children())) {
                $parsedElement = $this->xmlToArray($element);
                foreach ($element->attributes() as $attribute => $value) {
                    $parsedElement[$attribute] = (string) $value;
                }

                if (count($element->children()) == 1) {
                    $output[$type] = $parsedElement;
                } else {
                    // Pretty array output.
                    if (! isset($output[$type])) {
                        $output[$type] = $parsedElement;
                        $output[$type]['nested'] = false;
                    } else if (isset($output[$type]) && isset($output[$type]['nested'])) {
                        $tmp = $output[$type];
                        unset($tmp['nested']);

                        $output[$type] = [];
                        $output[$type][] = $tmp;
                        $output[$type][] = $parsedElement;
                    } else {
                        $output[$type][] = $parsedElement;
                    }
                }
                continue;
            }

            $parsedElement = json_decode(json_encode($element), TRUE);

            if (isset($parsedElement["@attributes"])) {
                foreach ($parsedElement["@attributes"] as $attribute => $value) {
                    $parsedElement[$attribute] = (string) $value;
                }

                unset($parsedElement["@attributes"]);
            } else {
                if (count($parsedElement) < 2) {
                    $parsedElement = $parsedElement[0] ?? '';
                    $output[$type] = $parsedElement;
                    continue;
                }
            }

            $parsedElement = $this->changeArrayKey($parsedElement, 0, 'value');

            $output[$type][] = $parsedElement;
        }

        return $output;
    }

    private function changeArrayKey($array, $oldKey, $newKey)
    {
        if (array_key_exists($oldKey, $array)) {
            $array[$newKey] = $array[$oldKey];
            unset($array[$oldKey]);
        }

        return $array;
    }
}
