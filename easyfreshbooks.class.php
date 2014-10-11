<?php
/*******************************************************************************
 *                     Easy Freshbooks API - PHP Class
 *******************************************************************************
 *      Author:     Cesar David Bernal Moreno
 *      Email:      webmaster@misterwebo.com
 *      Website:    http://www.misterwebo.com
 *
 *      File:       freshbooks.class.php
 *      Version:    1.0
 *      Copyright:  (c) 2012 - Cesar David Bernal Moreno
 *******************************************************************************
 

/***************************************/
/*   CHANGE THIS SETTING TO YOUR OWN   */
/***************************************/

define('MW_FB_URL','https://jamiebrwr.freshbooks.com/api/2.1/xml-in'); // YOUR FRESHBOOKS API URL
define('MW_FB_TOKEN','f01cb501294c31aaccc60cc0cb6296d4'); // YOUR FRESHBOOKS TOKEN

/***************************************/
/*   DON NOT CHANGE BELOW THIS POINT   */
/***************************************/






class easyFreshBooksAPI{



	/****************************/
	/*********** VARS ***********/
	/****************************/
	
	private $fburl=MW_FB_URL;
	private $fbtoken=MW_FB_TOKEN;
	public $line;
	public $client;
	public $item;
	public $autobill;
	public $estimate;
	public $expense;
	public $invoice;
	public $contact;
	public $recurring;
	public $error;
	public $lastrequest;
	public $payment;
	public $project;
	public $task;
	public $tax;
	public $timeentry;
	
	public function __construct(){
		$this->line=new stdClass();
		$this->client=new stdClass();
		$this->estimate=new stdClass();
		$this->expense=new stdClass();
		$this->invoice=new stdClass();
		$this->contact=new stdClass();
		$this->item=new stdClass();
		$this->payment=new stdClass();
		$this->project=new stdClass();
		$this->autobill=new stdClass();
		$this->recurring=new stdClass();
		$this->task=new stdClass();
		$this->tax=new stdClass();
		$this->timeentry=new stdClass();
	}

	/****************************************/
	/*********** COMMON FUNCTIONS ***********/
	/****************************************/
		
	function callfb($content){
		$send_curlConn = curl_init($this->fburl);
		curl_setopt($send_curlConn, CURLOPT_HEADER, false);
		curl_setopt($send_curlConn, CURLOPT_NOBODY, false);
		curl_setopt($send_curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($send_curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($send_curlConn, CURLOPT_USERPWD, $this->fbtoken);
		curl_setopt($send_curlConn, CURLOPT_TIMEOUT, 4);
		curl_setopt($send_curlConn, CURLOPT_SSL_VERIFYPEER, FALSE); // Validate SSL certificate
		curl_setopt($send_curlConn, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($send_curlConn, CURLOPT_USERAGENT, "FreshBooks API AJAX tester 1.0");
		curl_setopt($send_curlConn, CURLOPT_POSTFIELDS, $content);
		$result = curl_exec($send_curlConn);
		return $result;
	}
	
	function prepare_xml($method, $tags='', $lines=''){
		$content='<?xml version="1.0" encoding="utf-8"?><request method="'.$method.'">'.PHP_EOL;
		
		$methodcut=explode('.', $method);
		if($methodcut[1]=='create' || $methodcut[1]=='update' || $methodcut[1]=='verify'){
			$content.="<".$methodcut[0].">".PHP_EOL;
		}
		
		if(!empty($tags)){
			$content.=$this->prepare_tags($tags);
		}
		
		if(!empty($lines)){
			$content.=$this->prepare_lines($lines);
		}
		
		if($methodcut[1]=='create' || $methodcut[1]=='update' || $methodcut[1]=='verify'){
			$content.="</".$methodcut[0].">".PHP_EOL;
		}
		
		$content.='</request>';
		$this->lastrequest=$content;
		$result=$this->callfb($content);
		return $result;
	}
	
	function prepare_tags($tags){
		$prep="";
		foreach($tags as $k=>$v){
			if($v!='' || $k=='autobill'){
				if($k=='contacts' || $k=='tasks' || $k=='expiration' || $k=='card' || $k=='autobill'){
					$prep.="<".$k.">".$v."</".$k.">".PHP_EOL;
				}else{
					$prep.="<".$k.">".$this->xmlentities($v)."</".$k.">".PHP_EOL;
				}
			}elseif($k=='inventory'){
				$prep.="<".$k.">".$this->xmlentities($v)."</".$k.">".PHP_EOL;
			}
		}
		return $prep;
	}

	function prepare_lines($lines){
		$list='';
		if($lines){
		$list.='<lines>'.PHP_EOL;
			foreach($lines as $line){
				$list.='<line>';
				$list.=$this->prepare_tags($line);
				$list.='</line>'.PHP_EOL;
			}
		$list.='</lines>'.PHP_EOL;
		}
		return $list;
	}

	function xmlentities($string) {
		return str_replace(array("&", "<", ">", "\"", "'"),
			array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;"), $string);
	}
	
	function xml_obj($xml,$return=''){
		$xmlObj = simplexml_load_string($xml);
		$json = json_encode($xmlObj);
		$obj= json_decode($json);
		if($return==''){
			if($obj->{'@attributes'}->status=='ok'){ return true; }else{ $this->error=$obj->error; return false; }
		}elseif($return=='full'){
			if($obj->{'@attributes'}->status=='ok'){ return $obj; }else{ $this->error=$obj->error; return false; }
		}else{
			if($obj->{'@attributes'}->status=='ok'){ return $obj->$return; }else{ $this->error=$obj->error; return false; }
		}
	}
	
	function addContact(){
		if(isset($this->contact->email)){
			$this->contacts[]=$this->contact;
			$this->contact=new stdClass();
			return true;
		}else{
			return false;
		}
	}
	
	function addLine(){
		if($this->line){
			$this->lines[]=$this->line;
			$this->line=new stdClass();
			return true;
		}else{
			return false;
		}
	}
	
	
	
	/******************************************/
	/*********** CALLBACK FUNCTIONS ***********/
	/******************************************/
	
	// Create a new callback for a specific event or a set of events.
	// $uri Must be a valid HTTP URI
	function callbackCreate($event, $uri){
		$method='callback.create';
		$tags=array(
			'event'=>$event,
			'uri'=>$uri
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'callback_id');
		return $obj;
	}
	
	// Verify a callback using a unique verification code that was sent when the callback was first created.
	function callbackVerify($callback_id, $verifier){
		$method='callback.verify';
		$tags=array(
			'callback_id'=>$callback_id,
			'verifier'=>$verifier
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Resend a verification code to an unverified callback. Note that no token will be sent if the callback is already verified.
	function callbackResendToken($callback_id){
		$method='callback.resendToken';
		$tags=array(
			'callback_id'=>$callback_id,
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Return a list of registered callbacks. You can optionally filter by event or uri. This method uses pagination (use $page)
	function callbackList($filters='', $page=1){
		$method='callback.list';
		$tags=array(
			'page'=>$page
		);
		if($filters){
			foreach($filters as $k=>$v){
				$tags[$k]=$v;
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	//Delete an existing callback.
	function callbackDelete($callback_id){
		$method='callback.delete';
		$tags=array(
			'callback_id'=>$callback_id,
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	
	
	/********************************************/
	/*********** CATEGORIES FUNCTIONS ***********/
	/********************************************/
	
	// Create a new category. If successful, returns the category_id of the newly created item. 
	function categoryCreate($name){
		$method='category.create';
		$tags=array(
			'name'=>$name,
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'category_id');
		return $obj;
	}
	
	// Update an existing expense category with the given category_id. Any category fields left out of the request will remain unchanged. $name is optional
	function categoryUpdate($category_id, $name=''){
		$method='category.update';
		$tags=array(
			'category_id'=>$category_id,
			'name'=>$name
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Return the complete category details associated with the given category_id. 
	function categoryGet($category_id){
		$method='category.get';
		$tags=array(
			'category_id'=>$category_id,
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Delete an existing expense category. 
	function categoryDelete($category_id){
		$method='category.delete';
		$tags=array(
			'category_id'=>$category_id,
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Returns a list of expense categories. This method uses pagination.
	function categoryList($page=1){
		$method='category.list';
		$tags=array(
			'page'=>$page
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response, true);
		return $obj;
	}
	
	
	
	/****************************************/
	/*********** CLIENT FUNCTIONS ***********/
	/****************************************/
	
	// Search clients by email and retrieves array with client_id's. It only retrieves first 25 results.
	function clientIdByEmail($email){
		$filters['email']=$email;
		$clients=$this->clientList($filters);
		if(isset($clients->clients->client)){
			if(is_array($clients->clients->client)){
				foreach($clients->clients->client as $client){
					$client_ids[]=$client->client_id;
				}
			}else{
				$client_ids=$clients->clients->client->client_id;
			}
			return $client_ids;
		}else{
			return false;
		}
	}
	
	// Create a new client and return the corresponding client_id. If a password is not supplied, one will be created at random. 
	function clientCreate(){
		$method='client.create';
		$tags=$this->client;
		$this->client=new stdClass();
		if(isset($this->contacts)){
			foreach($this->contacts as $contact){
				$result=$this->prepare_tags($contact);
				$contacts.='<contact>'.$result.'</contact>'.PHP_EOL;
			}
			$tags->contacts=$contacts;
			$this->contacts=array();
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response, 'client_id');
		return $obj;
	}
	
	// Update the details of the client with the given client_id. Any fields not referenced in the request will remain unchanged. 
	function clientUpdate($client_id){
		$method='client.update';
		$tags=$this->client;
		$tags->client_id=$client_id;
		$this->client=new stdClass();
		if(isset($this->contacts)){
			foreach($this->contacts as $contact){
				$result=$this->prepare_tags($contact);
				$contacts.='<contact>'.$result.'</contact>'.PHP_EOL;
			}
			$tags->contacts=$contacts;
			$this->contacts=array();
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Return the client details associated with the given client_id. Note: The credit element is deprecated and will only represent credit in the systems base currency.
	// A new element, called credits has been added with child elements for each currency that the client has credit in.
	function clientGet($client_id){
		$method='client.get';
		$tags=array(
			'client_id'=>$client_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response, 'full');
		return $obj;
	}
	
	// Delete the client with the given client_id. 
	function clientDelete($client_id){
		$method='client.delete';
		$tags=array(
			'client_id'=>$client_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Returns a list of client summaries in order of descending client_id. This method uses pagination.
	// Filters: email, username, updated_from, updated_to, per_page, folder, notes
	function clientList($filters='', $page=1){
		$method='client.list';
		$tags=array(
			'page'=>$page,
		);
		if($filters){
			foreach($filters as $k=>$v){
				$tags[$k]=$v;
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Retrieves the client ID if client exits. Instead it retrieves 'false'.
	function checkIfClientExistsByEmail($email, $folder='active'){
		$method='client.list';
		$client=array(
			'email'=>$email,
			'folder'=>$folder
		);
		$response=$this->prepare_xml($method,$client);
		$obj=$this->xml_obj($response, true);
		
		if($obj->clients->client->client_id){
			return (int) $obj->clients->client->client_id;
		}else{
			if($obj->clients->client[0]->client_id){
				return (int) $obj->clients->client[0]->client_id;
			}else{
				return false;
			}
		}
	}
	
	
	
	/*******************************************/
	/*********** ESTIMATES FUNCTIONS ***********/
	/*******************************************/
	
	// Create a new estimate and return the corresponding estimate_id.
	function estimateCreate(){
		$method='estimate.create';
		$tags=$this->estimate;
		$this->estimate=new stdClass();
		if(isset($this->contacts)){
			foreach($this->contacts as $contact){
				$result=$this->prepare_tags($contact);
				$contacts.='<contact>'.$result.'</contact>'.PHP_EOL;
			}
			$tags->contacts=$contacts;
			$this->contacts=array();
		}
		$response=$this->prepare_xml($method,$tags,$this->lines);
		$this->lines=array();
		$obj=$this->xml_obj($response, 'estimate_id');
		return $obj;
	}
	
	// Update an existing estimate. 
	function estimateUpdate($estimate_id){
		$method='estimate.update';
		$tags=$this->estimate;
		$this->estimate=new stdClass();
		$tags->estimate_id=$estimate_id;
		if(isset($this->contacts)){
			foreach($this->contacts as $contact){
				$result=$this->prepare_tags($contact);
				$contacts.='<contact>'.$result.'</contact>'.PHP_EOL;
			}
			$tags->contacts=$contacts;
			$this->contacts=array();
		}
		$response=$this->prepare_xml($method,$tags,$this->lines);
		$this->lines=array();
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Retrieve an existing estimate. 
	function estimateGet($estimate_id){
		$method='estimate.get';
		$tags=array(
			'estimate_id'=>$estimate_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Delete an existing estimate. 
	function estimateDelete($estimate_id){
		$method='estimate.delete';
		$tags=array(
			'estimate_id'=>$estimate_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Returns a list of estimates. This method uses pagination.
	// Filters: client_id, folder, date_from, date_to
	function estimateList($filters='', $page=1){
		$method='estimate.list';
		$tags=array(
			'page'=>$page,
		);
		if($filters){
			foreach($filters as $k=>$v){
				$tags[$k]=$v;
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Send an estimate to the associated client via e-mail.
	// If you would like to send a custom email, include a message element. If a subject element is not included, the default subject line will be used. 
	function estimateSendByEmail($estimate_id, $subject='', $message=''){
		$method='estimate.sendByEmail';
		$tags=array(
			'estimate_id'=>$estimate_id,
			'subject'=>$subject,
			'message'=>$message
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	
	
	/******************************************/
	/*********** EXPENSES FUNCTIONS ***********/
	/******************************************/
	
	// Create a new expense specifically for a client, and optionally one of their projects, or keep it generalized for a number of clients. If successful, returns the expense_id of the newly created item.
	// staff_id is a required field only for admin users. It is ignored for staff using the API.
	function expenseCreate($staff_id){
		$method='expense.create';
		$tags=$this->expense;
		$tags->staff_is=$staff_id;
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response, 'expense_id');
		$this->expense=new stdClass();
		return $obj;
	}
	
	// Update an existing expense with the given expense_id. Any expense fields left out of the request will remain unchanged. 
	function expenseUpdate($expense_id){
		$method='expense.update';
		$tags=$this->expense;
		$tags->expense_id=$expense_id;
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		$this->expense=new stdClass();
		return $obj;
	}
	
	// Return the complete expense details associated with the given expense_id. 
	function expenseGet($expense_id){
		$method='expense.get';
		$tags=array(
			'expense_id'=>$expense_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Delete an existing expense. 
	function expenseDelete($expense_id){
		$method='expense.delete';
		$tags=array(
			'expense_id'=>$expense_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Returns a list of expense summaries. This method uses pagination.
	// Filters: client_id, category_id, project_id, date_from, date_to or vendor.
	function expenseList($filters='', $page=1){
		$method='expense.list';
		$tags=array(
			'page'=>$page,
		);
		if($filters){
			foreach($filters as $k=>$v){
				$tags[$k]=$v;
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	
	
	/*****************************************/
	/*********** GATEWAY FUNCTIONS ***********/
	/*****************************************/
	
	// Returns a list of payment gateways enabled in your FreshBooks account that can process credit card transactions. This method uses pagination
	// Filters: autobill_capable
	function gatewayList($filters='', $page=1){
		$method='gateway.list';
		$tags=array(
			'page'=>$page,
		);
		if($filters){
			foreach($filters as $k=>$v){
				$tags[$k]=$v;
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	
	
	/*****************************************/
	/*********** INVOICE FUNCTIONS ***********/
	/*****************************************/
	
	// Create a new invoice complete with line items. If successful, returns the invoice_id of the newly created invoice.
	// If you don't specify an invoice <number>, it will increment from the last one.
	// You may optionally specify a different address on the invoice; otherwise the address will be pulled from your client's details.
	// You may optionally specify a <return_uri> element. If provided, users will be presented with a link to the URI when they pay the invoice.
	function invoiceCreate(){
		$method='invoice.create';		
		$tags=$this->invoice;
		if(isset($this->contacts)){
			foreach($this->contacts as $contact){
				$result=$this->prepare_tags($contact);
				$contacts.='<contact>'.$result.'</contact>'.PHP_EOL;
			}
			$tags->contacts=$contacts;
			$this->contacts=array();
		}
		$response=$this->prepare_xml($method,$tags,$this->lines);
		$obj=$this->xml_obj($response, 'invoice_id');
		$this->invoice=new stdClass();
		$this->lines=array();
		return $obj;
	}
	
	// Update an existing invoice with the given invoice_id. Any invoice fields left out of the request will remain unchanged.
	// If you do not specify a <lines> element, the existing lines will remain unchanged. If you do specify <lines> elements the original ones will be replaced by the new ones.
	function invoiceUpdate($invoice_id){
		$method='invoice.update';
		$tags=$this->invoice;
		$tags->invoice_id=$invoice_id;
		if(isset($this->contacts)){
			foreach($this->contacts as $contact){
				$result=$this->prepare_tags($contact);
				$contacts.='<contact>'.$result.'</contact>'.PHP_EOL;
			}
			$tags->contacts=$contacts;
			$this->contacts=array();
		}
		$response=$this->prepare_xml($method,$tags,$this->lines);
		$obj=$this->xml_obj($response);
		$this->invoice=new stdClass();
		$this->lines=array();
		return $obj;
	}
	
	// Return the complete invoice details associated with the given invoice_id.
	// You can use the <links> element to provide your customers with direct links to the invoice for editing, viewing by the client and viewing by an administrator.
	function invoiceGet($invoice_id){
		$method='invoice.get';
		$tags=array(
			'invoice_id'=>$invoice_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Delete an existing invoice.
	function invoiceDelete($invoice_id){
		$method='invoice.delete';
		$tags=array(
			'invoice_id'=>$invoice_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Returns a list of invoice summaries. Results are ordered by descending invoice_id. This method uses pagination.
	// Filters: client_id, recurring_id, status, number, date_from, date_to, updated_from, updated_to, folder
	function invoiceList($filters='', $page=1){
		$method='invoice.list';
		$tags=array(
			'page'=>$page,
		);
		if($filters){
			foreach($filters as $k=>$v){
				$tags[$k]=$v;
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Get invoice by invoice number and returns the invoice_id.
	function invoiceID($invoice_number){
		$method='invoice.list';
		$tags['number']=$invoice_number;
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		if(isset($obj->invoices->invoice->invoice_id)){
			return $obj->invoices->invoice->invoice_id;
		}else{
			return false;
		}
	}
	
	// Send an existing invoice to your client via e-mail. The invoice status will be changed to sent.
	// If you would like to send a custom email, include a message and subject element. Note that both of these elements are required (one or the other will not work).
	function invoiceSendByEmail($invoice_id, $subject='', $message=''){
		$method='invoice.sendByEmail';
		$tags=array(
			'invoice_id'=>$invoice_id,
			'subject'=>$subject,
			'message'=>$message
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Send an existing invoice to your client via snail mail. If you do not have enough stamps, the request will fail. If successful, the invoice status will be changed to sent.
	// Be careful with this method. This operation cannot be undone.
	function invoicesendBySnailMail($invoice_id){
		$method='invoice.sendBySnailMail';
		$tags=array(
			'invoice_id'=>$invoice_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Adds (a) new line(s) to an existing invoice. One or more lines may be added.
	// Do not specify line_id for any lines. (Ids will be assigned automatically). Use invoiceLinesUpdate to change existing lines.
	function invoiceLinesAdd($invoice_id){
		$method='invoice.lines.add';
		$tags=array(
			'invoice_id'=>$invoice_id
		);
		$response=$this->prepare_xml($method,$tags,$this->lines);
		$obj=$this->xml_obj($response, 'full');
		$this->lines=array();
		return $obj;
	}
	
	// Deletes a single line from an existing invoice. Only a single line can be deleted per request.
	function invoiceLinesDelete($invoice_id, $line_id){
		$method='invoice.lines.delete';
		$tags=array(
			'invoice_id'=>$invoice_id,
			'line_id'=>$line_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Updates (an) existing line(s) on an existing invoice. One or more lines may be updated. Only the provided fields will be updated. All others will be left unchanged.
	// line_id is mandatory for each line to be updated. Use invoiceLinesAdd to create new lines.
	function invoiceLinesUpdate($invoice_id){
		$method='invoice.lines.update';
		$tags=array(
			'invoice_id'=>$invoice_id
		);
		$response=$this->prepare_xml($method,$tags,$this->lines);
		$obj=$this->xml_obj($response);
		$this->lines=array();
		return $obj;
	}
	
	
	
	/**************************************/
	/*********** ITEM FUNCTIONS ***********/
	/**************************************/
	
	// Create a new item and return the corresponding item_id. 
	function itemCreate(){
		$method='item.create';
		if($this->item){
			$tags=$this->item;
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response, 'item_id');
		$this->item=new stdClass();
		return $obj;
	}
	
	// Update an existing item. All fields aside from the item_id are optional; by omitting a field, the existing value will remain unchanged. 
	function itemUpdate($item_id){
		$method='item.update';
		if($this->item){
			$tags=$this->item;
		}
		$tags->item_id=$item_id;
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		$this->item=new stdClass();
		return $obj;
	}
	
	// Get an existing item with the given item_id. 
	function itemGet($item_id){
		$method='item.get';
		$tags=array(
			'item_id'=>$item_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Delete an existing item.
	function itemDelete($item_id){
		$method='item.delete';
		$tags=array(
			'item_id'=>$item_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Returns a list of items, ordered by descending item_id.  This method uses pagination.
	// Filters: folder
	function itemList($filters='', $page=1){
		$method='item.list';
		$tags=array(
			'page'=>$page,
		);
		if($filters){
			foreach($filters as $k=>$v){
				$tags[$k]=$v;
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	
	
	/*******************************************/
	/*********** LANGUAGES FUNCTIONS ***********/
	/*******************************************/
	
	// Returns a list of language names and the corresponding codes that you can use for clients, invoices and estimates.
	// The codes are from IETF RFC 5646, which is usually the two-letter ISO-639-1 code. 
	// This method uses pagination
	function languageList($page=1){
		$method='language.list';
		$tags=array(
			'page'=>$page,
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	
	
	/******************************************/
	/*********** PAYMENTS FUNCTIONS ***********/
	/******************************************/
	
	/*  Create a new payment and returns the corresponding payment_id.
		This function can have one of three possible effects depending on the presence of invoice_id and client_id:
			- If you specify an invoice_id only, the payment will be recorded as an invoice payment.
			- If you specify a client_id only, the payment will be recorded as a client credit.
			- If you specify both an invoice_id and client_id, the payment will be recorded as an invoice payment, and the amount will be subtracted from the client's credit.
		Payment type must be one of: ‘Check’, ‘Credit’, ‘Credit Card’, ‘Bank Transfer’, ‘Debit’, ‘PayPal’, ’2Checkout’, ‘VISA’, ‘MASTERCARD’, ‘DISCOVER’, ‘NOVA’, ‘AMEX’, ‘DINERS’, ‘EUROCARD’, ‘JCB’ or ‘ACH’.
		Note that ‘currency_code’ can only be provided when creating a credit, not a regular payment. Regular payments will default to the currency code of the invoice they are being made against. 
	*/
	function paymentCreate(){
		$method='payment.create';
		if($this->payment){
			$tags=$this->payment;
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response, 'payment_id');
		$this->payment=new stdClass();
		return $obj;
	}
	
	// Update an existing payment. All fields besides payment_id are optional - unpassed fields will retain their existing value.
	// Note that 'currency_code' can only be provided when updating a credit, not a regular payment. Regular payments will default to the currency code of the invoice they are being made against. 
	function paymentUpdate($payment_id){
		$method='payment.update';
		if($this->payment){
			$tags=$this->payment;
		}
		$tags->payment_id=$payment_id;
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		$this->payment=new stdClass();
		return $obj;
	}
	
	// Retrieve payment details according to payment_id. 
	function paymentGet($payment_id){
		$method='payment.get';
		$tags=array(
			'payment_id'=>$payment_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Permanently delete a payment. This will modify the status of the associated invoice if required.
	function paymentDelete($payment_id){
		$method='payment.delete';
		$tags=array(
			'payment_id'=>$payment_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Returns a list of recorded payments. You can optionally filter by invoice_id or client_id.   This method uses pagination.
	// Filters: client_id, invoice_id, date_from, date_to, updated_from, updated_to
	function paymentList($filters='', $page=1){
		$method='payment.list';
		$tags=array(
			'page'=>$page,
		);
		if($filters){
			foreach($filters as $k=>$v){
				$tags[$k]=$v;
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}

	
	
	/*****************************************/
	/*********** PROJECT FUNCTIONS ***********/
	/*****************************************/
	
	// Add task for to use on projectCreate and projectUpdate
	function addTask($task_id, $rate=''){
		$tags=array(
			'task_id'=>$task_id,
			'rate'=>$rate
		);
		$this->tasks[]=$this->prepare_tags($tags);
	}
	
	// Create a new project. If you specify project-rate or flat-rate for bill_method, you must supply a rate. 
	// Billing Method Types: task-rate, flat-rate, project-rate, staff-rate
	function projectCreate(){
		$method='project.create';
		if($this->project){
			$tags=$this->project;
		}
		if(isset($this->tasks)){
			if(is_array($this->tasks)){
				$tasks='';
				foreach($this->tasks as $task){
					$tasks.='<task>'.$task.'</task>'.PHP_EOL;
				}
				$tags->tasks=$tasks;
				$this->tasks=array();	
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response, 'project_id');
		$this->project=new stdClass();
		return $obj;
	}
	
	// Update an existing project. 
	function projectUpdate($project_id){
		$method='project.update';
		if($this->project){
			$tags=$this->project;
		}
		if(isset($this->tasks)){
			if(is_array($this->tasks)){
				$tasks='';
				foreach($this->tasks as $task){
					$tasks.='<task>'.$task.'</task>';
				}
				$tags->tasks=$tasks;
				$this->tasks=array();	
			}
		}
		$tags->project_id=$project_id;
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		$this->project=new stdClass();
		return $obj;
	}
	
	// Retrieve an existing project. 
	// Staff IDs for staff members who are assigned to a project will only appear for admins and project managers.
	function projectGet($project_id){
		$method='project.get';
		$tags=array(
			'project_id'=>$project_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Delete an existing project. 
	function projectDelete($project_id){
		$method='project.delete';
		$tags=array(
			'project_id'=>$project_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Returns a list of projects in alphabetical order. This method uses pagination.
	// Filters: client_id, task_id
	// Staff IDs for staff members who are assigned to a project will only appear for admins and project managers.
	function projectList($filters='', $page=1){
		$method='project.list';
		$tags=array(
			'page'=>$page,
		);
		if($filters){
			foreach($filters as $k=>$v){
				$tags[$k]=$v;
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	
	
	/*******************************************/
	/*********** RECURRING FUNCTIONS ***********/
	/*******************************************/
	
	/* SET THE AUTOBILL
	$this->autobill->gateway='';
	$this->autobill->card_name='';
	$this->autobill->card_number='';
	$this->autobill->card_month='';
	$this->autobill->card_year='';
	*/
	function autobill(){
		if( isset($this->autobill->gateway) &&
			isset($this->autobill->card_name) &&
			isset($this->autobill->card_number) &&
			isset($this->autobill->card_month) &&
			isset($this->autobill->card_year)){
				if($this->autobill->gateway==''){
					$this->autobill=new stdClass();
					return '';
				}else{
				/*
				$the_tag='<gateway_name>'.$this->xmlentities($this->autobill->gateway).'</gateway_name>'.PHP_EOL;
				$the_tag.='<card>'.PHP_EOL;
				
					<number></number>
					<name></name>
					<expiration>
						<month></month>
						<year></year>
					</expiration>
				$the_tag.='<card>'.PHP_EOL;
				*/
					$expiration=array(
						'month'=>$this->autobill->card_month,
						'year'=>$this->autobill->card_year
					);
					$card=array(
						'number'=>$this->autobill->card_number,
						'name'=>$this->autobill->card_name,
						'expiration'=>$this->prepare_tags($expiration)
					);
					$tags=array(
						'gateway_name'=>$this->autobill->gateway,
						'card'=>$this->prepare_tags($card)
					);
					$this->autobill=new stdClass();
					return $this->prepare_tags($tags);
				}
		}else{
			$this->autobill=new stdClass();
			return false;
		}
	}
	
	/* Create a new recurring profile. The method arguments are nearly identical to invoiceCreate, but include five additional fields:
		- occurrences
			Number of invoices to generate, with zero (0) being infinite
		- frequency
			Rate at which to generate invoices – can be one of ‘weekly’, ’2 weeks’, ’4 weeks’, ‘monthly’, ’2 months’, ’3 months’, ’6 months’, ‘yearly’, ’2 years’ 
		- stopped
			This profile is no longer generating invoices (1 – stopped, 0 – active) 
		- send_email
			Notify client by email each time a new invoice is generated (1 or 0)
		- send_snail_mail
			Send a copy of your invoice by snail mail, each time it’s generated (1 or 0) 
		- autobill
			Enable credit card auto-billing for this recurring profile.
			
		New profiles that start today will be sent immediately.
		The method supports two placeholders in return_uri:
		Placeholder Replaced With
			::invoice id:: The generated invoice ID (used for invoice.get)
			::invoice number:: The generated invoice number (used in the user interface)
		These placeholders are case-sensitive. IE: http://invoices.example.com/invoices/::invoice id::/paid
	*/
	function recurringCreate(){
		$method='recurring.create';
		$tags=$this->recurring;
		if(isset($this->contacts)){
			foreach($this->contacts as $contact){
				$result=$this->prepare_tags($contact);
				$contacts.='<contact>'.$result.'</contact>'.PHP_EOL;
			}
			$tags->contacts=$contacts;
			$this->contacts=array();
		}
		if($autobill=$this->autobill()){
			$tags->autobill=$autobill;
		}
		$response=$this->prepare_xml($method,$tags,$this->lines);
		$obj=$this->xml_obj($response, 'recurring_id');
		$this->recurring=new stdClass();
		$this->lines=array();
		return $obj;
	}
	
	// Update an existing recurring profile. For all elements but autobill, if they are supplied, they will be changed. The autobill element is optional.
	// If it is not passed as part of the request then the recurring.update method will not modify any auto-bill information.
	// The recurring.update method can convert a non auto-bill enabled recurring profile into an auto-bill enabled recurring profile with the inclusion of the autobill element.
	// When updating auto-bill information, all child elements are required. An empty autobill element will turn off auto-billing on a recurring profile and delete all related information. 
	function recurringUpdate($recurring_id){
		$method='recurring.update';
		$tags=$this->recurring;
		if(isset($this->contacts)){
			foreach($this->contacts as $contact){
				$result=$this->prepare_tags($contact);
				$contacts.='<contact>'.$result.'</contact>'.PHP_EOL;
			}
			$tags->contacts=$contacts;
			$this->contacts=array();
		}
		$tags->recurring_id=$recurring_id;
		$tags->autobill=$this->autobill();
		$response=$this->prepare_xml($method,$tags,$this->lines);
		$obj=$this->xml_obj($response);
		$this->recurring=new stdClass();
		$this->lines=array();
		return $obj;
	}
	
	// Return the details of an existing recurring profile, including auto-bill information if this recurring profile has auto-billing enabled. 
	function recurringGet($recurring_id){
		$method='recurring.get';
		$tags=array(
			'recurring_id'=>$recurring_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Delete a recurring profile. Once deleted, it will no longer generate invoices. 
	function recurringDelete($recurring_id){
		$method='recurring.delete';
		$tags=array(
			'recurring_id'=>$recurring_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Returns a list of recurring profile summaries. Results are ordered by descending recurring_id.  This method uses pagination.
	// Note: A list request that returns no results (i.e. page 999), will return an empty result set, not an error.
	// Note: The response will include an empty autobill tag if the recurring profile does not have auto-billing enabled, otherwise the response will include an autobill element with the gateway name and card element. 
	// Filters: client_id, autobill, date_from, date_to, updated_from, updated_to, folder
	function recurringList($filters='', $page=1){
		$method='recurring.list';
		$tags=array(
			'page'=>$page,
		);
		if($filters){
			foreach($filters as $k=>$v){
				$tags[$k]=$v;
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Adds (a) new line(s) to an existing recurring profile.
	// Do not specify line_id for any lines. (Ids will be assigned automatically). Use recurringLinesUpdate to change existing lines.
	function recurringLinesAdd($recurring_id){
		$method='recurring.lines.add';
		$tags=array(
			'recurring_id'=>$recurring_id
		);
		$response=$this->prepare_xml($method,$tags,$this->lines);
		$obj=$this->xml_obj($response, 'full');
		$this->lines=array();
		return $obj;
	}
	
	// Deletes a single line from an existing recurring profile. Only a single line can be deleted per request.
	function recurringLinesDelete($recurring_id, $line_id){
		$method='recurring.lines.delete';
		$tags=array(
			'recurring_id'=>$recurring_id,
			'line_id'=>$line_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Updates (an) existing line(s) on an existing recurring profile. One or more lines may be updated. Only the provided fields will be updated. All others will be left unchanged.
	// line_id is mandatory for each line to be updated. Use recurringLinesAdd to create new lines.
	function recurringLinesUpdate($recurring_id){
		$method='recurring.lines.update';
		$tags=array(
			'recurring_id'=>$recurring_id
		);
		$response=$this->prepare_xml($method,$tags,$this->lines);
		$obj=$this->xml_obj($response);
		$this->lines=array();
		return $obj;
	}
	
	
	
	/***************************************/
	/*********** STAFF FUNCTIONS ***********/
	/***************************************/
	
	// Return the current user details. 
	function staffCurrent(){
		$method='staff.current';
		$response=$this->prepare_xml($method);
		$obj=$this->xml_obj($response, 'full');
		return $obj;
	}
	
	// Return the complete staff details associated with the given staff_id. 
	function staffGet($staff_id){
		$method='staff.get';
		$tags=array(
			'staff_id'=>$staff_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response, 'full');
		return $obj;
	}
	
	// Returns a list of staff. This method uses pagination.
	function staffList($page=1){
		$method='staff.list';
		$tags=array(
			'page'=>$page,
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	
	
	/****************************************/
	/*********** SYSTEM FUNCTIONS ***********/
	/****************************************/
	
	// Returns the current system's information. This API method is in a beta phase and is subject to change. 
	function systemCurrent(){
		$method='system.current';
		$response=$this->prepare_xml($method);
		$obj=$this->xml_obj($response, 'full');
		return $obj;
	}
	
	
	
	/***************************************/
	/*********** TASKS FUNCTIONS ***********/
	/***************************************/
	
	// Create a new task. 
	function taskCreate(){
		$method='task.create';
		$tags=$this->task;
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response, 'task_id');
		$this->task=new stdClass();
		return $obj;
	}
	
	// Update an existing expense with the given expense_id. Any expense fields left out of the request will remain unchanged. 
	function taskUpdate($task_id){
		$method='task.update';
		$tags=$this->task;
		$tags->task_id=$task_id;
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		$this->task=new stdClass();
		return $obj;
	}
	
	// Retrieve an existing task.
	function taskGet($task_id){
		$method='task.get';
		$tags=array(
			'task_id'=>$task_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Delete an existing task. 
	function taskDelete($task_id){
		$method='task.delete';
		$tags=array(
			'task_id'=>$task_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Returns a list of tasks in alphabetical order.  This method uses pagination.
	// Filters: project_id - returns only tasks associated with a given project.
	function taskList($filters='', $page=1){
		$method='task.list';
		$tags=array(
			'page'=>$page,
		);
		if($filters){
			foreach($filters as $k=>$v){
				$tags[$k]=$v;
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	
	
	/***************************************/
	/*********** TAXES FUNCTIONS ***********/
	/***************************************/
	
	// Create a new tax and return the corresponding tax_id.
	// *Tax name must be unique. You may not create more than one tax with the same name.
	function taxCreate(){
		$method='tax.create';
		$tags=$this->tax;
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response, 'tax_id');
		$this->tax=new stdClass();
		return $obj;
	}
	
	// Update an existing tax. All fields aside from the tax_id are optional; by omitting a field, the existing value will remain unchanged.
	function taxUpdate($tax_id){
		$method='tax.update';
		$tags=$this->tax;
		$tags->tax_id=$tax_id;
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		$this->tax=new stdClass();
		return $obj;
	}
	
	// Return the complete details for a given tax_id.
	function taxGet($tax_id){
		$method='tax.get';
		$tags=array(
			'tax_id'=>$tax_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Delete an existing tax.
	function taxDelete($tax_id){
		$method='tax.delete';
		$tags=array(
			'tax_id'=>$tax_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Returns a list of taxs, ordered by descending tax_id.  This method uses pagination.
	// Filters: Use a “compound” tag to return only compound or non-compound taxes.
	function taxList($filters='', $page=1){
		$method='tax.list';
		$tags=array(
			'page'=>$page,
		);
		if($filters){
			foreach($filters as $k=>$v){
				$tags[$k]=$v;
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	
	
	/**********************************************/
	/*********** TIME ENTRIES FUNCTIONS ***********/
	/**********************************************/
	
	// Create a new timesheet entry.
	// If you don’t specify a staff_id, it will default to using staff.current‘s staff_id. Note: You cannot assign staff to time entries of projects to which they aren’t assigned.
	function timeentryCreate(){
		$method='time_entry.create';
		$tags=$this->timeentry;
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response, 'time_entry_id');
		$this->timeentry=new stdClass();
		return $obj;
	}
	
	// Update an existing tax. All fields aside from the tax_id are optional; by omitting a field, the existing value will remain unchanged.
	function timeentryUpdate($time_entry_id){
		$method='time_entry.update';
		$tags=$this->timeentry;
		$tags->time_entry_id=$time_entry_id;
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		$this->timeentry=new stdClass();
		return $obj;
	}
	
	// Retrieve a single time_entry record.
	function timeentryGet($timeentry_id){
		$method='time_entry.get';
		$tags=array(
			'time_entry_id'=>$timeentry_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	// Delete an existing time_entry. This action is not recoverable.
	function timeentryDelete($timeentry_id){
		$method='time_entry.delete';
		$tags=array(
			'time_entry_id'=>$timeentry_id
		);
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response);
		return $obj;
	}
	
	// Returns a list of timesheet entries ordered according to date.  This method uses pagination.
	// Filters: project_id, task_id, date_from, date_to
	function timeentryList($filters='', $page=1){
		$method='time_entry.list';
		$tags=array(
			'page'=>$page,
		);
		if($filters){
			foreach($filters as $k=>$v){
				$tags[$k]=$v;
			}
		}
		$response=$this->prepare_xml($method,$tags);
		$obj=$this->xml_obj($response,'full');
		return $obj;
	}
	
	
}