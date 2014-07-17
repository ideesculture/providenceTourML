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

 	require_once(__CA_LIB_DIR__.'/core/TaskQueue.php');
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_MODELS_DIR__.'/ca_objects.php');
 	require_once(__CA_MODELS_DIR__.'/ca_object_representations.php');
 	require_once(__CA_MODELS_DIR__.'/ca_locales.php');
 	require_once(__CA_MODELS_DIR__.'/ca_tours.php');
        require_once(__CA_MODELS_DIR__.'/ca_tour_stops.php');
 	include_once(__CA_LIB_DIR__.'/ca/Search/TourSearch.php');
 	require_once(__CA_APP_DIR__.'/plugins/providenceTourML/lib/tourml.php');
 	

 	class ProvidenceTourMLController extends ActionController {
 		# -------------------------------------------------------
  		protected $opo_config;		// plugin configuration file
 		protected $opa_locales;
 		protected $opa_available_tours;


 		# -------------------------------------------------------
 		# Constructor
 		# -------------------------------------------------------

 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			global $allowed_universes;
 			
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if (!$this->request->user->canDoAction('can_use_tourml_plugin')) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3000?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$this->opo_config = Configuration::load(__CA_APP_DIR__.'/plugins/providenceTourML/conf/providenceTourML.conf');
 			
 			$tour_search = new TourSearch();
 			$qr_hits = $tour_search->search('*');
 			$this->opa_available_tours = array();
 			while($qr_hits->nextHit()){
 				$id = $qr_hits->get('tour_id');
 				$tour = new ca_tours();
 				$tour->load($id);
 				$this->opa_available_tours[]=array(
 					"tour_id"=>$qr_hits->get('tour_id'),
 					"tour_code"=>$tour->get('tour_code'),
 					"preferred_labels"=>$tour->get('preferred_labels')
 					);
 			}
 		}
 		 		
 		# -------------------------------------------------------
 		# Functions to render views
 		# -------------------------------------------------------
 		public function Index($type='') {
			$this->view->setVar('available_tours', $this->opa_available_tours);
			$this->render('index_html.php');			 				
 		}
 				
 		public function Preview($type='') {
                        $tour_id = $this->request->getParameter('id', pString);
                        $this->xmlExport($tour_id,"file",__DIR__."/../lib/tap-webapp/tour_".$tour_id.".xml" );
                        $this->view->setVar('id', $tour_id);
			$this->render('preview_html.php');			 				
 		}

        public function XmlOnScreen() {
            $tour_id = $this->request->getParameter('id', pString);
            $this->xmlExport($tour_id,"screen");
            die();
        }

 		private function xmlExport($tour_id,$target = "file", $filename="null") {
 			//$opa_id=$this->request->getParameter('id', pInteger);
 			$opa_id=2;
 			$opa_locale="fr";

                        
                        $asset_sources = array();
                        
 			$xml = new tourml();

                        /*$tour_id = $this->request->getParameter("id", pInteger);
                        $this->view->setVar("id", $tour_id);*/
                        
                        $vt_tour = new ca_tours($tour_id);
                        
                        
 			$metadatas = array(
 				array("name"=>"Title","value"=>$vt_tour->getLabelForDisplay(),"attributes"=>array("xml:lang"=>"en")),
 				array("name"=>"Description","value"=>  html_entity_decode($vt_tour->get("ca_tours.tour_description")),"attributes"=>array("xml:lang"=>"en")),
 				array("name"=>"Author","value"=>"Providence TourML"), 
                                array("name"=> "AppResource", "attributes"=>array("tourml:id"=>"tour_image_header", "tourml:usage"=>"image"))
 			);
                        
                        $va_header_image = $vt_tour->get("ca_tours.tour_image_header", array("version"=>"page", "return"=>"url", "showMediaInfo"=>true));
                        
                        $asset_sources[] = array(
                                    "id" => "tour_image_header",
                                    "type" => "image",
                                    "format"=>"image/jpeg",
                                    "uri" => $va_header_image,
                                    "lastModified"=>"2011-09-29T12:01:32",
                                    "properties"=>array(
                                        "width" => 320,
                                        "height"=> 200
                                    )
                                );
                        
                        $va_stops = $vt_tour->get("ca_tour_stops.stop_id", array("returnAsArray"=>true));
                        
                        
                        
                        foreach ($va_stops as $vn_stop_id){
                            $vt_stop = new ca_tour_stops($vn_stop_id);
                            $vt_stopContent = array();
                            
                            if(!$nonfirst){
                                $metadatas[] = array("name"=>"RootStopRef","attributes"=>array("tourml:id"=> $vt_stop->get("ca_tour_stops.idno")));
                                
                                $xml->setMetadatas($metadatas);
                                $xml->addMetadataProperty(array("google-analytics"=>"UA-123456"));
                                
                                $nonfirst = true;
                            }
                            
                            
                            $vt_stopContent[] = array(
                                "name" => "Title",
                                "value" => $vt_stop->get("ca_tour_stops.preferred_labels"),
                                "attributes" => array("xml:lang"=>"en")
                            );
                            
                            $vt_stopContent[] = array(
                                "name" => "Description",
                                "value" => html_entity_decode($vt_stop->get("ca_tour_stops.tour_stop_description")),
                                "attributes" => array("xml:lang" => "fr")
                            );
                            
                            $va_header_image = $vt_stop->get("ca_tour_stops.stop_image_header", array("version"=>"page", "return"=>"url", "showMediaInfo"=>true));
                                                        
                            $asset_refs = array();
                            
                            $asset_refs[] = array(
                                "id" => "header_".$vn_stop_id,
                                "usage" => "header_image"
                            );
                            
                            $asset_sources[] = array(
                                "id" => "header_".$vn_stop_id,
                                "type" => "header_image",
                                "format"=>"image/jpeg",
                                "uri" => $va_header_image,
                                "lastModified"=>"2011-09-29T12:01:32",
                                "properties"=>array(
                                    "width" => 320,
                                    "height"=> 200
                                )
                            );
                            
                            $vs_code = $vt_stop->get("ca_tour_stops.stop_numero");
                            
                            $va_assets = $vt_stop->get("ca_objects.object_id", array("returnAsArray"=>true));
                            
                            
                            
                            foreach($va_assets as $vn_asset_id){
                                $vt_asset = new ca_objects($vn_asset_id);
                                
                                $asset_type =  "page";
                                
                                //$asset_media = $vt_asset->get("ca_object_representations.media.large_page", array("returnAsArray"=>true));
                                $asset_media = reset($vt_asset->getPrimaryRepresentation(array($asset_type))["urls"]);
                                
                                $asset_media_width = $vt_asset->getPrimaryRepresentation(array($asset_type))["info"][$asset_type]["WIDTH"];
                                $asset_media_height = $vt_asset->getPrimaryRepresentation(array($asset_type))["info"][$asset_type]["HEIGHT"];
                                                                
                                $asset_sources[] = array(
                                    "id" => $vn_asset_id,
                                    "type" => "image",
                                    "format"=>"image/jpeg",
                                    "uri" => $asset_media,
                                    "lastModified"=>"2011-09-29T12:01:32",
                                    "properties"=>array(
                                        "width" => $asset_media_width,
                                        "height"=> $asset_media_height
                                    )
                                );
                                
                                $asset_refs[] = array(
                                    "id" => $vn_asset_id,
                                    "usage" => "image"
                                );
                            }
                            
                            if($vs_code != null){
                                $xml->addStop($vt_stop->get("ca_tour_stops.idno"), "stop_group", $vt_stopContent, $asset_refs, array("code"=>$vs_code), $asset_sources );
                            }else{
                                $xml->addStop($vt_stop->get("ca_tour_stops.idno"), "stop_group", $vt_stopContent, $asset_refs, null, $asset_sources );
                            }
                            
                            
                        }
                        
                        foreach ($asset_sources as $asset_source){
                            $xml->addAsset($asset_source["id"], array($asset_source));
                        }		 				
                        
                        
                        if ($target=="file") {
                            if(!$filename) die("xmlExport : no filename received");

                            if(!$monfichier = fopen($filename, 'w+'))
                            {
                                echo 'Ouverture impossible!';
                            }

                            fwrite($monfichier, $xml->get());
                            fclose($monfichier);
                        } elseif ($target == "screen") {
                            print $xml->get();
                            die();
                        }
                        //die();
                }
 }