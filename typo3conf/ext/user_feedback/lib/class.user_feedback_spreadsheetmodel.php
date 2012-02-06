<?php
/**
 * Classe permettant de se connecter à Google Doc, de lister les SpreadSheets, les worksheets,
 * les lignes d'un worksheet. Vous pouvez ajouter, modifier et supprimer des lignes d'un
 * worksheet.
 * 
 * Cette classe utilise les class Zend_Gdata du framework Zend, vous devez donc installer le
 * framework Zend et paramètrer le serveur web pour qu'il accède au chargeur de class Zend/loader.php
 * 
 * Le Framwork Zend utilise l'API Google 1.0 qui ne sera plus supporter courant 2012
 * 
 * @autor		Marc-Antoine Tréhin
 * @copyright	@zeliz
 * @version 	1.0
 * @date 		9/11/2011
 *
 */

class SpreadSheetModel {

	private $service = null;
	
	/**
	 * Charge les classes du framework et se connecte sur un compte
	 * Google Doc'
	 */
	public function __construct($user, $pass) {
		/* Chargement des classes Zend */
		require_once 'Zend/Loader.php';
		Zend_Loader::loadClass('Zend_Gdata');
		Zend_Loader::loadClass('Zend_Gdata_AuthSub');
		Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
		Zend_Loader::loadClass('Zend_Gdata_Docs');
		Zend_Loader::loadClass('Zend_Gdata_Docs_DocumentListEntry');
		Zend_Loader::loadClass('Zend_Gdata_App_AuthException');
		Zend_Loader::loadClass('Zend_Gdata_App_FeedEntryParent');
		Zend_Loader::loadClass('Zend_Gdata_Spreadsheets');
		
		$this->connecting($user, $pass);
	}
	
	
	/**
	 * Connect l'utilisateur au service
	 * @return true or false
	 */
	private function connecting($user, $pass) {
		$client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, Zend_Gdata_Spreadsheets::AUTH_SERVICE_NAME);
		$this->service = new Zend_Gdata_Spreadsheets($client);
		
		//return true;
	}
	
	/* Ajout, Modification et Suppression de lignes d'un worksheet */
	
	/**
	 * Insertion d'une liste de ligne passé en paramètre dans un worksheet
	 * @param array de valeurs des clefs des colonnes $lignes
	 * @param string $worksheetId Id du worksheet
	 * @param string $spreadsheetId Id du spreadsheet
	 * @return Object SpreadSheet object représentant les lignes ajoutés
	 */
	public function insertRow($line, $worksheetId, $spreadsheetId) {
		return $this->service->insertRow($line, $spreadsheetId, $worksheetId);
	}
	
	/**
	 * Met à jour une liste de ligne du worksheet
	 * @param array de valeur de clefs des colonnes à modifier $oldLines
	 * @param array de valeur de clefs des colonnes à ajouter $newLines
	 * @return Object SpreadSheet object représentant les lignes ajoutés
	 */
	public function updateRow($oldLine, $newLine) {
		return $this->service->updateRow($oldLine, $newLine);
	}
	
	/**
	 * Supprime une liste de ligne du worksheet
	 * @param array de ligne $lines
	 * @return ??
	 */
	public function deleteRow($line) {
		return $this->service->delete($line);
	}
	
	
	/* Get List of SpreadSheets or WorkSheets or Lines */
	
	/**
	 * Retourn une liste de SpreadSheets de l'utilisateur passé en paramètre
	 * @param Object $client
	 * @return Zend__Gdata_Spreadsheets_SpreadsheetFeed $spreadSheetList
	 */
	public function getSpreadSheetsList() {
		return $this->service->getSpreadsheetFeed();
	}

	/**
	 * Retourne une liste des feuilles d'un SpreadSheet suivant son id passé en paramètre
	 * @param String id du spreedsheat
	 * @return la liste des worksheets
	 */
	public function getWorkSheetsListById($ssId) {
		$query = new Zend_Gdata_Spreadsheets_DocumentQuery();
		$query->setSpreadsheetKey($ssId);
		
		return $this->service->getWorksheetFeed($query);
	}
	
	/**
	 * Retourne une liste des feuilles d'un SpreadSheet suivant son titre passé en paramètre
	 * @param String titre du spreedsheat
	 * @return la liste des worksheets
	 */
	public function getWorkSheetsListByTitleValue($title) {
		$query = new Zend_Gdata_Spreadsheets_DocumentQuery();
		$query->setSpreadsheetKey($this->explodeId($this->getSpreadSheetByTitleValue($title)->getId()));
		
		return $this->service->getWorksheetFeed($query);
	}
	
	/**
	 * Retourne une liste des lignes du worksheet
	 */
	public function getLinesList($worksheetId, $spreadSheetId) {
		$query = new Zend_Gdata_Spreadsheets_ListQuery();
		$query->setSpreadsheetKey($spreadSheetId);
		$query->setWorksheetId($worksheetId);
		
		return $this->service->getListFeed($query);
	}	
	
	/**
	 * Retourne le résultat de la requete passé en paramètre sous la forme d'une liste de lignes
	 * @param String $worksheetId Id du worksheet
	 * @param String $spreadsheetId Id du spreadsheet
	 * @param String $strQuery la requête
	 */
	public function getResultQueryList($strQuery, $worksheetId, $spreadSheetId) {
		$query = new Zend_Gdata_Spreadsheets_ListQuery();
		$query->setSpreadsheetKey($spreadSheetId);
		$query->setWorksheetId($worksheetId);
		$query->setSpreadsheetQuery($strQuery);
		
		return $this->service->getListFeed($query);
	}
	
	
	/* Get SpreadSheet or WorkSheet */
	
	/**
	 * Retourne le spreadsheet ayant le même id passé en paramètre
	 * @return Object spreadSheet
	 */
	public function getSpreadSheetById($id) {
		$ss = null;
		foreach ($this->getSpreadSheetsList() as $spreadSheet) {
			if ($spreadSheet->getId() == $id) {
				$ss = $spreadSheet;
			}
		}
		return $ss;
	}

	/**
	 * Retourne le spreadsheet ayant le même titre passé en paramètre
	 * @return Object spreadSheet
	 */
	public function getSpreadSheetByTitleValue($title) {
		$ssRech = null;
		foreach ($this->getSpreadSheetsList() as $ss) {
			if ($ss->getTitleValue() == $title) {
				$ssRech = $ss;
			}
		}
		return $ssRech;
	}
	

	/* Extra function */
	
	/**
	 * Récupère l'id d'un SpreadSheet ou Worksheet dans l'adresse URL
	 * A utiliser sur la chaine retournée par la méthode getId() du framework
	 */
	public static function explodeId($url) {
		$tab = explode("/", $url);
		return $tab[count($tab)-1];
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['/typo3/ext/SpreadSheetModel.php'])    {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['/typo3/ext/SpreadSheetModel.php']);
}
?>
