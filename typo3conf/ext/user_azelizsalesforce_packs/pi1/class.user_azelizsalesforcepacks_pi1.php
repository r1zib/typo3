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
if (!class_exists("Zend_Loader_Autoloader")) {
    require_once("Zend/Loader/Autoloader.php");
    Zend_Loader_Autoloader::getInstance();
}

/**
 * Plugin 'Pack nke - SaleForces' for the 'user_azelizsalesforce_packs' extension.
 *
 * @author    Marc-Antoine TREHIN <marcantoine.trehin@gmail.com>
 * @package    TYPO3
 * @subpackage    user_azelizsalesforcepacks
 */
class user_azelizsalesforcepacks_pi1 extends tslib_pibase {
    var $prefixId      = 'user_azelizsalesforcepacks_pi1';        // Same as class name
    var $scriptRelPath = 'pi1/class.user_azelizsalesforcepacks_pi1.php';    // Path to this script relative to the extension dir.
    var $extKey        = 'user_azelizsalesforce_packs';    // The extension key.
    var $pi_checkCHash = true;
    var $baseURL = "http://apps.nke-marine-electronics.fr/typo3/";
    var $pack;
    
    /**
     * The main method of the PlugIn
     *
     * @param    string        $content: The PlugIn content
     * @param    array        $conf: The PlugIn configuration
     * @return    The content that is displayed on the website
     */
    function main($content, $conf) {
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();

        $this->pack = $this->getPack();

        $this->templateHtml = $this->cObj->fileResource($conf['templateFile']);
        $subpart = $this->cObj->getSubpart($this->templateHtml, '###TEMPLATE###');
        
        $markerArray = array();
        $markerArray['###NAME###'] = $this->pack["Name"];
        $markerArray['###CODE###'] = $this->pack["Code"];
        $markerArray['###Id###'] = $this->pack["Id"];
        $markerArray['###INTRO__C###'] = $this->pack["intro__c"];
        $markerArray['###IMAGE2__C###'] = $this->pack["image2__c"];
        
        $markerArray['###LIB_STD__C###'] = $this->pack["lib_std__c"];
        $markerArray['###IMAGE__C###'] = $this->pack["image__c"];
        $markerArray['###PRODUCTS_STD###'] = $this->listProduits('std');
        $markerArray['###PRODUCTS_STD_AMOUNT###'] = $this->pack["products_std_amount"];
        $markerArray['###PRODUCTS_STD_AMOUNT_TTC###'] = $this->pack["products_std_amount_ttc"];

        $markerArray['###LIB_OPT1__C###'] = $this->pack["lib_opt1__c"];
        $markerArray['###PRODUCTS_OPT1###'] = $this->listProduits('opt1');
        $markerArray['###PRODUCTS_OPT1_AMOUNT###'] = $this->pack["products_opt1_amount"];
        $markerArray['###PRODUCTS_OPT1_AMOUNT_TTC###'] = $this->pack["products_opt1_amount_ttc"];
        
        $markerArray['###LIB_OPT2__C###'] = $this->pack["lib_opt2__c"];
        $markerArray['###PRODUCTS_OPT2###'] = $this->listProduits('opt2');
        $markerArray['###PRODUCTS_OPT2_AMOUNT###'] = $this->pack["products_opt2_amount"];
        $markerArray['###PRODUCTS_OPT2_AMOUNT_TTC###'] = $this->pack["products_opt2_amount_ttc"];
        
        $markerArray['###LIB_OPT3__C###'] = $this->pack["lib_opt3__c"];
        $markerArray['###PRODUCTS_OPT3###'] = $this->listProduits('opt3');
        $markerArray['###PRODUCTS_OPT3_AMOUNT###'] = $this->pack["products_opt3_amount"];
        $markerArray['###PRODUCTS_OPT3_AMOUNT_TTC###'] = $this->pack["products_opt3_amount_ttc"];
        
        $markerArray['###PRODUCTS_C1###'] = $this->listProduitcomplements('c1');
        $markerArray['###PRODUCTS_C2###'] = $this->listProduitcomplements('c2');
        $markerArray['###PRODUCTS_C3###'] = $this->listProduitcomplements('c3');
        
        list($rep,$image) = $this->transfertImage($this->pack["image2__c"],'./uploads/user_azelizsalesforce_packs/');
        // reformatage pour typo, cela na marche pas avec /upload.....
        $imageRessource = 'uploads/user_azelizsalesforce_packs/'.$image;
        $markerArray['###IMAGE_BATEAU###'] = '<a href="'.$rep.$image.'" alt="'.$this->pack["Name"].'" class="rzcolorbox cboxElement pack-avatar" rel="rzcolorbox[cb253]">';
        $markerArray['###IMAGE_BATEAU###'] .= $this->resize_img($imageRessource,$this->pack["Name"], $this->pack["Name"], 218, 218,false);
        $markerArray['###IMAGE_BATEAU###'] .= '</a>';
        
        $content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray);

        return $this->pi_wrapInBaseClass($content);
    }
    
    /*
     * transfertImage
     *   Transfert une image sur le serveur
     *
     * @param string $url url de l'image
     * @param string $rep répertoire ou stocker les images sur le serveur (par défaut le fichier de config livedocx.image)
     * @return array ($repertoire de l'image, $nom de l'image)
     * @throws Zend_Service_Exception
     */

    public function transfertImage($url, $rep=null) {

        if ($rep == null) {
            $rep = $this->getRepertoireImage();
        }

        // Rien à faire, image est vide
        if ($url == '') return ;
        
        $file = file_get_contents($url);
        
        if ($file === false) {
            throw new Zend_Service_Exception('PB dans la lecture de'.$url."\n",1);
        }
        // nom de l'image
        $tab = explode('/',$url);
        $nom = $tab[count($tab) - 1 ];
        $out = $rep.$nom;

        if (file_put_contents($out,$file) === false) {
            throw new Zend_Service_Exception("PB d'écriture dans le répertoire".$rep."\n",2);
        }
        return array($rep, $nom);

    }

    
    /*
     * premet de retailler un image 
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
    * Retourne un tableau contenant l'opportunité depuis le code dans pi_flexform
    */
    private function getPack() {
        $dataXML = Zend_Json::fromXml($this->cObj->data['pi_flexform']);
        $dataFlexform = Zend_Json::decode($dataXML, Zend_Json::TYPE_ARRAY);

        $packCode = $dataFlexform["T3FlexForms"]["data"]["sheet"]["language"]["field"]["value"];
        $subUrl = "opportunity/code/".$packCode;
        $data = $this->getDataJson($subUrl);
         
        $data["Code"] = $packCode;
         
        return $data;
    }
    
    /**
    * Retourne le Json d'une Url sous forme d'un tableau ou d'object
    * @param string sous repertoire formant l'url'
    * @param Zend_Json::TYPE Type de l'object returné (Array ou Object)'
    * @return array or object
    */
    private function getDataJson($subUrl, $option = Zend_Json::TYPE_ARRAY) {
        $dataJson = file_get_contents($this->baseURL.$subUrl);
        $data = Zend_Json::decode($dataJson, $option);
        
        return $data;
    }
    
    /**
    * Retourne le liste des produits 
    * @param String type std, opt1, opt2....
    * @return String la liste des produits au format html 
    */
    private function listProduits($type) {
        if (!isset($this->pack['products_'.$type])) return '';
        $content = '';
        foreach($this->pack['products_'.$type] as $product) {
            $content .= '<li>'.$product["Quantity"]." ".$product["Name"];
            if (array_key_exists("complement__c", $product)) {
                $content .= " ".$product["complement__c"];
            }
            if (array_key_exists("ProductCode", $product)) {
                $content .= ' <span class="product-code">['.$product["ProductCode"].']</span>';
            }
            $content .= '</li>';
        }
        return $content;
    }
   
   /**
    * Retourne le contenu des Compléments
    * @param String type c1, c2, c3 ....
    * @return String la liste des produits au format html 
    */
    private function listProduitcomplements($type) {
        if (!isset($this->pack['products_'.$type])) return '';
        $content = "";
        foreach ($this->pack['products_'.$type] as $product) {
            $content .= "<li>".$product["Name"];
            if (array_key_exists("complement__c", $product)) {
                $content .= " ".$product["complement__c"];
            }
            if (array_key_exists("ProductCode", $product)) {
                $content .= ' <span class="product-code">['.$product["ProductCode"].']</span>';
            }
            if (array_key_exists("complement__c", $product)) {
                $content .= '<span class="product-complement">'.$product["complement__c"].'</span>';
            }
    
            $content .= '<div><span class="product-price">'.$product["UnitPrice"].' € HT</span>'.
                        '<span class="product-price-ttc">'.$product["UnitPrice_ttc"].' € TTC</span>'.
                        '</div></li>';
        }
        return $content;
    }
  
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_azelizsalesforce_packs/pi1/class.user_azelizsalesforcepacks_pi1.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_azelizsalesforce_packs/pi1/class.user_azelizsalesforcepacks_pi1.php']);
}

?>
