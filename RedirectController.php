<?php
 /*
  * Redirect Service Controller 
  * 2013 
  * pashkovdenis@gmail.com 
  * 
  */

class RedirectController extends AppController {
 
		// Store back Url From forms
		public function setback(){
			 $this->autoRender = false;  
			 $this->Session->write("back", $_POST["back"] );
			 echo "Saved";  
		}
	
	
	
}