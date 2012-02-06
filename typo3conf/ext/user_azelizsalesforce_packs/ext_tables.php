<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='';

// Ajout FlexForm
$TCA["tt_content"]["types"]["list"]["subtypes_addlist"][$_EXTKEY."_pi1"]="pi_flexform";

t3lib_extMgm::addPlugin(array(
	'LLL:EXT:user_azelizsalesforce_packs/locallang_db.xml:tt_content.list_type_pi1',
	$_EXTKEY . '_pi1',
	t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'
),'list_type');

// Ajout Zend
if (!class_exists("Zend_Loader_Autoloader")) {
    /* Mise en place de l'autoloader Zend */
    require_once("Zend/Loader/Autoloader.php");
    Zend_Loader_Autoloader::getInstance();
}

// Ajout FLexForm
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:'.$_EXTKEY.'/flexform_ds.xml');
include_once(t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_azelizsalesforce_dynform.php');
?>