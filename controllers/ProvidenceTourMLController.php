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

			$this->render('preview_html.php');			 				
 		}
 				
 		public function Export($type='') {
 			//$opa_id=$this->request->getParameter('id', pInteger);
 			$opa_id=2;
 			$opa_locale="fr";

 			$xml = new tourml();

                        $tour_id = $this->request->getParameter("id", pInteger);
                        $this->view->setVar("id", $tour_id);
                        
                        $vt_tour = new ca_tours($tour_id);
                        
                        
 			$metadatas = array(
 				array("name"=>"Title","value"=>$vt_tour->getLabelForDisplay(),"attributes"=>array("xml:lang"=>"en")),
 				array("name"=>"Description","value"=>$vt_tour->get("ca_tours.tour_description"),"attributes"=>array("xml:lang"=>"en")),
 				array("name"=>"Author","value"=>"Indianapolis Museum of Art"),
 				array("name"=>"RootStopRef","attributes"=>array("tourml:id"=>"stop-1"))
 			);
                        
                        $va_stops = $vt_tour->get("ca_tour_stops.stop_id", array("returnAsArray"=>true));
                        
                        $xml->setMetadatas($metadatas);

 			$xml->addMetadataProperty(array("google-analytics"=>"UA-123456"));
                        
                        $asset_sources = array();
                        $vt_stopContent = array();
                        foreach ($va_stops as $vn_stop_id){
                            $vt_stop = new ca_tour_stops($vn_stop_id);
                            $vt_stopContent[] = array(
                                "name" => "Title",
                                "value" => $vt_stop->get("ca_tour_stops.preferred_labels"),
                                "attributes" => array("xml:lang"=>"en")
                            );
                            
                            $vt_stopContent[] = array(
                                "name" => "Description",
                                "value" => $vt_stop->get("ca_tour_stops.tour_stop_description"),
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
                            
                            var_dump($va_header_image);
                            die();
                            
                            $vs_code = $vt_stop->get("ca_tour_stops.stop_numero");
                            
                            $va_assets = $vt_stop->get("ca_objects.object_id", array("returnAsArray"=>true));
                            
                            
                            
                            foreach($va_assets as $vn_asset_id){
                                $vt_asset = new ca_objects($vn_asset_id);
                                
                                $asset_type =  "page";
                                
                                //$asset_media = $vt_asset->get("ca_object_representations.media.large_page", array("returnAsArray"=>true));
                                $asset_media = reset($vt_asset->getPrimaryRepresentation(array($asset_type))["urls"]);
                                
                                $asset_media_width = $vt_asset->getPrimaryRepresentation(array($asset_type))["info"][$asset_type]["WIDTH"];
                                $asset_media_height = $vt_asset->getPrimaryRepresentation(array($asset_type))["info"][$asset_type]["HEIGHT"];
                                
                                //var_dump($asset_media_height);
                                //die();
                                
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
                                
                                //var_dump($vt_asset->get("object_id"));
                                //die();
                                
                                //$vs_id = $vt_asset->get("object_id");
                                
                                //$xml->addAsset($vs_id, $asset_content);
                                
                            }
                            
                            if($vs_code != null){
                                $xml->addStop($vt_stop->get("ca_tour_stops.idno"), "StopGroup", $vt_stopContent, $asset_refs, array("code"=>$vs_code), $asset_sources );
                            }else{
                                $xml->addStop($vt_stop->get("ca_tour_stops.idno"), "StopGroup", $vt_stopContent, $asset_refs, null, $asset_sources );
                            }
                            
                            
                        }
                        
                        foreach ($asset_sources as $asset_source){
                            $xml->addAsset($asset_source["id"], array($asset_source));
                        }
                        
 			
                        /*
 			$stop1content = array(
 				array("name"=>"Title","value"=>"Ankhaman's remains","attributes"=>array("xml:lang"=>"en")),
 				array("name"=>"Title","value"=>"remainos Ankhaman's","attributes"=>array("xml:lang"=>"es"))
 			);
 			$xml->addStop("stop-1","StopGroup",$stop1content,null,array("code"=>"100"));

 			$stop2content = array(
 				array("name"=>"Title","value"=>"CT imagery of the mummy","attributes"=>array("xml:lang"=>"en"))
 			);
 			$stop2assetrefs = array(
 				array("id"=>"img-1","usage"=>"primary")
 			);
 			$xml->addStop("stop-2","ImageStop",$stop2content,$stop2assetrefs,array("code"=>"200"));
 			*/
 			/*$asset1sources = array(
 				array(
 					"tourml:format"=>"video/quicktime",
 					"tourml:lastModified"=>"2011-09-29T12:01:32",
 					"xml:lang"=>"en",
 					"tourml:uri"=>"file:///videos/ankh-ct.mov",
 					"properties"=>array(
 						"duration"=>"00:10:36",
 						"width"=>"1920",
 						"height">"1080"
 					)
 				)
 			);
 			$xml->addAsset("img-1",$asset1sources);
 			
 			$asset2content = array(
 				array(
 					 "tourml:format"=>"gml",
 					 "tourml:lastModified"=>"2011-09-29T12:01:32",
 					 "data"=>"<gml:Point srsName=\"EPSG:4326\"><gml:pos>21.052 -10.854</gml:pos></gml:Point>"
 				)	
 			);
 			$xml->addAsset("geo-1",NULL,$asset2content);
 			*/
 			$this->view->setVar('xml', $xml->get());
			$this->render('export_html.php');			 				
 		}
 }