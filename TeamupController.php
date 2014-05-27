<?php

/*
 * Some Team Controller For User Contract PRocedures :
 * pashkovdenis@wgmail.com 
 * 2014 @
 * 
 * 
 */ 



 class TeamupController extends AppController {
 	
 	
	public $helpers = array (
			'Html',
			'General',
			'Linkid',
			'Verify' 
	);
	public $uses = array (
			'User',
			'Category',
			'Skill',
			'Region',
			'Country',
			'State',
			'SecurityQuestion' 
	);
	private $model; 
	
	
	public function getmileDesc($id){
		$this->autoRender = false;  
		$v =  $this->model->query("SELECT * FROM  teamup_milestones WHERE id = '{$id}'  ");
		 print_r($v[0]["teamup_milestones"]["desc"]);  
	}
	
	//  Before Filter Stuff :   
	
	public function beforeFilter() {
		parent::beforeFilter ();
		$this->loadModel ( "Teamup" );
		$this->loadModel ( "User" );
		$this->loadModel ( "Project" );
		$this->loadModel ( "JobBid" );
		$this->loadModel ( "Job" ); 
		$this->loadModel("SystemInbox"); 
	 
		
		$this->layout = "teamup"; 
		$this->model = new Teamup ();
	}
	
	/*
	 * Does user Has Access
	 */
	private function hasAccess($user, $user2) {
		$c = $this->model->find ( "count", array (
				"conditions" => array (
						"user_id" => $user,
						"to_user" => $user2 
				) 
		) );
		$c3 = $this->model->find ( "count", array (
				"conditions" => array (
						"user_id" => $user2,
						"to_user" => $user2 
				) 
		) );
		
		if ($this->Auth->user ( "id" ) == $user || $this->Auth->user ( "id" ) == $user || $this->Auth->user ( "id" ) == $user2)
			return true;
		
		if ($c == 0 && $c3 == 0)
			return false;
		return true;
	}
	  
	
	
	
	
	/*
	 * 
	 * Admin had Confirm  Closing Milestone Here :   
	 *  
	 * 2013  
	 * 
	 */
	 
	public function adminconfirm($mil) {
		
		$this->autoRender = false;
		$this->loadModel("User") ;  
		$comment = $_POST["comment"];  
		
		if ($mil && $this->Auth->user ( "id" )) {
			$this->model->query ( "UPDATE teamup_milestones SET closed = 1 WHERE id ='{$mil}'  " );
			$this->model->query ( " DELETE FROM   teamup_closed  WHERE milestone ='{$mil}'  " );
		}
		
		
		$milestone = $this->model->query ( "SELECT * FROM teamup_milestones WHERE id = '{$mil}'  " );  
		$tid = $milestone [0] ["teamup_milestones"] ["teamup"] ; 
		
	 
		$row = $this->model->find ( "first", array (
				"conditions" => array (
						"id" =>  $milestone [0] ["teamup_milestones"] ["teamup"] 
				)
		) );
		
		 $open =  $this->model->query("SELECT COUNT(*) as c FROM  teamup_milestones WHERE  teamup='{$tid}' AND closed <> 1   "); 
 
			App::import("model", "FeedbackFactory") ;


 		 // Leave   feedbakc For the   users :
 		 if ($open[0][0]["c"]==0){   
		    	 FeedbackFactory::addRequest( $row["Teamup"]["user_id"], $row["Teamup"]["to_user"] , $row["Teamup"]["job_id"],   LEAVE_FEEDBACK_EXPERT);
		    	 FeedbackFactory::addRequest( $row["Teamup"]["to_user"],  $row["Teamup"]["user_id"]  , $row["Teamup"]["job_id"] , LEAVE_FEEDBACK_LEADER);
		  }
		 
	    $text = ADMIN_MILESTONE_CLOSED  ; 
	    $text = str_replace ( "{name}",     $milestone [0] ["teamup_milestones"] ["title"]  , $text ); 
	    $text = str_replace ( "{comment}",   $comment  , $text ); 
	    $email = new CakeEmail ( 'gmail' );
	    $email->template ( 'default', "default" );
	    $email->emailFormat ( 'html' ); 
	    $user  = $this->User->find ( "first", array (
	    		"conditions" => array (
	    				"User.id" =>   $this->Auth->user("id")
	    		)
	    ) );
	    $team_up = $this->model->find ( "first", array (
	    		"conditions" => array (
	    				"id" => $milestone [0] ["teamup_milestones"] ["teamup"]
	    		)
	    ) ); 
	    $to_user = $this->User->find ( "first", array (
	    		"conditions" => array (
	    				"User.id" =>   $team_up["Teamup"]["to_user"]
	    		)
	    ) ); 
	    $email->from ( $user ["User"] ["email"] );
	    $email->to ( $to_user ["User"] ["email"] );
	    $email->subject ( " Milestone has been closed" );
	    $email->send ( $text );  
	    $system = str_replace("{name}",  $milestone [0] ["teamup_milestones"] ["title"]   , MILE_STONE_HAS_BEEN_CLOSED);   
	    SystemInbox::insert( $row["Teamup"]["to_user"],  $system  , $row ["Teamup"] ["job_id"] ) ;
	    $this->Session->setFlash ( __ ( '  Milestone  was closed. '   ), 'default', array (
	    		"class" => "success"
	    ) ); 
	     echo "Confirm";
	}

	/*
	 * Close MileStone Stacl :  
	 * 2013. 
	 * Server. 
	 */
	public function closemilestone($id) {
		$this->autoRender = false;
		
		if ($id) {


			$desc = $_POST ["desc"]; 
			$milestone = $this->model->query ( "SELECT * FROM teamup_milestones WHERE id = '{$id}'  " );
			$user = $this->User->find ( "first", array (
					"conditions" => array (
							"User.id" => $this->Auth->user ( "id" ) 
					) 
			) );

             $milestone2   =  $this->model->query("SELECT * FROM teamup_jobs WHERE id='".$milestone [0] ["teamup_milestones"] ["teamup"]."'  ");



			$user2 = $this->User->find ( "first", array (
					"conditions" => array (
							"User.id" =>$milestone2  [0] ["teamup_jobs"] ["user_id"]
					) 
			) );


			$text = CONFIRM_MILESTONE;
			$text = str_replace ( "{user}", $user ["User"] ["username"], $text );
			$text = str_replace ( "{title}", $milestone [0] ["teamup_milestones"] ["title"], $text );
			$text = str_replace ( "{desc}", $milestone [0] ["teamup_milestones"] ["desc"], $text );
			$text = str_replace ( "{comment}", $desc, $text );
			$text = str_replace ( "{date}", date ( "Y-m-d" ), $text );
			$row = $this->model->find ( "first", array (
					"conditions" => array (
							"id" => $milestone [0] ["teamup_milestones"] ["teamup"] 
					) 
			) );
			$text .= "<p>    <a href='" . SITE_URL . "/Teamup/milestones/{$row ["Teamup"] ["job_id"]}/{$row ["Teamup"] ["to_user"]}/' >  View  Details   </a>    </p> ";
			$email = new CakeEmail ( 'gmail' );
			$email->template ( 'default', "default" );
			$email->emailFormat ( 'html' );
            $email->from ( $user ["User"] ["email"] );
			$email->to ( $user2 ["User"] ["email"] );
            $email->subject ( "New Confirmed   Milestone  {$milestone[0]["teamup_milestones"]["title"]} " );
			$email->send ( $text );
			$desc = mysql_escape_string ( $desc );
			$text = mysql_escape_string ( $text );
			$this->model->query ( "INSERT INTO  teamup_closed SET milestone='{$id}'  ,  comments='{$desc}'  ,   description='{$text}'   " );
		}

           // check for Reported :
			SystemInbox::insert($milestone2  [0] ["teamup_jobs"] ["user_id"],  LEADER_CLOSED.$milestone [0] ["teamup_milestones"] ["title"], $row ["Teamup"] ["job_id"] ) ;
			
		$this->Session->setFlash ( __ ( '  Milestone confirmation was sent ' ), 'default', array (
				"class" => "success" 
		) );
	}
	
	
	/*
	 * Download FILES
	 * 2013  
	 * 
	 */
	public function downloadfile($id) {
		$this->autoRender = false;
		$file = $this->model->query ( "SELECT file FROM  teamup_files  WHERE id='{$id}' " );
		$file = $file [0] ["teamup_files"] ["file"];
		$fullPath = JOB_APPLY_TEMP_THUMB_DIR_FILE . "/" . $file;
		if ($fd = fopen ( $fullPath, "r" )) {
			$fsize = filesize ( $fullPath );
			$path_parts = pathinfo ( $fullPath );
			$ext = strtolower ( $path_parts ["extension"] );
			// $fl = $fullPath;
			header ( 'Content-Description: File Transfer' );
			header ( 'Content-Disposition: attachment; filename=' . basename ( $fullPath ) );
			header ( 'Content-Transfer-Encoding: binary' );
			header ( 'Expires: 0' );
			header ( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header ( 'Pragma: public' );
			header ( 'Content-Length: ' . filesize ( $fullPath ) );
			ob_clean ();
			flush ();
			readfile ( $fullPath );
			exit ();
		}
	}
	
	/*
	 * Remove File FRom room
	 */
	public function removefile($id) {
		$row = $this->model->find ( "first", array (
				"conditions" => array (
						"id" => $id 
				) 
		) );
		$this->loadModel("User") ;
			
		$user_loaded  =   new User();
		$user_ = $user_loaded->find("first",  array("conditions"=>array("id"=>$this->Auth->user("id"))));
		$file = $this->model->query ( "SELECT file FROM  teamup_files  WHERE id='{$id}' " );
		$file = $file [0] ["teamup_files"] ["file"];
		
		if ($row ["Teamup"] ["is_send"] == 1)
			$this->model->insertEvent ( "file", "File was removed " . $file, strftime ( "%Y-%m-%d %H:%M:%S", time () ). ":". $user_["User"]["username"] , $this->Auth->user ( "id" ), $file [0] ["teamup_files"] ["teamup_id"], "file" );
		$this->model->query ( "DELETE FROM  teamup_files  WHERE id=  '{$id}'  " );
	}
	
	 
	public function uploadfile($id) {
		$this->autoRender = false;
		$this->loadModel ( 'JobFileTemp' );
		$row = $this->model->find ( "first", array (
				"conditions" => array (
						"id" => $id 
				) 
		) );
		$this->loadModel("User") ;
		$j_model  = new Job(); 
		 
		$job_loaded = $j_model->find("first" , array("conditions"=>array("id"=>$row["Teamup"]["job_id"]))) ; 
		$user_loaded  =   new User(); 
		$user_ = $user_loaded->find("first",  array("conditions"=>array("id"=>$this->Auth->user("id"))));
		if (isset ( $this->request->data ["JobFileTemp"] ['job_file'] ['tmp_name'] )) {
			if (! empty ( $this->request->data ["JobFileTemp"] ['job_file'] ['tmp_name'] )) {
				$file_array = $this->request->data ["JobFileTemp"] ['job_file'];
				$this->request->data ["JobFileTemp"] ['job_file'] = $this->request->data ["JobFileTemp"] ['job_file'] ['name'];
			}
			if (! empty ( $file_array )) {
				$n = explode ( ".", $this->request->data ["JobFileTemp"] ['job_file'] );
				$file_name = array_shift ( $n );
				$file_name = str_replace ( " ", "_", $file_name );
			 	$filename = parent::__upload ( $file_array, JOB_APPLY_TEMP_THUMB_DIR_FILE, $file_name );
			}
		}
		$this->request->data ['JobFileTemp'] ['user_id'] = $this->Auth->User ( 'id' );
		$this->request->data ['JobFileTemp'] ['job_file'] = $filename;
	 	if (! empty ( $this->request->data )) {  
	 		 $this->model->query("UPDATE teamup_jobs SET changed=1 WHERE id='{$id}'  ") ;   
	 		 $this->model->query("UPDATE teamup_jobs SET admin_change=1  WHERE id = '{$id}' ");
	 		 $this->model->query ( "INSERT INTO teamup_files SET file='{$filename}' , teamup_id='{$id}'  ,  date='" . date ( "y-m-d" ) . "'  " );
			 if ($row ["Teamup"] ["is_send"] == 1)
				$this->model->insertEvent ( "file", "File added " . $filename, strftime ( "%Y-%m-%d %H:%M:%S", time () ). ":". $user_["User"]["username"], $this->Auth->user ( "id" ), $id, "file" );
			 	$lastInsert_id = $this->model->getLastInsertID ();
			echo "success|" . $filename . "|" . $lastInsert_id . "|" . date ( "Y-m-d" );
		}
		die ();
	}
	 
	
	
	
	/*
	 * STEP 2 Some Approvements Here hasAccess2 Check Acces if other user has access 
	 * 
	 */
	private function hasAccess2($work_id = null) {
		$user_id = $this->Auth->user ( "id" );
		if (empty ( $user_id ))
			return false;
		$count1 = $this->model->query ( "SELECT COUNT(*) as c FROM teamup_jobs WHERE  (user_id='{$user_id}' ) AND id='{$work_id}'  " );
		$count2 = $this->model->query ( "SELECT COUNT(*) as c FROM teamup_jobs WHERE  (to_user='{$user_id}' ) AND id='{$work_id}' AND is_send=1  " );
		// Check From user to user  
		  if ($count1[0][0]["c"]==0 && $count2[0][0]["c"]==0 )
		  return false;
		  return true;
	}
	
	
	
	
	/*
	 * Step 0  General  Step REqired For   all  THe Data :    
	 * pashkovdenis@gmail.com 
	 * 2014 
	 */  
	
	public function general($job_id, $userid, $id = false){
		
		
		$this->loadModel("Job")  ;  
		$job_model =   new Job()  ;     
		$this->loadModel("Colloberation") ; 
		$colloberation  =  new Colloberation()  ;  
		$this->Set("job_id" ,   $job_id);   
		$this->set("user_id",  $userid);  
		   
 		$this->hasAccess ( $this->Auth->user ( "id" ), $userid );
		if (empty ( $userid ) || $this->Auth->user ( "id" ) == "" || $this->hasAccess ( $this->Auth->user ( "id" ), $userid ) == false)
			$this->redirect ( "/" );
		
		if ($id == false)
			$id = $this->model->create2 ( $this->Auth->user ( "id" ), $userid, $job_id );
		else {
			$lost = $this->model->find ( "first", array (
					"conditions" => array (
							"id" => $id
					)
			) );
			$id = $lost ["Teamup"] ["id"];
		}
		$_SESSION["revision"] =array() ; //  Flush  Revisions :
		
		$row = $this->model->find ( "first", array (
				"conditions" => array (
						"id" => $id
				)
		) );
		$this->set ( "id", $id );
		$freelacner =  true ;   
		 
		$this->set ( "id", $id );
		$canedit = true;
		$project = new Project ();
		$project_id = $project->query ( "SELECT project_id FROM jobs WHERE id='{$job_id}'  " );
		$project_id = $project_id [0] ["jobs"] ["project_id"];
		$pc = $project->find ( "count", array (
				"id" => $project_id,
				"user_id" => $this->Auth->user ( "id" )
		) );
		if ($pc <= 0)
			$canedit = false;
		$this->set ( "user_id", $userid );
		$this->set ( "job_id", $job_id );
		
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["to_user"] || $row ["Teamup"] ["is_send"] == 1  )
			$canedit = false;
		
		
		
		
		
		
		$this->set ( "canedit", $canedit );
		$can_remove = true;
		$row = $this->model->find ( "first", array (
				"conditions" => array (
						"id" => $id
				)
		) );
		$needconfirm = false;
		
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["to_user"] && $row ["Teamup"] ["confirmed"] == 0)
			$needconfirm = true;
			
		
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["user_id"]) {
				
			$ids = array(0);//[0];
			$all = $this->model->query ( "SELECT * FROM   teamup_milestones WHERE  teamup = '{$id}'     " );
			foreach ( $all as $m )
				$ids [] = $m ["teamup_milestones"] ["id"];
				
			$closed = $this->model->query ( "SELECT * FROM  teamup_closed WHERE  milestone IN (" . join ( ",", $ids ) . ")" );

		 	foreach ( $closed as $index => $ar ) {
				$sl = $this->model->query ( "SELECT * FROM teamup_milestones WHERE id='{$ar["teamup_closed"]["milestone"]}'   " );
				$closed [$index] ["title"] = $sl [0] ["teamup_milestones"] ["title"];
				$closed [$index] ["desc"] = $sl [0] ["teamup_milestones"] ["desc"];
		
			}
			if (count($closed))
				$this->set ( "closed", $closed );
		}
		
		$this->set ( "needconfirm", $needconfirm );
		if ($row ["Teamup"] ["is_send"] == 1)
			$can_remove = false;
		$this->set ( "can_remove", $can_remove );
		$freelacner= 0  ; 
		
	   	$clb  =  $job_model->query("SELECT clb FROM  clb_job WHERE job_id='{$job_id}' "); 
	   	 
		if (isset($clb[0]["clb_job"]["clb"]) &&   $clb[0]["clb_job"]["clb"] == 7  ){
		  	$freelacner = 1 ;  
		  
		}
		
		
		$toid = false;
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["to_user"] && $row ["Teamup"] ["confirmed"] == 1)
			$toid = true;
		
		$this->set ( "toid", $toid );
		$this->set("owner" ,  Colloberation::getOwnerDrop( $clb[0]["clb_job"]["clb"] )); 
		$this->set("contract" ,  Colloberation::getContractDerop( $clb[0]["clb_job"]["clb"] )); 
		$this->set("freelacner" ,  $freelacner); 
		
		
		 $data=   $colloberation->query("SELECT * FROM teamup_general WHERE  teamup='{$id}'  ");  
		 
		 if (isset($data[0]["teamup_general"])){
		 	$this->set("data",  $data[0]["teamup_general"]); 
		 } 
		  
		 
	
		 
		// Genera l Stack  :   
		if (isset($_POST["general"])){
		    
			if ($canedit){
				
				if (!isset($_POST["other_text"]))
					$_POST["other_text"] =  1 ; 
				
 				$colloberation->query("DELETE FROM teamup_general WHERE teamup ='{$id}' ") ; 
				$colloberation->query("INSERT INTO teamup_general SET teamup='{$id}' ,  owner ='{$_POST["owner"]}' ,   contract='{$_POST["contract"]}' ,  credits='{$_POST["credits"]}' , earning='{$_POST["earning"]}' , other_text ='{$_POST["other_text"]}' ") ; 
				$this->Session->setFlash ( __ ( 'Saved' ), 'default', array (
						"class" => "success"
				) ); 
			}
 
				 // teamup milestones :    
				 $this->redirect("/teamup/milestones/".$job_id."/".$userid."/{$id}" ) ; 
				 return;
		 }
	 	
		
	}
	
	 
	
	  
	/*
	 * Milestone Opens  Here :  
	 * 2013 :  
	 * 
	 */
	 
	public function milestones($job_id, $userid, $id = false) {
		
		$this->hasAccess ( $this->Auth->user ( "id" ), $userid );
		if (empty ( $userid ) || $this->Auth->user ( "id" ) == "" || $this->hasAccess ( $this->Auth->user ( "id" ), $userid ) == false)
			$this->redirect ( "/" );
		
		if ($id == false)
			$id = $this->model->create2 ( $this->Auth->user ( "id" ), $userid, $job_id );
		else {
			$lost = $this->model->find ( "first", array (
					"conditions" => array (
							"id" => $id 
					) 
			) );
			$id = $lost ["Teamup"] ["id"];
		}
		$_SESSION["revision"] = array() ; //  Flush  Revisions :   
		
		$row = $this->model->find ( "first", array (
				"conditions" => array (
						"id" => $id 
				) 
		) );
		
		$this->set ( "id", $id );
		$canedit = true;
		$project = new Project ();
		$project_id = $project->query ( "SELECT project_id FROM jobs WHERE id='{$job_id}'  " );
		$project_id = $project_id [0] ["jobs"] ["project_id"];
		$pc = $project->find ( "count", array (
				"id" => $project_id,
				"user_id" => $this->Auth->user ( "id" ) 
		) );
		if ($pc <= 0)
			$canedit = false;
		$this->set ( "user_id", $userid );
		$this->set ( "job_id", $job_id );
		
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["to_user"])
			$canedit = false;
		
		
		// Check role  
		if ($this->Auth->user("role_id") == 4  ) 
			$canedit  =false ;  
		
		
		
		
		$this->set ( "canedit", $canedit );
		$can_remove = true;
		$row = $this->model->find ( "first", array (
				"conditions" => array (
						"id" => $id 
				) 
		) );
		$needconfirm = false;
		
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["to_user"] && $row ["Teamup"] ["confirmed"] == 0)
			$needconfirm = true;
			
		  
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["user_id"]) {
		 
			$ids = array(0); //[0]
			$all = $this->model->query ( "SELECT * FROM   teamup_milestones WHERE  teamup = '{$id}'     " );
			foreach ( $all as $m )
				$ids [] = $m ["teamup_milestones"] ["id"];
				 
			$closed = $this->model->query ( "SELECT * FROM  teamup_closed WHERE  milestone IN (" . join ( ",", $ids ) . ")" );
			
			 
			foreach ( $closed as $index => $ar ) {
				$sl = $this->model->query ( "SELECT * FROM teamup_milestones WHERE id='{$ar["teamup_closed"]["milestone"]}'   " );
				$closed [$index] ["title"] = $sl [0] ["teamup_milestones"] ["title"];
				$closed [$index] ["desc"] = $sl [0] ["teamup_milestones"] ["desc"];
				
			}
			  if (count($closed))
			$this->set ( "closed", $closed );
		}
		
		$this->set ( "needconfirm", $needconfirm );
		if ($row ["Teamup"] ["is_send"] == 1)
			$can_remove = false;
		$this->set ( "can_remove", $can_remove );
		$toid = false;
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["to_user"] && $row ["Teamup"] ["confirmed"] == 1)
			$toid = true;
		 
		$this->set ( "toid", $toid );
		
		
		
		// Check for   Atlest one need to be filled  
		
		$atlestone =   0;    
		
		$jobmodel = new Job(); 
		$job_selected =  $jobmodel->find("first",  array("id"=>$row ["Teamup"] ["job_id"])); 
		if ($job_selected["Job"]["contracter_percent"] ==1) 
		$atlestone = 1 ;  
		 
		$this->set("required", $atlestone ); 
		
		
		
		
		if (isset ( $_POST ["proceed"] )) {
			$data = null;
			if (! empty ( $_POST ["stone"] ))
				$data = $_POST ["stone"];
			
			
			
			//  Check wherever at lest one required  Milestone :  
			if ($atlestone ==1 && $canedit && count($data)==0){
				$this->Session->setFlash ( __ (  MILISTONE_REQ ), 'default', array (
						"class" => "error"
				) ); $this->set ( "stones", $this->model->loadMilestones ( $id ) );
				
				return; 
					
					
			} 
			
			
			
			
			// Data :
			if (empty ( $data )) {
			
			
				
				
			} else { 
			 
				
				if ($this->Auth->user("id")==  $row["Teamup"]["user_id"]) {
					 
					if ($this->model->checkRevisions ( "stone", $data, $this->Auth->user ( "id" ), $id )==400){ 
						 	$this->model->query("UPDATE teamup_jobs SET admin_change=1  WHERE id = '{$id}' "); 
					  }
					
					if ($this->model->validate ( $project_id, $data ) == false) {
						$this->set ( "stones", $this->model->loadMilestones ( $id ) ); 
						 
						
						$this->Session->setFlash ( __ ( '  Milestone Percent  is more  then dream owner value ' ), 'default', array (
								"class" => "error" 
						) );
						return;
					}
				}
			 
				  
				$this->model->saveMilestones ( $id, $data, $this->Auth->user ( "id" ) );
			}
			$this->redirect ( "/teamup/custom_terms/{$id}" );
		} 
		 	$this->set ( "stones", $this->model->loadMilestones ( $id ) );
	}
	
	 
	
	/*
	 * STEP @ Work Product Data Stack 2013
	 * 2013  
	 */ 
	
	public function work_product($id = null) {
		$row = $this->model->find ( "first", array (
				"conditions" => array (
						"id" => $id 
				) 
		) );
		
		if (empty ( $row ["Teamup"] ["to_user"] ) || $this->hasAccess2 ( $id ) == false)
			$this->redirect ( "/" );
		
		$canedit = true;
		$project = new Project ();
		$project_id = $project->query ( "SELECT project_id FROM jobs WHERE id='{$row ["Teamup"] ["job_id"] }'  " );
		$project_id = $project_id [0] ["jobs"] ["project_id"];
		$pc = $project->find ( "count", array (
				"id" => $project_id,
				"user_id" => $this->Auth->user ( "id" ) 
		) );
		
		if ($pc <= 0)
			$canedit = false;
		
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["to_user"] ||  $row ["Teamup"] ["is_send"]==1)
		$canedit = false;
		 
		
		
		$this->set ( "canedit", $canedit );
		$can_remove = true;
		
		if ($row ["Teamup"] ["is_send"] == 1)
			$can_remove = false;
		
		$this->set ( "can_remove", $can_remove );
		$needconfirm = false;
		
		$toid = false;
		
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["to_user"])
			$toid = true;
		$this->set ( "toid", $toid );
		
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["to_user"] && $row ["Teamup"] ["confirmed"] == 0)
			$needconfirm = true;
		
		$this->set ( "needconfirm", $needconfirm );
		if (empty ( $row ["Teamup"] ["id"] ) || $this->hasAccess ( $this->Auth->user ( "id" ), $row ["Teamup"] ["to_user"] ) == false)
			$this->redirect ( "/" );
		
		$this->set ( "id", $id );
		$this->set ( "user_id", $row ["Teamup"] ["to_user"] );
		$this->set ( "job_id", $row ["Teamup"] ["job_id"] );
		
		$data = $this->model->loadWorkData ( $id );
		$this->set ( "data", $data );
		
		if (isset ( $_POST ["proceed"] )) {
			
			if ($canedit)
				$this->model->saveWorkData ( $id, $_POST, $this->Auth->user ( "id" ) );
			
			$this->redirect ( "/teamup/custom_terms/{$id}" );
		}
	}
	
	
	 
	
	/*
	 * Custom Rems
	 * pashkovdenis@gmail.com  
	 * revision  2.1
	 * 2014    
	 * 
	 * 
	 */
	
	
	public function custom_terms($id = null) {
		
		  $this->loadModel("Workroom");
        $Workroom_model  =  new Workroom() ;

		
		$row = $this->model->find ( "first", array (
				"conditions" => array (
						"id" => $id 
				) 
		) );
		
		
		
		if (empty ( $row ["Teamup"] ["to_user"] ) || $this->hasAccess2 ( $id ) == false)
			$this->redirect ( "/" );
		if (empty ( $row ["Teamup"] ["id"] ) || $this->hasAccess ( $this->Auth->user ( "id" ), $row ["Teamup"] ["to_user"] ) == false)
			$this->redirect ( "/" );

        $workroom_url  =  Workroom::getJob( $row ["Teamup"] ["job_id"] ) ;

        // Clear  Hidden :



		$this->set ( "id", $id );
		$this->set ( "user_id", $row ["Teamup"] ["to_user"] );
		$this->set ( "job_id", $row ["Teamup"] ["job_id"] );
		$this->set ( "is_send", $row ["Teamup"] ["is_send"] );
		$this->set ( "log", $this->model->getLogList ( $id ) );
		
		$ex = $this->User->find("first", array("conditions"=>array("User.id"=> $row ["Teamup"] ["to_user"]))); 
		$this->set("expert_name", $ex["User"]["username"]);
 
	 	$toid = false;
		 
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["to_user"])
			$toid = true;
		$this->set ( "toid", $toid );
		
		$canedit = true;
		$project = new Project (); 
		 
		$project_id = $project->query ( "SELECT project_id FROM jobs WHERE id='{$row ["Teamup"] ["job_id"] }'  " );
		$project_id = $project_id [0] ["jobs"] ["project_id"];
		$pc = $project->find ( "count", array (
				"id" => $project_id,
				"user_id" => $this->Auth->user ( "id" ) 
		) ); 
		
		
		if ($pc <= 0)
			$canedit = false;  
		
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["to_user"] ||  $row ["Teamup"] ["is_send"] ==1    )
			$canedit = false; 
		
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["user_id"] &&   $row ["Teamup"] ["admin_change"] ==1   ) 
			 $this->set("canupdate",  true); 
		
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["user_id"]) 
				 $this->set("ileader" , true ); 
		
		
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["user_id"] &&   $row ["Teamup"] ["is_send"]!=1) 
		$this->set("first_send" , true ); 
		 
		
		/*
		 * 
		 * Confirm Some Leaders   Changes    
		 * 2013  
		 * 
		 * 
		 */  
		
		
		
		if (isset($_POST["resendreview"])){
			
			$userload =  $this->User->find("first",  array("conditions"=>array("User.id"=>$this->Auth->user("id"))));
			$this->model->insertEvent ( "updated",   "Submitted updates", strftime ( "%Y-%m-%d %H:%M:%S", time()  ).":".$userload["User"]["username"] , $this->Auth->user("id") , $id,  "Milestone Table"   );
		 	$this->model->query ( "UPDATE teamup_jobs SET  admin_change=0 , changed=1 WHERE id='{$id}'   " ); 
			$job_model = new Job() ;
			$job_loaded = $job_model->find ( "first", array (
					"conditions" => array (
							"id" => $row ["Teamup"] ["job_id"]
					)
			) );
			
			
			SystemInbox::insert($row ["Teamup"]["to_user"],  "<a href='" . SITE_URL . "/Teamup/general/{$row ["Teamup"] ["job_id"]}/{$row ["Teamup"] ["to_user"]}/{$id}' > " . 
			EXPERT_TERMS_OF_CONTRACT  . "</a>  " . $job_loaded ["Job"]["title"] , $job_loaded ["Job"]["id"]);
			
			
 			$this->loadModel("User") ;
			$user_loaded  =   new User();
			$user_ = $user_loaded->find("first",  array("conditions"=>array("id"=>$this->Auth->user("id"))));
			$user2 = $user_loaded->find("first",  array("conditions"=>array("id"=> $row["Teamup"]["to_user"])));
			$text =  ADMIN_HAS_MADE_CHANGES;
			$email = new CakeEmail ( 'gmail' );
			$email->template ( 'default', "default" );
			$email->emailFormat ( 'html' );
			$email->from ( $user_ ["User"] ["email"] );
			$email->to ( $user2 ["User"] ["email"] );
			$email->subject ( "Leader has made updates into  milestones table" );
			
			
			 $email->send ( "<a href='" . SITE_URL . "/Teamup/general/{$row ["Teamup"] ["job_id"]}/{$row ["Teamup"] ["to_user"]}/{$id}' > "   .  $text . "</a> " );  
			
			
			$this->Session->setFlash ( __ ( 'Has been sent' ), 'default', array (
					"class" => "success"
			) ); 
			
			
			$this->model->submitRevisions();
			$this->redirect (  $workroom_url);
			
		}
		
		
		 /*
		 * End Leaders Changes  
		 * 3024  
		 */
		 
		 
		
		
		
			$leader = false ; 
			if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["user_id"]) 
			 $leader =true ; 
			 $this->set("leader",  $leader);  
	 		 $this->set ( "canedit", $canedit );
		 	 $needconfirm = false;
		
	
		if ($row ["Teamup"] ["changed"] ==1 && $this->Auth->user ( "id" ) == $row ["Teamup"] ["to_user"] ){
			$this->set("changed" , 1)  ; 
		}
		
		if ($this->Auth->user ( "id" ) == $row ["Teamup"] ["to_user"] && $row ["Teamup"] ["confirmed"] == 0)
			$needconfirm = true;
		
		$this->set ( "needconfirm", $needconfirm );
		 
		 
		
		
		/*
		 * Expert Has been  agree  With Some Changes 
		 * 2014 
		 * revision  2    
		 * pashkovdenis@gmail.com    
		 *  
		 * 
		 */
		
		
		
		if (isset($_POST["changed"])){ 
			
			
		    	 $this->model->query("UPDATE teamup_jobs SET changed=0  WHERE  id='{$id}' "); 
		    	 $job_model=  new Job();  
		    	 $job_loaded =  $job_model->find("first", array("conditions"=>array("id"=>$row["Teamup"]["job_id"])) );
		    	 $usermodel = new User (); 
				 $current_user = $usermodel->find ( "first", array (
						"conditions" => array (
								"id" => $this->Auth->user ( "id" )
						)
				) );
				 $leader_loaded = $usermodel->find ( "first", array (
						"conditions" => array (
								"id" => $row ["Teamup"] ["user_id"]
						)
				) ); 
						$new = new Job() ; 
						$jb = $new->find("first" , array("conditions"=>array("id"=> $row ["Teamup"] ["job_id"])));  
				 $text = str_replace ( "{user}", $current_user ["User"] ["username"], LEADER_TERMS_2   );
				  
			  	 $email = new CakeEmail ( 'gmail' );
				 $email->template ( 'default', "default" );
				 $email->emailFormat ( 'html' );
				 $email->from ( $current_user ["User"] ["email"] );
				 $email->to ( $leader_loaded ["User"] ["email"] );
				 $email->subject ( "User Agree with updates. "   );
				 $email->send ( "<a href='" . SITE_URL . "/Teamup/general/{$row ["Teamup"] ["job_id"]}/{$row ["Teamup"] ["to_user"]}/{$id}' > " .  $text . "</a> " ); 
				 
				 
				 $this->model->insertEvent ( "updated", $text .  $job_loaded ["Job"]["title"] , strftime ( "%Y-%m-%d %H:%M:%S", time()  ).":".$current_user["User"]["username"] , $this->Auth->user("id") , $id,  "Milestone Table"   );

				 
				 SystemInbox::insert($row ["Teamup"] ["user_id"], "<a href='" . SITE_URL . "/Teamup/general/{$row ["Teamup"] ["job_id"]}/{$row ["Teamup"] ["to_user"]}/{$id}' > " . 
				 LEADER_TERMS_2 . " </a> " . $jb["Job"]["title"] , $jb["Job"]["id"] ) ;  
				 
				  
				 $this->Session->setFlash ( __ ( '   Approvement Message has been sent     ' ), 'default', array (
				 		"class" => "success"
				 ) ); 
				 $this->model->submitRevisions();
				 $this->redirect ( $workroom_url);
				 return  ; 
		 }
		
		
		 
		 
		 /*
		  * Post Request Was Send   
		  * 2013 :   
		  * 
		  */
		
		if (isset ( $_POST ["proceed"] ) && $canedit) {
			 
			if ($canedit) {
				$this->model->checkRevisions ( "terms", $_POST, $this->Auth->user ( "id" ), $id );
				$this->model->saveTerms ( $_POST, $id );
				 
				if ($row ["Teamup"] ["is_send"] != 1) {
					 
					 
					$usermodel = new User ();
					 
					$current_user = $usermodel->find ( "first", array (
							"conditions" => array (
									"id" => $this->Auth->user ( "id" )
							)
					) );
					$expert_loaded = $usermodel->find ( "first", array (
							"conditions" => array (
									"id" => $row ["Teamup"] ["to_user"]
							)
					) );
					$job_model = new Job ();
					$job_loaded = $job_model->find ( "first", array (
							"conditions" => array (
									"id" => $row ["Teamup"] ["job_id"]
							)
					) );
					 
					 
					$text = str_replace ( "{user}", $current_user ["User"] ["username"], TEAMUP_SEND );
					$text = str_replace ( "{jobname} ", $job_loaded ["Job"] ["title"], $text );
					$text .= "<p>    <a href='" . SITE_URL . "/Teamup/general/{$row ["Teamup"] ["job_id"]}/{$row ["Teamup"] ["to_user"]}/{$id}' >  View  Details   </a>    </p> ";
					$email = new CakeEmail ( 'gmail' );
					$email->template ( 'default', "default" );
					$email->emailFormat ( 'html' );
					$email->from ( $current_user ["User"] ["email"] );
					$email->to ( $expert_loaded ["User"] ["email"] );
					$email->subject ( " Teamup request for job " . $job_loaded ["Job"] ["title"] );
					$email->send ( $text );
				   $this->model->submitRevisions(); 
				   
					$this->Session->setFlash ( __ ( 'Has been sent' ), 'default', array (
							"class" => "success"
					) );
					SystemInbox::insert($row ["Teamup"] ["to_user"] , " <a href='" . SITE_URL . "/Teamup/general/{$row ["Teamup"] ["job_id"]}/{$row ["Teamup"] ["to_user"]}/{$id}' > " .   EXPERT_PLEASE_READ. "  </a> " .$job_loaded ["Job"] ["title"], $job_loaded ["Job"]["id"]) ;
				}
				
			}
			$this->redirect ( $workroom_url );
		} else {
			 
			
			
			
			/*
			 * Agree  
			 * sned Agree Message  
			 * 2014  
			 */
			
			if (isset ( $_POST ["agree"] )) {
				
				
				
				$this->model->query ( "UPDATE teamup_jobs SET confirmed = 1 WHERE id='{$id}' AND to_user='{$this->Auth->user("id")}'  " );
				$job_model = new Job ();
				$usermodel = new User ();
				$this->loadModel ( "JobBid" );
				$this->loadModel ( "Job" );
				$this->loadModel ( "Project" );
				
				$load_job = $this->Job->find ( "first", array (
						"conditions" => array (
								"id" => $row ["Teamup"] ["job_id"] 
						) 
				) );
				
				$job_bid = new JobBid ();
				$current_user = $usermodel->find ( "first", array (
						"conditions" => array (
								"id" => $this->Auth->user ( "id" ) 
						) 
				) );
				$leader = $usermodel->find ( "first", array (
						"conditions" => array (
								"id" => $row ["Teamup"] ["user_id"] 
						) 
				) );
				$job_loaded = $job_model->find ( "first", array (
						"conditions" => array (
								"id" => $row ["Teamup"] ["job_id"] 
						) 
				) );
				
				$text = TEAMUP_APPROVED . " With Job  " . $job_loaded ["Job"] ["title"];
				$text .= "<p>    <a href='" . SITE_URL . "/Teamup/general/{$row ["Teamup"] ["job_id"]}/{$row ["Teamup"] ["to_user"]}/{$id}' >  View  Details   </a>    </p> ";
			 	
				// CakeEmail
				$email = new CakeEmail ( 'gmail' );
				$email->template ( 'default', "default" );
				$email->emailFormat ( 'html' );
				$email->from ( $current_user ["User"] ["email"] );
				$email->to ( $leader ["User"] ["email"] );
				$email->subject ( " Teamup User  {$current_user["User"]["username"]}  Approved Request  " . $job_loaded ["Job"] ["title"] );
				$email->send ( $text );
				
				$this->Session->setFlash ( __ ( '   Approvement Message has been sent     ' ), 'default', array (
						"class" => "success" 
				) );
				  
				
				
				 // SystemInbox ::   2014  ;    
				 SystemInbox::insert($row ["Teamup"] ["user_id"] , "  <a href='" . SITE_URL . "/Teamup/general/{$row ["Teamup"] ["job_id"]}/{$row ["Teamup"] ["to_user"]}/{$id}' > " . 
				 LEADER_EXPERT_HAS_CONFIRM.$job_loaded["Job"]["title"] . "</a>", $job_loaded["Job"]["id"] ); 
				 $this->redirect ( $workroom_url);
				// SystemInbox::  
				 
				 
			}
		}
		
		$data = $this->model->query ( "SELECT * FROM  teamup_terms WHERE team_id ='{$id}' " );
		$this->set ( "data", $data );
		$can_remove = true;
		if ($row ["Teamup"] ["is_send"] == 1)
			$can_remove = false;
		$this->set ( "can_remove", $can_remove );
		$files = $this->model->query ( "SELECT * FROM teamup_files WHERE teamup_id ='{$id}' " );
		$file_obj = array ();
		
		foreach ( $files as $file ) {
			$f = new stdClass ();
			$f->id = $file ["teamup_files"] ["id"];
			$f->file = $file ["teamup_files"] ["file"];
			$f->title = $file ["teamup_files"] ["date"];
			
			$file_obj [] = $f;
		}
		 $this->set ( "files", $file_obj );
	}
	 
	
	
	/*
	 * Here We need to   Send   Users  Email And notify That>   
	 * 2013  That we wanna TEam up  with Him   
	 * pashkovdenis@gmail.com    
	 * 
	 */
	 
	public function gotoroom($job = false, $user_id = false) { 
		
		
		
		$this->autoRender = false;
		$this->loadModel ( "User" );
		$this->loadModel ( "Job" );
		$this->loadModel("JobBid"); 
		 $bidModel =  new JobBid()  ;  
		 
		$this->loadModel ( "Workroom" );
		$this->loadModel ( "Project" );  
		$work_room =  new Workroom() ;  
		
	     $id = $this->model->create2 ( $this->Auth->user ( "id" ),  $user_id, $job ); 
		 $row = $this->model->find ( "first", array (
		 		"conditions" => array (
		 				"id" => $id
		 		)
		 ) );  
		   
		 
		 /*
		  * Transform Chatroom  into    
		  * JobWorkroom  For that user :    
		  * 2014  
		  * 
		  */
		  
	     $j =   $work_room->find("first",  array("conditions"=>array("type"=>2, "job_id"=>$job))); 
	     $job_room  =  $j["Workroom"]["id"]; 
		 $work_room->query("DELETE FROM workroom_hidden WHERE room='{$job_room}' AND user='{$user_id}' "); 
	     $w = $work_room->query("SELECT * FROM workroom WHERE type=0 AND job_id='{$job}' AND  (user_id ='{$user_id}' OR leader='{$user_id}') ORDER BY id DESC   ") ;
		 if(sizeof($w)!=0){
		 $caht_room  = $w[0]["workroom"]["id"] ;  
	     $work_room->query("UPDATE workroom_chat SET room_id='{$job_room}' WHERE room_id='{$caht_room}' ") ;  
	     $work_room->query("DELETE FROM  workroom WHERE id='{$caht_room}' ") ; 
		 //  End cleaning Stuff here :    
		 // Remove Private rooms :   
		 }
	      $work_room->query("DELETE FROM workroom  WHERE  job_id='{$job}' AND  job_private ='1' ") ; 
	      
		  
	     
	     
	     
	     // End of transformation :  
	     $this->redirect( "/Teamup/general/{$job}/{$user_id}" );   
	      	   
	  	exit ();
	}
}