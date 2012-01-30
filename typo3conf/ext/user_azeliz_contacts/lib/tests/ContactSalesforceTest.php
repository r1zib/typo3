<?php
require_once ('PHPUnit/Framework/TestCase.php');
require_once ('../ContactSalesforce.php');
require_once ('../Contact.php');

class ContactSalesforceTest extends PHPUnit_Framework_TestCase {
  private $wsdl = './wsdl.xml';
  private $username = 'nke@azeliz.com';
  private $password = 'online1test';
  private $token = '402tg3j6pbHECz6yojaX8C8hX';

  private $send;
  function __construct() {
		parent::__construct();
		require_once 'Zend/Loader/Autoloader.php';
		$autoloader = Zend_Loader_Autoloader::getInstance();
  }
  
  function testCreateContact () {
  	$contact = new Contact();
  	
  	$contact->setEmail("test@test.com");
  	$contact->setFirstName("test FirstName");
  	$contact->setLastName("test LastName");
  	
  	$send = new ContactSalesforce($this->username, $this->password, $this->token, $this->wsdl);
  	
  	$ret = $send->connection();
  	$this->assertTrue($ret);
  	$ret = $send->update($contact);
  	$this->assertTrue($ret);
  	 
  }
  function testCreateContactTask () {
  	$contact = new Contact();
  	 
  	$contact->setEmail("test2@test.com")->setFirstName("test FirstName")->setLastName("test LastName");
  	$contact->setSujet("Super le site")->setTexte("Et quel design !!!!");
  	 
  	$send = new ContactSalesforce($this->username, $this->password, $this->token, $this->wsdl);
  	 
  	$ret = $send->connection();
  	$this->assertTrue($ret);
  	$ret = $send->update($contact);
  	$this->assertTrue($ret);
  
  }
  
  
  	
  }