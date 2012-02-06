<?php
    class tx_azelizsalesforce_dynform {
        
        var $baseURL ="http://apps.nke-marine-electronics.fr/typo3/";
        
        public function getPacks($config) {
        	$packs = $this->getDataJson("opportunities");
        	$tmp = array();
        	foreach ($packs as $pack) {
        		if (array_key_exists("Code__c", $pack) && array_key_exists("Name", $pack)) {
        			if ($pack["Code__c"] != '') {
        				$option = "[".$pack["Code__c"]."] ".$pack["Name"];
        				array_push($tmp, array(0 => $option, 1 => $pack["Code__c"]));
        			}
        		}
        	}
        	sort($tmp);
        	$config['items'] = $tmp;
        	return $config;
        }
        
    	/**
     	* Retourne le Json d'une Url sous forme d'un tableau ou d'object
     	* @param string sous repertoire formant l'url'
     	* @param Zend_Json::TYPE Type de l'object returné (Array ou Object)'
     	* @return array or object
     	*/
    	private function getDataJson($subUrl, $options = Zend_Json::TYPE_ARRAY) {
        	$dataJson = file_get_contents($this->baseURL.$subUrl);
        	$data = Zend_Json::decode($dataJson, $options);
        	return $data;
    	}
}
?>