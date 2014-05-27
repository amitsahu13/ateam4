<?php
 
/*
 * Collaboration  
 * pashkovdenis@gmail.com  
 * 2014   
 * 
 */

class CollaborationController   extends AppController {
	
	var	$name	=	'Collaboration';
	var $helpers = array('General');
	public $components = array('General','Cookie','Upload','RequestHandler');  

	private $model ;   
	
	
	/*
	 * Befomre Filter :  
	 * pashkovdenis@gmail.com  
	 * 2014    
	 * 
	 */ 
	
	public function beforeFilter(){
		$this->autoLayout = false ; 
		$this->autoRender = false;   
		parent::beforeFilter() ;   
		$this->loadModel("Colloberation")  ; 
		$this->model   = new  Colloberation();   
		 
	}
	
	
	/*
	 * Ajax Methods 
	 * pashkovdenis@gmail.com   
	 * 2014 :    
	 * 
	 *    
	 */
	  	
	public function getprojectcolaboration($id,$project_id=null) {  
 			$loaded = null  ;   
			  
 			App::import("model", "Project") ;  
			$project_model =  new Project() ;   
 			
			$loaded =  $project_model->find("first" , array("conditions"=>array("id"=>$id))) ;  
 			$data_saved  =  $this->model->query("SELECT * FROM clb_projects  WHERE project='{$project_id}'  "); 
 			 
 			$data=false;  

 			
 			// DAta saved  : 
 			if (isset($data_saved[0]["clb_projects"]))
 				$data = $data_saved[0]["clb_projects"];   
 			
 			
 			$free= null  ; 
 			 $free =$data["freelance"]; 
 			if (empty($free) &&   isset($_SESSION["freelace"] )) 
 			$free = $_SESSION["freelace"] ; 
 			
 			
 			if (empty($id)) 
 				return "" ;  
 			 
 			if ($id==82 || $id == 83) { 
 				$loaded =  $this->model->loadSingle( 3  ) ; 
 			}else 
 				$loaded =  $this->model->loadSingle( 4   ) ;
 			
 			
			  $str = ' 
		  		<input type="hidden" name="collaboration" value="'.$loaded->id.'" />  
		  		
		  		<label class="compensation_frmrow_L">Collaboration Type<span></span></label>
							 <div class="compensation_frmrow_R">
								<span class=" ">
									'.$loaded->title.'
								</span>
						</div> 
						<div class="clear"></div>
						<label class="compensation_frmrow_L">Explanation<span></span></label>
							 <div class="compensation_frmrow_R">
								<span class=" ">
									'.$loaded->explain.'
								</span>
						</div> 					
		  		
		  		<div class="clear"></div>
		  		<label class="compensation_frmrow_L">Example<span></span></label>
							 <div class="compensation_frmrow_R">
								<span class=" ">
									'.$loaded->example.'
								</span>
						</div> 
		  		<div class="clear"></div>
		  		
		  		<label class="compensation_frmrow_L">Freelancing assistance </label>
							 <div class="compensation_frmrow_R">
							 Do you allow also Freelancer jobs in project?<span>*</span>
								<span class=" ">
									<input type="radio" name="freelance" value="1" required '.($free==1?'checked':null).' /> Yes    
		  							<input type="radio" name="freelance" value="0" required '.($free==0?'checked':null).' /> No     
		  				   		 </span>
						</div>  
		  		<div class="clear"></div>
		  		
											
		  		
		  		'; 
			
		  
		  
		  
			
			
			
		  
		 echo $str ;  
	}
	
	
	/*
	 * Get Details :   
	 * pashkovdenis@gmail.com   
	 * 2014  
	 
	 */ 
	 
	public function  loadinfo($id){ 
		$this->model->loadSingle($id)  ; 
		$result =  array(
			"explain"=>$this->model->explain , 
			"example"=>$this->model->example , 
			"link" => "<a href='".SITE_URL."/jobs/read_pdf/".$this->model->id."' target='_blank'>   Independent contractor managed ".$this->model->title." default agreement </a> ", 
		 );  
		
		 return json_encode($result); 
	}
 
	
	
	
}