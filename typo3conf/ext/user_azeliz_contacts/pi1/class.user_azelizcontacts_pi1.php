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
require_once __dir__.'/../lib/ContactSalesforce.php';
require_once __dir__.'/../lib/Contact.php';
require_once __dir__.'/../lib/AddCreatesend.php';

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
    private $serviceSF = array('user' => 'contact@nke-marine-electronics.fr',
                                'pass' => 'wxcvbn,123',
                                'token' => 'GNyIcUu0XzoRaXjOlN3HEJTK');

   private $log; // log dans le fichier /fileadmin/logs/contact.log
    /**
    * Constructeur de la classe faisant appel au constructeur parent
    */
    public function __construct() {
        parent::__construct();
        require_once 'Zend/Loader/Autoloader.php';
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Azeliz_');

		$redacteur = new Zend_Log_Writer_Stream(getcwd().'/fileadmin/logs/contact.log');
	        $this->log = new Zend_Log($redacteur);
		if (!class_exists('SpreadSheetModel')) {
		    require_once(t3lib_extMgm::extPath('user_feedback') . 'lib/class.user_feedback_spreadsheetmodel.php');
		}
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
        $this->pi_loadLL();
        $this->pi_USER_INT_obj = 1;
        $this->log->debug('Formulaire : $_POST[formname] ' .$_POST['formname']);
        if (isset($_POST['formname'])) {
            $this->getPostRequest();
             $this->log->debug('Formulaire : Nombre d\'erreurs : ' .count($this->formError) );
            if (count($this->formError) == 0) {
                // Envoi des données vers les services
                $content = '<div id="'.$this->formValue['formname'].'"><div class="ajax-replace-content">';
                $contact = $this->createContact();
                if ($this->sendGoogledoc($contact) &&
                    $this->sendCreateSend($contact)) {
                     $this->log->debug('Formulaire : Send GoogleDoc/CreateSend c\'est bien passé ');
                    $content .= '<p>Votre demande est bien envoyée !</p>';
                } else {
                    $this->log->debug('Formulaire : Send GoogleDoc KO ');

                    $content .= "<p>Une erreur s'est produite lors de l'envoi du message :<br />".
                                $this->criticalError."</p>";
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
    
    
    private function getPostRequest() {
        $this->formValue['formname'] = $_POST['formname'];
        $this->formValue['zone'] = $_POST['zone'];
        
        if (isset($_POST['LastName']) && (trim($_POST['LastName']) == '') )
            $this->add_erreur('LastName', 'Veuillez renseigner votre nom');
        $this->formValue['LastName'] = $_POST['LastName'];
        
        if (isset($_POST['FirstName']) && (trim($_POST['FirstName']) == '') )
            $this->add_erreur('FirstName', 'Veuillez renseigner votre prénom');
        $this->formValue['FirstName'] = $_POST['FirstName'];
        /*
        if (isset($_POST['Telephone']) && (trim($_POST['Telephone']) == '') )
            $this->add_erreur('Telephone', 'Veuillez renseigner votre numéro de téléphone');
        */
        $this->formValue['Telephone'] = $_POST['Telephone'];
        
        if (isset($_POST['Email']) && (filter_var($_POST['Email'], FILTER_VALIDATE_EMAIL) == false))
            $this->add_erreur('Email', 'Votre e-mail est incorrect');
        $this->formValue['Email'] = $_POST['Email'];
        
        if (isset($_POST['Objet']) && (trim($_POST['Objet']) == '') )
            $this->add_erreur('Objet', 'Veuillez renseigner l\'objet du message');
        $this->formValue['Objet'] = $_POST['Objet'];
        
        if (isset($_POST['Message']) && (trim($_POST['Message']) == '') )
            $this->add_erreur('Message', 'Veuillez renseigner le corp du message');
        $this->formValue['Message'] = $_POST['Message'];
        
        if (isset($_POST['Ville']) && (trim($_POST['Ville']) == '') )
            $this->add_erreur('Ville', 'Veuillez renseigner votre ville');
        $this->formValue['Ville'] = $_POST['Ville'];
        
        if (isset($_POST['Port']) && (trim($_POST['Port']) == '') )
            $this->add_erreur('Port', 'Veuillez renseigner votre port d\'attache');
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
        
        $this->markerArray['###LastName###'] = $this->getFieldValue('LastName');
        $this->markerArray['###LastName_erreur_class###'] = $this->class_erreur('LastName');
        $this->markerArray['###LastName_erreur###'] = $this->msg_erreur('LastName');

        $this->markerArray['###FirstName###'] = $this->getFieldValue('FirstName');
        $this->markerArray['###FirstName_erreur_class###'] = $this->class_erreur('FirstName');
        $this->markerArray['###FirstName_erreur###'] = $this->msg_erreur('FirstName');

        $this->markerArray['###Telephone###'] = $this->getFieldValue('Telephone');
        $this->markerArray['###Telephone_erreur_class###'] = $this->class_erreur('Telephone');
        $this->markerArray['###Telephone_erreur###'] = $this->msg_erreur('Telephone');

        $this->markerArray['###Email###'] = $this->getFieldValue('Email');
        $this->markerArray['###Email_erreur_class###'] = $this->class_erreur('Email');
        $this->markerArray['###Email_erreur###'] = $this->msg_erreur('Email');

        $this->markerArray['###Objet###'] = $this->getFieldValue('Objet');
        $this->markerArray['###Objet_erreur_class###'] = $this->class_erreur('Objet');
        $this->markerArray['###Objet_erreur###'] = $this->msg_erreur('Objet');

        $this->markerArray['###Message###'] = $this->getFieldValue('Message');
        $this->markerArray['###Message_erreur_class###'] = $this->class_erreur('Message');
        $this->markerArray['###Message_erreur###'] = $this->msg_erreur('Message');

        $this->markerArray['###Ville###'] = $this->getFieldValue('Ville');
        $this->markerArray['###Ville_erreur_class###'] = $this->class_erreur('Ville');
        $this->markerArray['###Ville_erreur###'] = $this->msg_erreur('Ville');

        $this->markerArray['###Port###'] = $this->getFieldValue('Port');
        $this->markerArray['###Port_erreur_class###'] = $this->class_erreur('Port');
        $this->markerArray['###Port_erreur###'] = $this->msg_erreur('Port');
	/*
        $this->markerArray['###ChantierNaval###'] = $this->getFieldValue('ChantierNaval');
        $this->markerArray['###ChantierNaval_erreur_class###'] = $this->class_erreur('ChantierNaval');
        $this->markerArray['###ChantierNaval_erreur###'] = $this->msg_erreur('ChantierNaval');
	*/
        $this->markerArray['###Maj_erreur###'] = $this->msg_erreur('maj');
        $this->markerArray['###Erreur###'] = "";

        $this->markerArray['###whatId###'] = $this->getFieldValue('whatId');


        $this->templateHtml = $this->cObj->fileResource($this->conf['templateFormulaires']);
        $subpart = $this->cObj->getSubpart($this->templateHtml, '###TEMPLATE###');

        return $this->cObj->substituteMarkerArrayCached($subpart, $this->markerArray);
    }
    
    /**
    * Retour le template javascript en remplaçant les marqueurs
    */
    private function initJS() {
        $this->markerArray['###URL###'] = $_SERVER["REQUEST_URI"];
        
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
    
    private function sendSalesforce($contact) {
        $redacteur = new Zend_Log_Writer_Stream(getcwd().'/fileadmin/logs/contact.log');
        $log = new Zend_Log($redacteur);
        $log->info($contact->__toString());
        
        $send = new ContactSalesforce($this->serviceSF['user'], $this->serviceSF['pass'],
                                      $this->serviceSF['token'], __dir__.'/static/wsdl.xml');
        $status = $send->connection();
        
        if ($status !== TRUE ) {
            $this->criticalError = "-> Connexion error" ;
            $log->log($status,Zend_Log::CRIT);
            return false;
        }
        
        $status = $send->update($contact);
        if ($status !== TRUE ) {
            $this->criticalError = "-> Content message error";
            $log->log($status,Zend_Log::CRIT);
            return false;
        }
        
        return true;
    }

    private function sendGoogledoc($contact) {
       try {
		$this->log->info($contact->__toString());
		
		$ssModel = new SpreadSheetModel('marcantoine.trehin@azeliz.com', 'tev7g862');
	      	$this->log->debug('new SpreadSheetModel ' );

		$newLine = array(
		        'timestamp' => date("d/m/Y")." ".date("H:i"),
		        'url' => $this->getCurrentURL(),
			'nom' =>$contact->getLastName(),
		        'prenom' =>$contact->getFirstName(),
		        'email' => $contact->getEmail(),
			'formulaire' =>$this->getFieldValue('formname'),
		        'tel' => $contact->getPhone(),
		        'sujet' => $contact->getSujet()
	       	);
	      	$ret = $ssModel->insertRow($newLine, 'od6', 'tGzzJvn0FdPHR18hOBWUdUg');
	      	$this->log->debug(Zend_Json::encode($newLine));
	      	$this->log->debug('test ');
	      	$content = null;
       } catch (Exception $e) {
       		$this->log->err($e->getMessage());
	 	return false;
	}
        return true;
    }
    private function sendCreateSend(Contact $contact) {
    	try {
    		$listID = '';
    		$apiKey = 'f48be37814dcd065f6a319011fcb6c02';
    		switch ($this->getFieldValue('formname')) {
    			case "contact_pdf": $listID = 'deea7de245997711a8adf0ab7f91cb2a'; break;
    			default: $listID = '8b2520798e41d464c0f52872142aac69'; break;
    		}
    		
    		if ($listID == '') return true;
    		
    		$create = new AddCreatesend($listID,$apiKey);
    		$newLine = array(
    			'name' => $contact->getLastName(). ' ' .$contact->getFirstName(), 
   		        'email' => $contact->getEmail(),
   		        'sujet' => $contact->getSujet()
    		);
    		$ret = $create->sendToCreateSend($newLine);
    		$this->log->debug('CreateSend :' . Zend_Json::encode($newLine) .' --> '.$ret );
    		$content = null;
    		if (!$ret) {
    			$this->log->err('CreateSend :'.$ret);
    		}
    		/* On va renvoyer vrai même s'il y a eu des erreurs 
    		 * cas d'un email déjà dans la liste ...
    		 */ 
    		return true;
    	} catch (Exception $e) {
    		$this->log->err('CreateSend :'.$e->getMessage());
    		return false;
    	}
    	return true;
    }
    

    
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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_azeliz_contacts/pi1/class.user_azelizcontacts_pi1.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/user_azeliz_contacts/pi1/class.user_azelizcontacts_pi1.php']);
}

?>
