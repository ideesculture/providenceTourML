<?php
/* ----------------------------------------------------------------------
 * plugins/statisticsViewer/controllers/ProvidenceTourMLController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__ . '/core/TaskQueue.php');
require_once(__CA_LIB_DIR__ . '/core/Configuration.php');
require_once(__CA_MODELS_DIR__ . '/ca_lists.php');
require_once(__CA_MODELS_DIR__ . '/ca_objects.php');
require_once(__CA_MODELS_DIR__ . '/ca_object_representations.php');
require_once(__CA_MODELS_DIR__ . '/ca_locales.php');
require_once(__CA_MODELS_DIR__ . '/ca_tours.php');
require_once(__CA_MODELS_DIR__ . '/ca_tour_stops.php');
include_once(__CA_LIB_DIR__ . '/ca/Search/TourSearch.php');
require_once(__CA_APP_DIR__ . '/plugins/providenceTourML/lib/tourml.php');


class ProvidenceTourMLController extends ActionController
{
    # -------------------------------------------------------
    protected $opo_config; // plugin configuration file
    protected $opa_locales;
    protected $opa_available_tours;
    protected $opa_stop_types_mapping; // mapping for stop types

    # -------------------------------------------------------
    # Constructor
    # -------------------------------------------------------

    public function __construct(&$po_request, &$po_response, $pa_view_paths = null)
    {
        global $allowed_universes;

        parent::__construct($po_request, $po_response, $pa_view_paths);

        if (!$this->request->user->canDoAction('can_use_tourml_plugin')) {
            $this->response->setRedirect($this->request->config->get('error_display_url') . '/n/3000?r=' . urlencode($this->request->getFullUrlPath()));
            return;
        }

        $this->opo_config = Configuration::load(__CA_APP_DIR__ . '/plugins/providenceTourML/conf/providenceTourML.conf');
        $this->opa_stop_types_mapping = array_flip($this->opo_config->getAssoc('stop_types'));

        $tour_search = new TourSearch();
        $qr_hits = $tour_search->search('*');
        $this->opa_available_tours = array();
        while ($qr_hits->nextHit()) {
            $id = $qr_hits->get('tour_id');
            $tour = new ca_tours();
            $tour->load($id);
            $this->opa_available_tours[] = array(
                "tour_id" => $qr_hits->get('tour_id'),
                "tour_code" => $tour->get('tour_code'),
                "preferred_labels" => $tour->get('preferred_labels')
            );
        }
    }

    # -------------------------------------------------------
    # Functions to render views
    # -------------------------------------------------------
    public function Index($type = '')
    {
        $this->view->setVar('available_tours', $this->opa_available_tours);
        $this->render('index_html.php');
    }

    public function Preview($type = '')
    {
        $tour_id = $this->request->getParameter('id', pString);
        $this->xmlExport($tour_id, "file", array("filename" => __DIR__ . "/../lib/tap-webapp/tour_" . $tour_id . ".xml"));
        $this->view->setVar('id', $tour_id);
        $this->render('preview_html.php');
    }

    public function XmlOnScreen()
    {
        $tour_id = $this->request->getParameter('id', pString);
        $this->xmlExport($tour_id, "screen");
        die();
    }

    public function XmlBundling()
    {
        $tour_id = $this->request->getParameter('id', pString);
        $result = $this->xmlExport($tour_id, "bundle", array("bundlename"=>"test.bundle"));
        $this->view->setVar("result",$result);
        $this->render('xml_bundling_html.php');
    }

    private function xmlExport($tour_id, $target = "file", $options = array())
    {
        //$opa_id=$this->request->getParameter('id', pInteger);
        $opa_id = 2;
        $opa_locale = "fr";

            
        // If target==bundle, creating target directories
        if($target=="bundle") {
            $vs_bundle_dir = __DIR__ . "/../export/".$options["bundlename"];
            $vs_lproj_dir = $vs_bundle_dir."/fr.lproj";
            $vs_assets_dir = $vs_lproj_dir."/assets";
            if(!is_dir($vs_assets_dir)) 
                // 0770 : default rights when running mkdir under php, not changing here ; recursive = true
                mkdir($vs_assets_dir, 0777, true); 
        }

        $asset_sources = array();
        $xml = new tourml();

        $vt_tour = new ca_tours($tour_id);

        // Main tour metadatas : title, description, author, header, geo
        $metadatas[] = array("name" => "Title", "value" => $vt_tour->getLabelForDisplay(), "attributes" => array("xml:lang" => "en"));
        $metadatas[] = array("name" => "Description", "value" => html_entity_decode($vt_tour->get("ca_tours.tour_description")), "attributes" => array("xml:lang" => "en"));
        $metadatas[] = array("name" => "Author", "value" => "Providence TourML");
        $metadatas[] = array("name" => "AppResource", "attributes" => array("tourml:id" => "tour_image_header", "tourml:usage" => "image"));
        $metadatas[] = array("name" => "AppResource", "attributes" => array("tourml:id" => "tour_asset_geo", "tourml:usage" => "geo"));
        
        $va_header_image = $vt_tour->get("ca_tours.tour_image_header", array("version" => "page", "return" => "url", "showMediaInfo" => true));

        $source_file = $vt_tour->get("ca_tours.tour_image_header", array("version" => "page", "return" => "path", "showMediaInfo" => true));;

        $asset_sources["tour_image_header"] = array(
            "type" => "image"
        );
        $asset_sources["tour_image_header"]["image"] = array(
            "tourml:format" => "image/jpeg",
            "tourml:uri" => $va_header_image,
            "tourml:lastModified" => "2011-09-29T12:01:32",
            "properties" => array(
                "width" => 320,
                "height" => 200
            )
        );

        $va_tour_georeference = json_decode($vt_tour->get("ca_tours.tour_georeference"));
        $asset_contents[] = array(
            "id" => "tour_asset_geo",
            "type" => "geo",
            "data" => "{\"type\":\"Point\",\"coordinates\":[".$va_tour_georeference[1].",".$va_tour_georeference[0]."]}",
            "properties" => array(
                "centroid" => strval($va_tour_georeference[0]+0.002).",".strval($va_tour_georeference[1]+0.002),
                "bbox" => strval($va_tour_georeference[1]+0.004).",".strval($va_tour_georeference[1]).",".strval($va_tour_georeference[0]+0.004).",".strval($va_tour_georeference[0])
            )
        );

        $vi_zoom = $vt_tour->get("ca_tours.tour_zoom_level");
                
        
        $va_stops = $vt_tour->get("ca_tour_stops.stop_id", array("returnAsArray" => true));

        foreach ($va_stops as $vn_stop_id) {
            $vt_stop = new ca_tour_stops($vn_stop_id);
            $vt_stopContent = array();

            if (!$nonfirst) {
                $metadatas[] = array("name" => "RootStopRef", "attributes" => array("tourml:id" => $vt_stop->get("ca_tour_stops.stop_id")));

                $xml->setMetadatas($metadatas);
                $xml->addMetadataProperty(array("google-analytics" => "UA-123456"));
                if ($vi_zoom) 
                    $xml->addMetadataProperty(array("initial_map_zoom" => $vi_zoom));
                $nonfirst = true;
            }

            // Main stop metadatas : title & description
            $vt_stopContent[] = array(
                "name" => "Title",
                "value" => $vt_stop->get("ca_tour_stops.preferred_labels"),
                "attributes" => array("xml:lang" => "en")
            );
            $vs_description = $vt_stop->get("ca_tour_stops.tour_stop_description");
            if($vs_description) {
                $vt_stopContent[] = array(
                    "name" => "Description",
                    "value" => html_entity_decode($vt_stop->get("ca_tour_stops.tour_stop_description")),
                    "attributes" => array("xml:lang" => "fr")
                );
            }

            // Fetching stop type and mapping it to a valid TourML stop type
            $vn_type_id = $vt_stop->get("ca_tour_stops.type_id");
            $vs_stop_type = $this->opa_stop_types_mapping[$vn_type_id];
            
            $asset_refs = array();
            
            // header image
            $va_header_image = $vt_stop->get("ca_tour_stops.stop_image_header", array("version" => "page", "return" => "url", "showMediaInfo" => true));
            if($va_header_image) {
                // referencing header image asset
                $asset_refs[] = array(
                    "id" => "header_" . $vn_stop_id,
                    "usage" => "header_image"
                );
                // linking header image asset
                $asset_sources["header_" . $vn_stop_id] = array(
                    "type" => "header_image"
                );
                $asset_sources["header_" . $vn_stop_id]["header_image"] = array(
                    "tourml:format" => "image/jpeg",
                    "tourml:uri" => $va_header_image,
                    "tourml:lastModified" => "2011-09-29T12:01:32",
                    "properties" => array(
                        "width" => 320,
                        "height" => 200
                    )
                );
            }

            // Treating tour stop georeference metadata
            $va_georeference = json_decode($vt_stop->get("ca_tour_stops.tour_stop_georeference"));

            if (is_array($va_georeference)) {
                $asset_refs[] = array(
                    "id" => "geo_" . $vn_stop_id,
                    "usage" => "geo"
                );

                $asset_contents[] = array(
                    "id" => "geo_" . $vn_stop_id,
                    "type" => "geo",
                    "data" => "{\"type\":\"Point\",\"coordinates\":[".$va_georeference[1].",".$va_georeference[0]."]}",
                    "properties" => array(
                        "centroid" => $va_georeference[0].",".$va_georeference[1],
                        "bbox" => $va_georeference[1].",".$va_georeference[1].",".$va_georeference[0].",".$va_georeference[0]
                    )
                );
            }

            $vs_code = $vt_stop->get("ca_tour_stops.stop_numero");

            $va_assets = $vt_stop->get("ca_objects.object_id", array("returnAsArray" => true));
            $va_extract_media_mapping = array(
                "image_stop" => array(
                    "versions" => array("page","thumbnail"),
                    "format" => "image/jpeg"),
                "audio_stop" => array(
                    "versions" => array("original"),
                    "format" => "audio/mp3"),
                "video_stop" => array(
                    "versions" => array("original"),
                    "format" => "video/mp4")
            );
            if(count($va_assets)>0) {
                // Looping through all assets
                foreach ($va_assets as $vn_asset_id) {
                    $vt_asset = new ca_objects($vn_asset_id);
                    $vs_asset_type = reset(explode("_",$vs_stop_type));
                    $asset_sources[$vn_asset_id] = array(
                        "type" => $vs_asset_type
                    );
                    // Fetch which media have to be extracted, depending of the type (page & thumbnail for image stops)
                    $va_extract_media_types = $va_extract_media_mapping[$vs_stop_type]["versions"];

                    // Looping to get the wanted media versions, two required for images
                    foreach($va_extract_media_types as $va_extract_media_type) {
                        $asset_media = reset($vt_asset->getPrimaryRepresentation(array($va_extract_media_type))["urls"]);
                        $asset_media_width = $vt_asset->getPrimaryRepresentation(array($va_extract_media_type))["info"][$va_extract_media_type]["WIDTH"];
                        $asset_media_height = $vt_asset->getPrimaryRepresentation(array($va_extract_media_type))["info"][$va_extract_media_type]["HEIGHT"];
                        $asset_sources[$vn_asset_id][$va_extract_media_type] = array(
                            "tourml:format" => $va_extract_media_mapping[$vs_stop_type]["format"],
                            "tourml:uri" => $asset_media,
                            "tourml:lastModified" => date(DATE_ATOM),
                            "tourml:part" => ($va_extract_media_type=="thumbnail" ? "thumbnail" : "image")
                        );
                    }

                    // Creating the reference, note that for image we need to specifiy image_asset as type
                    $asset_refs[] = array("id" => $vn_asset_id,"usage" => ($vs_asset_type=="image"?"image_asset":$vs_asset_type));
                }
            }

            if ($vs_code != null) {
                $xml->addStop($vt_stop->get("ca_tour_stops.stop_id"), $vs_stop_type, $vt_stopContent, $asset_refs, array("code" => $vs_code), $asset_sources);
            } else {
                $xml->addStop($vt_stop->get("ca_tour_stops.stop_id"), $vs_stop_type, $vt_stopContent, $asset_refs, null, $asset_sources);
            }

            // Getting linked stops
            $va_linked_stops = $vt_stop->get("ca_tour_stops.related", array("returnAsArray" => true));
            foreach($va_linked_stops as $va_linked_stop) {
                if (($va_linked_stop["direction"] == "ltor") && ($va_linked_stop["relationship_type_code"] == "suivant")) {
                    $va_connections[] = array("in"=>$vn_stop_id, "out"=>$va_linked_stop["stop_id"]);
                }
            }
        }

        foreach ($asset_sources as $asset_source_id => $asset_source) {
            // if target == bundle, copy the assets to the asset dir, inside the bundle and write relative paths
            if($target=="bundle") {
                foreach($asset_source as $key=>$asset_source_version) {
                    if($key == "type") continue;
                    $asset_filename = basename($asset_source_version["tourml:uri"]);    
                    copy($asset_source_version["tourml:uri"],$vs_assets_dir."/".$asset_filename);
                    $asset_source[$key]["tourml:uri"]="assets/".$asset_filename;
                }
            }
            $type=$asset_source["type"];
            unset($asset_source["type"]);
            $xml->addAsset($asset_source_id,$type,$asset_source,NULL);
        }

        if($asset_contents) {
            foreach ($asset_contents as $asset_content) {
                $xml->addAsset($asset_content["id"], $asset_content["type"], NULL, array($asset_content));
            }
        }

        // Looping through all connections & defining an incremental priority
        $priority=1;
        foreach ($va_connections as $va_connection) {
            $xml->addConnection($va_connection["in"],$va_connection["out"],$priority);
            $priority++;
        }

        if ($target == "file") {
            if (!$options["filename"]) die("xmlExport : no filename received");
            //var_dump($filename);die();
            if (!$monfichier = fopen($options["filename"], 'w+')) {
                die('Unable to open file!');
            }
            fwrite($monfichier, $xml->get());
            fclose($monfichier);
            return true;
        } elseif ($target == "bundle") {
            if (!$options["bundlename"]) die("bundlename is mandatory");

            // Writing XML
            $vp_tour_xml = fopen($vs_lproj_dir."/tour.xml", 'w+');
            fwrite($vp_tour_xml, $xml->get());
            return $vs_lproj_dir."/tour.xml\n";
        } elseif ($target == "screen") {
            print $xml->get();
            return true;
        }
    }
}