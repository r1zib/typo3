<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Marc-Antoine TREHIN <marcantoine.trehin@gmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
    
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once("Zend/Loader/Autoloader.php");
Zend_Loader_Autoloader::getInstance();

/**
 * Plugin 'nke packs list' for the 'user_listing_packs' extension.
 *
 * @author    Marc-Antoine TREHIN <marcantoine.trehin@gmail.com>
 * @package    TYPO3
 * @subpackage    user_listingpacks
 */
class user_listingpacks_pi1 extends tslib_pibase {
    var $prefixId      = 'user_listingpacks_pi1';        // Same as class name
    var $scriptRelPath = 'pi1/class.user_listingpacks_pi1.php';    // Path to this script relative to the extension dir.
    var $extKey        = 'user_listing_packs';    // The extension key.
    var $pi_checkCHash = true;
    
    var $baseURL = "http://apps.nke-marine-electronics.fr/typo3/";
    var $packs = array();
    var $options = array();
    
    
    const SEL_SSPAGE = 0;
    const SEL_PACK = 1;
    
    const AFF_LISTE = 0;
    const AFF_SLIDER_BXSLIDER = 1;
    const AFF_SLIDER_ESTRO = 2;
    
    /**
     * The main method of the PlugIn
     * @param    string        $content: The PlugIn content
     * @param    array        $conf: The PlugIn configuration
     * @return    The content that is displayed on the website
     */
    function main($content, $conf) {
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();
        
        $this->options = $this->getOptions();

        /*
         *  Recherche des pages à afficher
         */
        
        if ($this->options['selOption'] == $this::SEL_PACK) {
        	/* liste du plugin*/
        	$UIDs = $this->getPacks();
        } else {
        	/* les sous-pages */
        	$currentPageID = $this->cObj->data["pid"];
        	$UIDs = $this->getUIDs($currentPageID);
        }
        
        /*
         * Pour chaque page, on recherche les informations à afficher
         */
        $lst = array();
        foreach($UIDs as $uid) {
        	/* recherche des autres informations */
        	$elt = $this->getinfoContent($uid);
        	$lst[] = $elt;
        }

        /*
        * Choix du template et affichage dans le template
        */
        
        $template = '';
        switch ($this->options['affOption']) {
        	case $this::AFF_SLIDER_BXSLIDER: 
        		$template = $conf['templateBxSlider'];         	
        		break;
        	case $this::AFF_SLIDER_ESTRO:
        		$template = $conf['templateEstro'];
        		break;
        		
        		
        	case $this::AFF_LISTE:  
        	default:
        		$template = $conf['templateFile'];
        	break;
        }
        
        /* Constitution des éléments li */
        $eltLi = '';
        $this->templateHtml = $this->cObj->fileResource($template);
        $subpart = $this->cObj->getSubpart($this->templateHtml, '###STRUCTURE-LI###');
        $markerArray = array();
        
        foreach ($lst as $pack) {
        	      	
            $markerArray['###PACK_LINK###']   = $url = tslib_pibase::pi_getPageLink($pack["uid"]);
            $markerArray['###PACK_NAME###']   = $pack["Name"];
            $markerArray['###PACK_INTRO###']  = $pack["Intro"];
            
            /* image sur le serveur ou pas */
            if (strpos($pack["image2__c"],'fileadmin') === 0 ) {
            	$urlImage = $pack["image2__c"];
            	
            } else {

            	$path_parts = pathinfo($pack["image2__c"]);
            	$urlImage = "uploads/user_azelizsalesforce_packs/".$path_parts['basename'];
            }
            
            
            
            $markerArray['###PACK_IMAGE###'] = $this->resize_img($urlImage,$pack["Name"], $pack["Name"], 235, 235,false);
            $markerArray['###IMAGE###'] = $this->resize_img($urlImage,$pack["Name"], $pack["Name"], 1000, 1000,false);
                    
            $eltLi .= $this->cObj->substituteMarkerArrayCached($subpart, $markerArray);
        }

        /* ajout de ul */
        $markerArray = array();
        $subpart = $this->cObj->getSubpart($this->templateHtml, '###TEMPLATE###');
        $markerArray['###ELEMENT_LI###'] = $eltLi;
        
        $content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray);
        
        /* On rajoute le jquery  si besoin */
        $subpart = $this->cObj->getSubpart($this->templateHtml, '###JQUERY###');
        
        $content .= $this->cObj->substituteMarkerArrayCached($subpart,array());;
        
        return $this->pi_wrapInBaseClass($content);
    }
  
  /**
    * Retourne un tableau contenant le paramétrage du plugin pi_flexform
    */
    private function getOptions() {
    	$fields = $this->getPIValues();
    	$fields['selOption'] = intval(@$fields['selOption']);
    	$fields['affOption'] = intval(@$fields['affOption']);
    	return $fields;
    }    
    /**
    * Permet de retailler une image 
    */
    function resize_img($image, $title, $alt, $maxH, $maxW, $crop=false){
    
        $img['file'] = $image;
        $lConf['file.']['maxH']=$maxH;
        $lConf['file.']['maxW']=$maxW;
        $lConf['altText']=$alt;
        $lConf['titleText']=$title;
    
        $lConf['emptyTitleHandling']='removeAttr';
    
        // Si on veut forcer une taille d'image sans conserver l'homothétie,
        // (par exemple toujours afficher une image carrée quelle que soit l'image d'origine) on utilise un "crop" sur l'image :
    
        if ($crop==true) {
            $lConf['file.']['height']=$maxH.'c';
            $lConf['file.']['width']=$maxW.'c';
        }
        return $this->cObj->cImage($img["file"], $lConf);
    }  
    
    /**
     * Retourne les uid des sous-pages de la page courante passé en paramètre
     * L'ordre des uids peuvent être désordonnés. Cela correspond à l'ordre des sous-pages
     * @param string $pageID
     */
    private function getUIDs($pageID) {
        if ($pageID != null) {
            $subPageID = array();
            $query = $GLOBALS["TYPO3_DB"]->exec_SELECTquery('uid,pid,deleted', 'pages', 'pid='.$pageID.' AND deleted=0');
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($query)) {
                if ($row['deleted'] == 0) {
                    array_push($subPageID, $row['uid']);
                }
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($query);
            return $subPageID;
            
        } else {
            return null;
        }
    }
    /**
    * Retourne les uid des pages sélectionné 
    */
    private function getPacks() {
    	return explode(',',$this->options['listpacks']);
    }

    /**
    * Retourne un tableau contenant les informations de contenu  pi_flexform de chaque page passée en paramètre
    * @param array $pageID : ID des pages
    * @return array : uid, code, name, intro, image2_c
    */
    private function getinfoContent($uid) {
    	
    	$info = array();
    	$info['uid']= $uid;
    	$query = $GLOBALS["TYPO3_DB"]->exec_SELECTquery('uid,pi_flexform,deleted,list_type', 'tt_content',  'deleted=0 AND hidden=0 AND pid='.$uid);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($query);

		if (count($row) !== 4) return $info;
    	switch ($row['list_type']) {
    		case 'user_azelizsalesforce_packs_pi1':
    			/* Pack : recherche dans salesforce */
    			/* TODO améliorer la recherche dans flexform */
    			$data = $this->getPIValues($row['pi_flexform']);
    			$info['PackCode']  = $data['PackCode'];
    			$data = $this->getDataJson("opportunity/code/".$info['PackCode']);
    			$info["Name"] = $data["Name"];
    			$info["Intro"] = $data["intro__c"];
    			$info["image2__c"] = $data["image2__c"];
    			
    			break;
    	    case 'user_azelizsalesforce_produit_pi1':
    	    	$data = $this->getPIValues($row['pi_flexform']);
    	    	$info['PackCode']  = $data['ProductCode'];
    	    	$info["Name"] = $data["Name"];
    	    	$info["Intro"] = $data["Intro"];
    	    	$info["image2__c"] = "fileadmin/produits/images/".$data["Image"];
    	    	
    	    	
    		default:
    			break;
    	}
    	
		$GLOBALS['TYPO3_DB']->sql_free_result($query);
		return $info;
    }
    
    
    
    /**
     * Retourne le Json d'une Url sous forme d'un tableau ou d'object
     * @param string sous repertoire formant l'url
     * @param Zend_Json::TYPE Type de l'object retourné (Array ou Object)
     * @return array or object : Suivant le paramètre $option
     */
    private function getDataJson($subURL, $options = Zend_Json::TYPE_ARRAY) {
        $dataJson = file_get_contents($this->baseURL.$subURL);
        $data = Zend_Json::decode($dataJson, $options);
       
        
        return $data;
    }
    /**
    * Retourne un tableau contenant la configuration flexform du plugin coté Backend.
    * Le tableau organise les éléments sous la forme "key" => "value"
    * @param String $xml_flexform : information de la base flexform
    *        Si null alors on retourne le flexform du plugin     
    * @return array $lConf
    */
    private function getPIValues($xml_flexform=null) {

    	if ($xml_flexform != null) {
    		$piFlexForm = t3lib_div::xml2array($xml_flexform);
    	} else {
    		$this->pi_initPIflexform();
    		$piFlexForm = $this->cObj->data['pi_flexform'];
    	}
    	$lConf = array(); // Setup our storage array...
    	   
    	foreach ($piFlexForm['data'] as $sheet => $data) {
    		foreach ($data as $lang => $value) {
    			foreach ($value as $key => $val) {
    				if ( is_array($val) ) {
    					// SI VRAI
    					if ( array_key_exists("_TRANSFORM_vDEF.vDEFbase", $val) )
    					$lConf[$key] = $val["_TRANSFORM_vDEF.vDEFbase"]; // Gère les balises p
    					else
    					$lConf[$key] = $val["vDEF"]; // Ne gère pas les balises p
    				} else {
    					$lConf[$key] = $this->pi_getFFvalue($piFlexForm, $key, $sheet);
    				}
    			}
    		}
    	}
    	return $lConf;
    }
    
    
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_listing_packs/pi1/class.user_listingpacks_pi1.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_listing_packs/pi1/class.user_listingpacks_pi1.php']);
}

?>
