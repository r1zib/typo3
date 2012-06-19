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

/**
 * Plugin 'Contacts Azeliz' for the 'user_azeliz_contacts' extension.
 *
 * @author    Marc-Antoine TREHIN <marcantoine.trehin@gmail.com>
 * @package    TYPO3
 * @subpackage    user_azelizcontactsd
 */
class user_azelizcontacts_pi1 extends tslib_pibase {
    var $prefixId      = 'user_azelizcontacts_pi1';        // Same as class name
    var $scriptRelPath = 'pi1/class.user_azelizcontacts_pi1.php';    // Path to this script relative to the extension dir.
    var $extKey        = 'user_azeliz_contacts';    // The extension key.
    var $pi_checkCHash = true;
    
    private $markerArray = array(); // Tableau des marqueur du template
    private $formError = array(); // Tableau des champs formulaire nom valide avec message d'erreur
    private $formValue = array(); // Tableau des champs formulaire avec les valeurs utilisateurs
    private $criticalError; // Tableau des erreurs critiques du programme
    private $sendContact;

    private $log; // log dans le fichier /fileadmin/logs/contact.log
    
    /* information du flexform */
    var $PIValues = array();
    
    var $upload = 'uploads/user_azeliz_contacts/';
    
    
    /**
    * Constructeur de la classe faisant appel au constructeur parent
    */
    public function __construct() {
        parent::__construct();
        require_once 'Zend/Loader/Autoloader.php';
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Az_');
       	$this->initLog();
    }
    
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
        // Loading language-labels
        $this->pi_loadLL();

        // Récupération de la config backend du plugin
	$this->PIValues = $this->getPIValues();
        
        $this->pi_USER_INT_obj = 1;
        
        /* permet d'avoir les méthodes pour faciliter les transfert */
        $this->fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');

        if (isset($_POST['formname'])) {
            $this->log->debug('Requete : '.Zend_Json::encode($_POST));
            $this->getPostRequest();
            
            /* un validation en ajax de contact_default_cv impossible */
            //if ($_POST['formname'] == 'contact_default_cv' && 
            //    ! isset($this->PIValues['form'])) return '';
            
            /* permet le téléchargement de fichier */
            if (count($this->formError) == 0 && isset($this->PIValues['form']) ) {
                $erreur = $this->download();
                $this->log->debug('Document  : Erreurs ' .Zend_Json::encode($erreur));
                if ($erreur !== true) {
                    $this->add_erreur('cv', $erreur);
                    Zend_Debug::dump('add_erreur');
                }        
            } 
            
            if (count($this->formError) == 0) {
                
                
                // Envoi des données vers les services
                $content = '<div id="'.$this->formValue['formname'].'"><div class="ajax-replace-content">';
                $erreur = false;
                
                $sendContact = new Az_SendContact($conf['config'],$this->formValue);
                Zend_Debug::dump($this->formValue);
                #$ret = $sendContact->sendRapide();
                $ret = $sendContact->send();
                                
                if (!$ret) {
                	if ($sendContact->getError() == Az_SendContact::KO_SPAM) {
                		$content .= "<p>".$this->pi_getLL('ko_spam')."</p>";
                	} else {
                		$content .= "<p>".$this->pi_getLL('ko_maj')."</p>";
                	}
                	
                	
                	$this->log->debug('Formulaire : Erreurs ' .$ret);
                } else {
                	$content .= $this->successMessage();
                }
                
                $content .= '</div><div>';
                
            } else {
	    	$this->log->debug('Formulaire : Erreurs ' .Zend_Json::encode($this->formError));
                $content = $this->initForms();
                $content .= $this->initJS();
            }
            
        } else {
            $content = $this->initForms();
            $content .= $this->initJS();
        }
        
        return $this->pi_wrapInBaseClass($content);
    }
    
    function traceMaj ($dest, $ret , $msg ) {
    	$this->sendContact->traceDest($dest, $ret, $msg);
    }
    
    
    private function getPostRequest() {
        $this->formValue['formname'] = $_POST['formname'];
        $this->formValue['zone'] = $_POST['zone'];
        
        if (isset($_POST['LastName']) && (trim($_POST['LastName']) == '') )
            $this->add_erreur('LastName', $this->pi_getLL ('oblig_lastname'));
        $this->formValue['LastName'] = $_POST['LastName'];
          
        if (isset($_POST['FirstName']) && (trim($_POST['FirstName']) == '') )
            $this->add_erreur('FirstName', $this->pi_getLL ('oblig_firstname'));
        $this->formValue['FirstName'] = $_POST['FirstName'];
        /*
        if (isset($_POST['Telephone']) && (trim($_POST['Telephone']) == '') )
            $this->add_erreur('Telephone', 'Veuillez renseigner votre numéro de téléphone');
        */
        $this->formValue['Telephone'] = $_POST['Telephone'];
        
        if (isset($_POST['Email']) && (filter_var($_POST['Email'], FILTER_VALIDATE_EMAIL) == false))
            $this->add_erreur('Email', $this->pi_getLL ('pb_email'));
        $this->formValue['Email'] = $_POST['Email'];
        
        if (isset($_POST['Objet']) && (trim($_POST['Objet']) == '') )
            $this->add_erreur('Objet', $this->pi_getLL ('oblig_object'));
        $this->formValue['Objet'] = $_POST['Objet'];
        
        if (isset($_POST['Message']) && (trim($_POST['Message']) == '') )
            $this->add_erreur('Message', $this->pi_getLL ('oblig_message'));
        $this->formValue['Message'] = $_POST['Message'];
        
        if (isset($_POST['Ville']) && (trim($_POST['Ville']) == '') )
            $this->add_erreur('Ville', $this->pi_getLL ('oblig_town'));
        $this->formValue['Ville'] = $_POST['Ville'];
        
        if (isset($_POST['Port']) && (trim($_POST['Port']) == '') )
            $this->add_erreur('Port', $this->pi_getLL ('oblig_port'));
        $this->formValue['Port'] = $_POST['Port'];
        /*
        if (isset($_POST['ChantierNaval']) && (trim($_POST['ChantierNaval']) == '') )
            $this->add_erreur('ChantierNaval', 'Veuillez renseigner votre chantier naval');
        $this->formValue['ChantierNaval'] = $_POST['ChantierNaval'];
	*/

	$this->formValue['whatId'] = $_POST['whatId'];

    }
    
    
    private function initForms() {
        $this->markerArray['###URL###'] = $_SERVER["REQUEST_URI"];
        $this->markerArray['###Zone###'] = $this->getFieldValue('zone');
        
        $this->markerArray['###TITLE_CONTACT###'] = $this->pi_getLL ('title_contact');
        $this->markerArray['###TITLE_CONTACT_CART###'] = $this->pi_getLL ('title_contact_cart');
        $this->markerArray['###TITLE_CONTACT_ENSAVOIRPLUS###'] = $this->pi_getLL ('title_contact_ensavoirplus');
        $this->markerArray['###TITLE_CONTACT_PDF###'] = $this->pi_getLL ('title_contact_pdf');
        $this->markerArray['###TITLE_CONTACT_AUTRE###'] = $this->pi_getLL ('title_contact_autre');
        
        $this->markerArray['###LABEL_LASTNAME###'] = $this->pi_getLL ('label_lastname');
        $this->markerArray['###LastName###'] = $this->getFieldValue('LastName');
        $this->markerArray['###LastName_erreur_class###'] = $this->class_erreur('LastName');
        $this->markerArray['###LastName_erreur###'] = $this->msg_erreur('LastName');
        
        $this->markerArray['###LABEL_FIRSTNAME###'] = $this->pi_getLL ('label_firstname');
        $this->markerArray['###FirstName###'] = $this->getFieldValue('FirstName');
        $this->markerArray['###FirstName_erreur_class###'] = $this->class_erreur('FirstName');
        $this->markerArray['###FirstName_erreur###'] = $this->msg_erreur('FirstName');

        $this->markerArray['###LABEL_PHONE###'] = $this->pi_getLL ('label_phone');
        $this->markerArray['###Telephone###'] = $this->getFieldValue('Telephone');
        $this->markerArray['###Telephone_erreur_class###'] = $this->class_erreur('Telephone');
        $this->markerArray['###Telephone_erreur###'] = $this->msg_erreur('Telephone');

        $this->markerArray['###LABEL_EMAIL###'] = $this->pi_getLL ('label_email');
        $this->markerArray['###Email###'] = $this->getFieldValue('Email');
        $this->markerArray['###Email_erreur_class###'] = $this->class_erreur('Email');
        $this->markerArray['###Email_erreur###'] = $this->msg_erreur('Email');

        $this->markerArray['###LABEL_OBJECT###'] = $this->pi_getLL ('label_object');
        $this->markerArray['###Objet###'] = $this->getFieldValue('Objet');
        $this->markerArray['###Objet_erreur_class###'] = $this->class_erreur('Objet');
        $this->markerArray['###Objet_erreur###'] = $this->msg_erreur('Objet');

        $this->markerArray['###LABEL_MESSAGE###'] = $this->pi_getLL ('label_message');
        $this->markerArray['###Message###'] = $this->getFieldValue('Message');
        $this->markerArray['###Message_erreur_class###'] = $this->class_erreur('Message');
        $this->markerArray['###Message_erreur###'] = $this->msg_erreur('Message');

        $this->markerArray['###Ville###'] = $this->getFieldValue('Ville');
        $this->markerArray['###Ville_erreur_class###'] = $this->class_erreur('Ville');
        $this->markerArray['###Ville_erreur###'] = $this->msg_erreur('Ville');

        $this->markerArray['###Port###'] = $this->getFieldValue('Port');
        $this->markerArray['###Port_erreur_class###'] = $this->class_erreur('Port');
        $this->markerArray['###Port_erreur###'] = $this->msg_erreur('Port');
        
        $this->markerArray['###LABEL_CV###'] = $this->pi_getLL ('label_cv');;
        $this->markerArray['###cv_erreur_class###'] = $this->class_erreur('cv');
        $this->markerArray['###cv_erreur###'] = $this->msg_erreur('cv');
        
        
	/*
        $this->markerArray['###ChantierNaval###'] = $this->getFieldValue('ChantierNaval');
        $this->markerArray['###ChantierNaval_erreur_class###'] = $this->class_erreur('ChantierNaval');
        $this->markerArray['###ChantierNaval_erreur###'] = $this->msg_erreur('ChantierNaval');
	*/
        $this->markerArray['###Maj_erreur###'] = $this->msg_erreur('maj');
        $this->markerArray['###Erreur###'] = "";

        $this->markerArray['###whatId###'] = $this->getFieldValue('whatId');
	
        //Zend_Debug::dump($this->current_lang);
	if ($GLOBALS['TSFE']->sys_language_uid == 4) {
            $this->templateHtml = $this->cObj->fileResource($this->conf['formulairesENTPL']);
       	} else {
            $this->templateHtml = $this->cObj->fileResource($this->conf['templateFormulaires']);
        }
        
        
        /* Parfois on peut vouloir le formulaire directement dans une page */
        
        $template = '###TEMPLATE###';
        
        if (isset($this->PIValues['form'])) {
            switch ($this->PIValues['form']) {
                case 'contact' : $template = '###CONTACT_DEFAULT###'; break;
                case 'other' : $template = '###CONTACT_OTHER###'; break;
            }
        }
        $subpart = $this->cObj->getSubpart($this->templateHtml, $template);
        return $this->cObj->substituteMarkerArrayCached($subpart, $this->markerArray);
    }
    
    /**
    * Retour le template javascript en remplaçant les marqueurs
    */
    private function initJS() {
        $this->markerArray['###URL###'] = $_SERVER["REQUEST_URI"];
        $this->markerArray['###WAIT###'] =  $this->pi_getLL ('wait');
        
        $this->templateHtml = $this->cObj->fileResource($this->conf['templateJS']);
        $subpart = $this->cObj->getSubpart($this->templateHtml, '###TEMPLATE###');

        return $this->cObj->substituteMarkerArrayCached($subpart, $this->markerArray);
    }
    
    /*
    * Message d'erreur, il est important d'afficher le label même s'il n'y a pas de message
    */
    private function msg_erreur($field) {
        $erreur = '';
        $class = '';

        if (isset($this->formError[$field])) {
            $erreur = $this->formError[$field];
            $class = 'error';
        }

        return '<br /><label for="'.$field.'" class="'.$class.'" generated="true">'.$erreur.'</label>';
    }
    
    private function class_erreur($field) {
        if (isset($this->formError[$field]))
            return 'error';
        return '';
    }
    
    private function add_erreur($field,$msg) {
        $this->formError[$field] = $msg;
    }
    
    private function getFieldValue($field) {
        if (isset($this->formValue[$field]) )
            return $this->formValue[$field];
        return ''; 
    }
    
    private function createContact() {
        $contact = new Contact();
        
        $contact->setEmail($this->getFieldValue('Email'));
        $contact->setLastName($this->getFieldValue('LastName'));
        $contact->setFirstName($this->getFieldValue('FirstName'));
        $contact->setPhone($this->getFieldValue('Telephone'));
        $contact->setSujet($this->getFieldValue('Objet'));
        $contact->setWhatId($this->getFieldValue('whatId'));
        
        if ($this->getFieldValue('formname') == 'contact_default')
            $contact->setSujet("Contactez nke : ".$contact->getSujet());
        
        $corp = "[Origine : ".$this->getCurrentURL()."]\n".$this->getFieldValue('Message');
        
        if ($this->getFieldValue('formname') == 'contact_revendeur') {
            $corp .= "Ville de Résidence : ".$this->getFieldValue('Ville')."\n".
                     "Port d'attache : ".$this->getFieldValue('Port')."\n";
	    //"Chantier naval : ".$this->getFieldValue('ChantierNaval')."\n";
        }
            
        $contact->setTexte($corp);
            
        return $contact;
    }
    private function successMessage() {
       
        
        
        if ($this->getFieldValue("formname") == "contact_pdf_instru") {
            return '<p><a href="" target="_blank">'.$this->pi_getLL('ok_maj_contact_pdf_instru').'</a></p>';
        }
	
        return '<p>'.$this->pi_getLL('ok_maj').'</p>';
        
    }
    
    /*  Permet de Tracer dans un répertoire les validations de formulaire
     * 
     */
    private function initLog() {
    	$redacteur = new Zend_Log_Writer_Stream(__DIR__.'/../../../../fileadmin/logs/contact.log');
    	$this->log = new Zend_Log($redacteur);
    }
    
	 /* sendCreateSend : envoir des données dans googleDoc
    * @param Contact
    * return booblean/string  True-> le traitement c'est bien passé
    */
    
    

    
    private function getCurrentURL() {
         $pageURL = 'http';
         if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
         $pageURL .= "://";
         if ($_SERVER["SERVER_PORT"] != "80") {
             $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
         } else {
             $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
         }
         return $pageURL;
    }
    
    /*
     * Téléchargement des CV
     * @return boolean 
     */
    private function download() {
        /**/
        $erreur = '';
        
        if ($this->formValue['formname'] == 'contact_default_cv' && isset($_FILES['cv'])) {
            /* Vérification que le fichier est bien téléchargé */
            Zend_Debug::dump($_FILES['cv']);
            if ($_FILES['cv']['error'] > 0) {
                $erreur .= 'Erreur lors du tansfert/n';
            }
            /* Test sur les extensions */
            $extensions_valides = array( 'txt', 'doc', 'docx', 'pdf', 'odt'  );
            $extension_upload = strtolower(  substr(  strrchr($_FILES['cv']['name'], '.')  ,1)  );
            $name_upload = strtolower(  substr(  strrchr($_FILES['cv']['name'], '.')  ,0)  );
            
            if ( ! in_array($extension_upload,$extensions_valides) ) {
                $erreur .=  'Extension non correcte /n';
            }
            Zend_Debug::dump($extension_upload,"extension");
            if ($_FILES['cv']['size'] > 500 * 1024) $erreur .= 'Le fichier est trop gros./n';

            Zend_Debug::dump($_FILES['cv']['size'],'taille');
            
            if ($erreur == '') {
                /* Recherche d'un nom 
                 * Vérification si le nom existe
                 */

                $fichier_temp = $_FILES['cv']['tmp_name'];
                $fichier = $this->fileFunc->cleanFileName(basename($_FILES['cv']['name']));
                $chemin = PATH_site.$this->upload;
                if (!is_dir($chemin)) {
                    $erreur .=  'Répertoire n\'existe pas /n';
                }

                $file_path = $this->fileFunc->getUniqueName($fichier, $chemin);
                if (t3lib_div::upload_copy_move($fichier_temp, $file_path)) {
                    // si l'upload est completement réussi
                    $file = str_replace(PATH_site,$_SERVER["SERVER_NAME"].'/' ,$file_path );
                    
                    $this->formValue['cv'] = $file;
                    Zend_Debug::dump($this->formValue['cv']);
                } else {
                    $erreur .=  'Problème dans le téléchargement du fichier./n';
                }                   
            }
            
            
        }
        if ($erreur == '') {
            return true;
        } else {
            return $erreur;
        }
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_azeliz_contacts/pi1/class.user_azelizcontacts_pi1.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_azeliz_contacts/pi1/class.user_azelizcontacts_pi1.php']);
}

?>
