<?php

/**
 * tourml_export : a class to handle TourML XML export for CA
 * @author gautier
 *
 */
class tourml
{
    private $dom;
    private $xpath;

    /**
     * Constructor
     */
    public function __construct()
    {

        $this->dom = new DOMDocument('1.0', 'utf-8');
        $this->xpath = new DomXPath($this->dom);
        $this->xpath->registerNamespace('tourml', 'tourml');

        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = true;
        $this->tour = $this->dom->createElement('tourml:Tour');
        $tournode = $this->dom->appendChild($this->tour);
        $tournode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $tournode->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
        $tournode->setAttribute('xmlns:tourml', 'http://tapintomuseums.org/TourML');
        $tournode->setAttribute('xmlns:xml', 'http://www.w3.org/XML/1998/namespace');
        $tournode->setAttribute('xmlns:gml', 'http://www.opengis.net/gml');
        $tournode->setAttribute('xsi:schemaLocation', 'http://tapintomuseums.org/TourML TourML.xsd');
        $tournode->setAttribute('tourml:id', 'com.ideesculture.collectiveaccess.tourml');
    }

    private function createElement($domObj, $tag_name, $value = NULL, $attributes = NULL)
    {
        $element = ($value != NULL) ? $domObj->createElement($tag_name, $value) : $domObj->createElement($tag_name);
        if ($attributes != NULL) {
            foreach ($attributes as $attr => $val) {
                if(!is_array($val)) $element->setAttribute($attr, $val);
            }
        }
        return $element;
    }

    public function setMetadatas(array $input)
    {
        $tourMetadatas = $this->dom->createElement('tourml:TourMetadata');
        $this->tour->appendChild($tourMetadatas);

        foreach ($input as $i) {
            $element = $this->createElement($this->dom, 'tourml:' . $i["name"], $i["value"], $i["attributes"]);
            $tourMetadatas->appendChild($element);
        }
        $propertySet = $this->dom->createElement('tourml:PropertySet');
        $this->tour->appendChild($propertySet);
    }

    public function addMetadataProperty(array $input)
    {
        foreach ($input as $key => $val) {
            $property = $this->createElement($this->dom, 'tourml:Property', $val, array("tourml:name" => $key));
            // Find the parent node
            $propertySet = $this->dom->getElementsByTagName('tourml:PropertySet');
            $propertySet->item(0)->appendChild($property);
        }
    }

    public function addStop($id, $view, array $contents = NULL, array $assetrefs = NULL, array $properties = NULL, $asset_content = NULL)
    {
        $stop = $this->createElement($this->dom, 'tourml:Stop', NULL, array("tourml:id" => $id, "tourml:view" => $view));
        $this->dom->getElementsByTagName('tourml:Tour')->item(0)->appendChild($stop);
        if (sizeof($contents)) {
            foreach ($contents as $content) {
                $element = $this->createElement($this->dom, 'tourml:' . $content["name"], $content["value"], $content["attributes"]);
                $stop->appendChild($element);
            }
        }
        if (sizeof($assetrefs)) {
            foreach ($assetrefs as $assetref) {
                $attributes["tourml:id"] = $assetref["id"];
                if (isset($assetref["usage"])) $attributes["tourml:usage"] = $assetref["usage"];
                $element = $this->createElement($this->dom, 'tourml:AssetRef', NULL, $attributes);
                $stop->appendChild($element);
            }
        }
        if (sizeof($properties)) {
            $propertySet = $this->dom->createElement('tourml:PropertySet');
            foreach ($properties as $key => $val) {
                $property = $this->createElement($this->dom, 'tourml:Property', $val, array("tourml:name" => $key));
                // Find the parent node
                $propertySet->appendChild($property);
            }
            $stop->appendChild($propertySet);
        }
    }

    public function addAsset($id, $type = NULL, array $sources = NULL, array $contents = NULL)
    {
        $asset = $this->createElement($this->dom, 'tourml:Asset', NULL, array("tourml:id" => $id, "tourml:type" => $type));
        $this->dom->getElementsByTagName('tourml:Tour')->item(0)->appendChild($asset);
        if (sizeof($sources)) {
            foreach ($sources as $key => $source) {
                //$properties = $source_version["properties"];
                //unset($source["properties"]);
                //unset($source["id"]);
                //unset($source["type"]);
                $sourcenode = $this->createElement($this->dom, 'tourml:Source', NULL, $source);
                $asset->appendChild($sourcenode);
                if (sizeof($properties)) {
                    $propertySet = $this->dom->createElement('tourml:PropertySet');
                    foreach ($properties as $key => $val) {
                        $property = $this->createElement($this->dom, 'tourml:Property', $val, array("tourml:name" => $key));
                        // Find the parent node
                        $propertySet->appendChild($property);
                    }
                    $sourcenode->appendChild($propertySet);
                }
            }
        }
        if (sizeof($contents)) {
            foreach ($contents as $content) {
                $data = $content["data"];
                //var_dump($data);die();
                unset($content["data"]);
                $contentnode = $this->createElement($this->dom, 'tourml:Content', NULL, NULL);
                $asset->appendChild($contentnode);
                if ($data) {
                    $datanode = $this->createElement($this->dom, 'tourml:Data', NULL, NULL);
                    $contentnode->appendChild($datanode);
                    $datanode->appendChild($this->dom->createCdataSection($data));
                }
                $properties = $content["properties"];
                if (sizeof($properties)) {
                    $propertySet = $this->dom->createElement('tourml:PropertySet');
                    foreach ($properties as $key => $val) {
                        $property = $this->createElement($this->dom, 'tourml:Property', $val, array("tourml:name" => $key));
                        // Find the parent node
                        $propertySet->appendChild($property);
                    }
                    $contentnode->appendChild($propertySet);
                }
            }
        }
        return true;
    }

    public function addConnection($in, $out, $priority)
    {
        // Creation connection node 
        // Example : <tourml:Connection tourml:srcId="stop-6" tourml:destId="stop-7" tourml:priority="1" />
        $this->relation_counter++;
        $connectionnode = $this->createElement($this->dom, 'tourml:Connection', NULL, array(
            "tourml:srcId" => $in,
            "tourml:destId" => $out,
            "tourml:priority" => $priority
        ));
        $this->dom->getElementsByTagName('tourml:Tour')->item(0)->appendChild($connectionnode);
    }

    public function get()
    {
        return $this->dom->saveXML();
    }
}