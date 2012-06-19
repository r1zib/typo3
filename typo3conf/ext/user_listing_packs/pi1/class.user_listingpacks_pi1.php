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
 * Plugin 'nke liste des pages' for the 'user_listing_packs' extension.
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
    
    const BASE_URL = "http://apps.nke-marine-electronics.fr/typo3/";
    
    var $PIValues = array();
    var $packs = array();
    
    var $infoPage = array();
    
    
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
        
        // Récupération de la config backend du plugin
        $this->PIValues = $this->getPIValues();
        //$this->options = $this->getOptions();
        
        // Récupération des choix de pack à afficher
        if ($this->PIValues['selectType'] == "select-pack") {
            $pageUIDs = explode(',', $this->PIValues["selectPacks"]);
            $this->infoPage = array();
            foreach($pageUIDs as $pageUID) {
            	$page = array('uid' => $pageUID, 'link' => $pageUID);
            	$this->infoPage[] = $page;
            }   
        // Récupération des packs des sous-pages
        } else {
        	// mémorisation de la recherche dans $this->infoPage
        	$this->getSubPageUIDs($this->cObj->data["pid"]);
        }
        
        // On complete 	les informations sur la page avec le contenue
        foreach($this->infoPage as &$page) {
            $info = $this->getInfoContent($page['uid']);
            $page = array_merge($page,$info);
        }
        unset($page);
        
        // Récupération du template suivant le type d'affichage donnée dans la config backend du plugin
        $this->templateHtml = $this->cObj->fileResource($this->getTemplatePath());
        //Zend_Debug::dump($this->getTemplatePath());
        
        // Création des balises <li>
        $subpart = $this->cObj->getSubpart($this->templateHtml, '###STRUCTURE-LI###');
        $baliseLI = '';
        $markerArray = array();
        
        foreach ($this->infoPage as $pack) {
        	if (isset($pack['link'])) {
        		$url = $pack["link"];
        	} else {
        		$url = $pack["uid"];
        	}
        	
            $markerArray['###PACK_LINK###']   = tslib_pibase::pi_getPageLink($url);
            $markerArray['###PACK_NAME###']   = $pack["Name"];
            $markerArray['###PACK_INTRO###']  = $pack["Intro"];
            
            /* image sur le serveur ou pas */
            if (strpos($pack["image2__c"],'fileadmin') === 0 ) {
                $urlImage = $pack["image2__c"];
                
            } else {
                $path_parts = pathinfo($pack["image2__c"]);
                $urlImage = "uploads/user_azelizsalesforce_packs/".$path_parts['basename'];
            }
            
            $markerArray['###PACK_IMAGE###'] = $this->resize_img($urlImage,$pack["Name"], $pack["Name"], 300, 182,false);
            $markerArray['###IMAGE###'] = $this->resize_img($urlImage,$pack["Name"], $pack["Name"], 1000, 1000,false);
                    
            $baliseLI .= $this->cObj->substituteMarkerArrayCached($subpart, $markerArray);
        }

        // Englobement des <li> dans une <ul>
        $subpart = $this->cObj->getSubpart($this->templateHtml, '###TEMPLATE###');
        $markerArray = array();
        $markerArray['###ELEMENT_LI###'] = $baliseLI;
        $markerArray['###PluginUID###'] = $this->cObj->data["uid"];
        $content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray);
        
        /* On rajoute le jquery  si besoin */
        $subpart = $this->cObj->getSubpart($this->templateHtml, '###JQUERY###');
        $markerArray = array();
        $markerArray['###PluginUID###'] = $this->cObj->data["uid"];
        $content .= $this->cObj->substituteMarkerArrayCached($subpart,$markerArray);
        return $this->pi_wrapInBaseClass($content);
    }
    
    
    /**
    * Retourn le chemin du template suivant le type d'affichage donnée dans le PIValues
    * @ return string $path
    */
    private function getTemplatePath() {
        switch ($this->PIValues['displayMode']) {
            case 'liste' :
                $path = $this->conf['templateFile'];
                break;
            
            case 'bxslider' :
                $path = $this->conf['templateBxSlider'];
                break;
            
            case 'slide estro' :
                $path = $this->conf['templateEstro'];
                break;
            
            default :
                $path = $this->conf['templateFile'];
                break;
        }
        
        return $path;
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
     * Met à jours les uid et link des sous-pages de la page courante passé en paramètre
     * L'ordre des uids peuvent être désordonnés. Cela correspond à l'ordre des sous-pages
     * @param string $pageID
     */
    private function getSubPageUIDs($pageID) {
        $this->infoPage = array();
    	
    	if ($pageID != null) {
	    // ED 22/03/12 On ne doit pas voir les pages cachées
            $query = $GLOBALS["TYPO3_DB"]->exec_SELECTquery('uid,pid,deleted,mount_pid,doktype,hidden', 'pages', 'pid='.$pageID.' AND deleted=0 AND hidden=0');
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($query)) {
            	if ($row['deleted'] != 0) continue;
            	$page = array();
            	switch ($row['doktype']) {
                	case 1 : /* type = Standard */ 
                		$page['uid']   = $row['uid'];
                		$page['link'] = $row['uid'];
                	    break;
                	case 7:  /* type = Mount Page */
                		$page['uid'] = $row['mount_pid'];
                		$page['link'] = $row['uid'];
                	break;
                }
                $this->infoPage[] = $page;
                
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($query);
            
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
    * Retourne un tableau contenant les informations de contenu pi_flexform de chaque page passée en paramètre
    * @param array $pageID : ID des pages
    * @return array : code, name, intro, image2_c
    */
    private function getInfoContent($uid) {
        $pack = array();
        $pack_std = array();
         // ED 22/03/12 On ne doit pas voir les contenues cachées
        $res = $GLOBALS["TYPO3_DB"]->exec_SELECTquery('uid,pi_flexform,deleted,list_type,header,header_link,bodytext,image,CType,tx_templavoila_flex,subheader,sys_language_uid,hidden', 'tt_content', 'deleted=0 AND hidden=0 AND pid='.$uid);
        
        
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

		switch ($row['list_type']) {
		    case 'user_azelizsalesforce_packs_pi1':
		        $data = $this->getPIValues($row['pi_flexform']);
		        $pack['PackCode']  = $data['PackCode'];
		        $data = $this->getDataJson("opportunity/code/".$pack['PackCode']);
		        $pack["Name"] = $data["Name"];
		        $pack["Intro"] = $data["intro__c"];
		        $pack["image2__c"] = $data["image2__c"];
		        break;
		        
		    case 'user_azelizsalesforce_produit_pi1':
		        $data = $this->getPIValues($row['pi_flexform']);
		        $pack['PackCode']  = $data['ProductCode'];
		        $pack["Name"] = $data["Name"];
		        $pack["Intro"] = $data["Intro"];
		        $pack["image2__c"] = "fileadmin/produits/images/".$data["Image"];
		        break;
		    case '' :
		    	if ($row['CType'] == 'templavoila_pi1') {
		    		$data = $this->getPIValues($row['tx_templavoila_flex']);
				$temp = array();
				$temp['PackCode']  = "";
		    		$temp["Name"] = $row["header"];
		    		$temp["Intro"] = $row['subheader'];
		    		$temp["image2__c"] = "fileadmin/produits/".$data["field_image"];
	   		    
			  	/* cas du multilangue */
				if ($GLOBALS['TSFE']->sys_language_uid ==  $row["sys_language_uid"] ) {
		                   $pack = $temp;
				}
			  	/* cas du multilangue */
				if ($row["sys_language_uid"] == 0) {
		                   $pack_std = $temp;
				}
		    	}

		    	break;
		}
	}
	/* dans le cas ou l'on a pas trouvé de tranduction alors on affiche dans la langue standard */
        if (count($pack) == 0) {
	    $pack = $pack_std;	
	}

        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        
        return $pack;
    }
    
    
    /**
     * Retourne le Json d'une Url sous forme d'un tableau ou d'object
     * @param string sous repertoire formant l'url
     * @param Zend_Json::TYPE Type de l'object retourné (Array ou Object)
     * @return array or object : Suivant le paramètre $option
     */
    private function getDataJson($subURL, $options = Zend_Json::TYPE_ARRAY) {
        /* on ajoute la langue */
	$subURL .= '/lg/'.$GLOBALS['TSFE']->lang;  

        $dataJson = file_get_contents(self::BASE_URL.$subURL);
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
    private function getPIValues($xml_flexform = null) {
        if ($xml_flexform != null) {
            $piFlexForm = t3lib_div::xml2array($xml_flexform);
        } else {
            $this->pi_initPIflexform();
            $piFlexForm = $this->cObj->data['pi_flexform'];
            //Zend_Debug::dump($piFlexForm);
        }
        
        $lConf = array(); // Setup our storage array...
           
        foreach ($piFlexForm['data'] as $sheet => $data) {
            foreach ($data as $lang => $value) {
            	if ($lang != 'lDEF') continue;
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
