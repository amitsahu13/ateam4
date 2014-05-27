<?php 
/*
 * Paypal Controller is used   to   TRansfer  data 
 * and check   Tranzactions   
 * 2014 
 * 
 */

class PaypalController extends AppController {
	 
	var	$name	=	'Paypal'; 
	var $usermodel  =  null ; 
	
	
	public function beforeFilter(){ 
		
		parent::beforeFilter(); 
		$this->loadModel("User") ;
		$this->autoLayout = false ; 
		$this->autoRender =false ; 
		$this->usermodel = new User() ; 
		
	}
	
	
	// This method will handle responses From PAypal 
	public function paypalResponder(){
		
		
		
		
	}
	
	
 
	public function checkAuthPayment(){
		$p = $this->usermodel->query("SELECT COUNT(*) as c FROM auth_payed WHERE user_id='{$this->Auth->user("id")}' ");
		if ($p[0][0]["c"]  > 0  ) 
			echo "true" ;
		else 
			echo  "false"; 
	} 
	
	
	
	
	
	
	
} 