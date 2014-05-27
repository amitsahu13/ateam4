<?php
/**
 * Blogs Controller
 * PHP version 5.4
 */
class WorkroomsController extends AppController {
	/**
	 * Controller name
	 *
	 * @var string
	 * @access public
	 */
	var $name = 'Workrooms';
	public $helpers = array (
			'General',
			'Html' 
	);
	var $model = 'Workroom';
	var $controller = 'workrooms';
	private $work_room_id = null;
	
	// index WorkingRoom
	public function index() {
		$this->autoRender = false;
		$this->redirect ( "/" );
	}
	
	
	
	
	/*
	 * ____________________ 
	 * Check is Workroom  close  
	 * pashkovdenis@gmail.com  
	 * 2014 
	 * _____________________
	 */
	
	
	public function isactive($id){
		 $this->loadModel("Workroom") ; 
		 $room  =   $this->Workroom->find("first" , array("conditions"=>array("id"=>$id)));   
		 if ($room["Workroom"]["active"]==0){
		 	$this->Session->setFlash ( __ ( 'Sorry Workroom  Closed  ' ), 'default', array (
		 			"class" => "error"
		 	) );  
		 	 $this->redirect($_SERVER["HTTP_REFERER"]) ;  
		  }   
		return true;  
	}
	 
	
	
	
	
	// Test  getting   title  of  Workmroom  :  
	public function  gettitle($id){
		$this->loadModel("Workroom")  ; 
		 $this->autoLayout =  false;  
		 $this->autoRender   = false; 
		
		  
		 echo  Workroom::getTitle($id) ; 
 
		 
		exit;  
	}
	
	
	
	// 
	public function fix() {
		$this->autoRender = false;
		$this->loadModel ( "Workroom" );
		$this->loadModel ( "Project" );
		$w = new Workroom ();
		$project = new Project ();
		$projects = $project->find ( "all" );
		
		foreach ( $projects as $p ) {
			$w->createIfnotFromProject ( $p ["Project"] ["id"], $p ["Project"] ["user_id"], $p ["Project"] ["title"] );
		}
	}
	
	// remove File From stack
	public function removefile($id) {
		$this->loadModel ( "Workroom" );
		$model = new Workroom ();
		if ($this->Auth->user ( "id" )) {
		}
	}
	
	 
	
	//  Check Expert Visibility :  
	//  2014  : 
	 
	public function hasAccess($room){



        $this->loadModel ( "Workroom" );
		$model  = new Workroom()  ;
        $rooml =  $model->find("first", array("conditions"=>array("Workroom.id"=>$room)));


  		$c=  $model->query("SELECT COUNT(*) as c FROM  workroom_hidden WHERE room='{$room}'  AND user='{$this->Auth->user("id")}'  "); 
 		  if ($c[0][0]["c"]>0 && $rooml["Workroom"]["type"] != 1 )
		  	return false;  	
		return true; 
	
	}
	 
	
	/*
	 * Kick Expert From Workroom  
	 * 2014 :  
	 * 
	 */
	
	public function  hideexpert($room ,  $id ,$projectid){
		$this->autoRender = false; 
		$this->loadModel ( "Workroom" );
		$this->loadModel("Project")  ;    
		$this->loadModel("User") ;  
		 
		$loaded_room1  = new Workroom();
		$loaded_room  = $loaded_room1 ->find("first", array("conditions"=>array("id"=>$room))) ; 
		
		$user_loaded1 =  new User();
		$user_loaded =  $user_loaded1->find("first",  array("conditions"=>array("id"=>$id))) ; 
		 
		$project =new Project(); 
		$row =  $project->find("count", array("conditions"=>array("id"=>$projectid, "user_id"=>$this->Auth->user("id"))));
		if ($row>0){
			$model = new Workroom (); 
			$user = $this->Auth->user("id");  
			$model->query("INSERT INTO workroom_hidden SET room='{$room}' , user='{$id}' ");  
		 }
		 $this->loadModel("SystemInbox"); 
  		 $text =WORKROOM_WAS_DISABLED_FOR_EXPERT ;  
 		 $text = str_replace("{user}", $user_loaded["User"]["username"], $text) ; 
 		 $text = str_replace("{room}",    Workroom::getTitle( $loaded_room["Workroom"]["id"])  , $text) ; 
 		 SystemInbox::insert( $id,  $text, $loaded_room["Workroom"]["job_id"], $projectid); 
 		 $this->sendMail($user_loaded["User"]["email"], "You have banned", $text, SITE_EMAIL) ; 
  }
	 
  
  
  
  // Show hidden Expert Here :  
	public function   showexpert($room ,  $id , $projectid=null){
		
		$this->autoRender = false; 
		$this->loadModel ( "Workroom" );
		$this->loadModel("Project")  ;   
		$project =new Project(); 
				$loaded_room1  = new Workroom();
		$loaded_room  = $loaded_room1 ->find("first", array("conditions"=>array("id"=>$room))) ; 
		
		$user_loaded1 =  new User();
		$user_loaded =  $user_loaded1->find("first",  array("conditions"=>array("id"=>$id))) ; 

		//$loaded_room  = (new Workroom())->find("first", array("conditions"=>array("id"=>$room))) ;
		
		//$user_loaded =  (new User())->find("first",  array("conditions"=>array("id"=>$id))) ;
		$project =new Project();
		 $model = new Workroom (); 
			$user = $this->Auth->user("id");  
			$model->query("DELETE FROM  workroom_hidden WHERE room='{$room}' AND  user='{$id}' ");  
			echo "DELETE FROM  workroom_hidden WHERE room='{$room}' AND  user='{$id}' "  ; 
			
		  $this->loadModel("SystemInbox");
		 $text  =  WORKROOM_WAS_ENABLED_FOR_EXPERT ;
		 $text = str_replace("{user}", $user_loaded["User"]["username"], $text) ;
		 $text = str_replace("{room}",   Workroom::getTitle($loaded_room["Workroom"]["id"] ), $text) ;
		 SystemInbox::insert( $id,  $text, $loaded_room["Workroom"]["job_id"], $projectid);
		 $this->sendMail($user_loaded["User"]["email"], "You have banned", $text, SITE_EMAIL) ; 
		echo  "Expert Was Enabled :  room  {$room}  user {$id} ";
		  
	 }
	
	
 
	/*
	 * Remove User From  Project :  
	 * 
	 */
	public function removeUserFromProject($expert_id, $room, $project) {
		$this->autoRender = false;
		$this->loadModel ( "Workroom" );
		$this->loadModel ( "Project" );
		$model = new Workroom ();
		$c = $this->Project->find ( "count", array (
				"conditions" => array (
						"user_id" => $this->Auth->user ( "id" ),
						"id" => $project 
				) 
		) );
		if ($c == 1) {
			
			$model->query("DELETE FROM workroom_experts WHERE expert='{$expert_id}' AND  room_id ='{$room}'    ") ;
			$model->query ( "DELETE FROM  job_bids WHERE project_id='{$project}'   AND user_id='{$expert_id}'  " );
		}
	}
	
 
	
	/*
	 *  Download Attached File :
	 *  download file by id from  chatroom  right sidebar :
	 *  2014@pashkovdenis@gmail.com
	 *
	 */
	public function downloadfile($file_id, $project_id) {
		
		$this->layout = false;
        $this->loadModel("Workroom") ;
        $model  =  new Workroom() ;
        $file_name =  $model->query("SELECT * FROM workroom_files WHERE id='{$file_id}' ")  ;
        $file  =  $file_name[0]["workroom_files"]["file"];
        $room_id  =  $file_name[0]["workroom_files"]["work_room"] ;

        $fullPath  =  "tmp/{$room_id}/" .$file ;
			
			if ($fd = fopen ( $fullPath, "r" )) {
				$fsize = filesize ( $fullPath );
				header ( 'Content-Description: File Transfer' );
				header ( "Content-type: application/force-download" );
				header ( 'Content-Disposition: attachment; filename=' . $file );
				header ( 'Content-Transfer-Encoding: Binary' );
				header ( 'Content-Type: application/octet-stream' );
				header ( 'Expires: 0' );
				header ( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
				header ( 'Pragma: public' );
				header ( 'Content-Length: ' . $fsize );
				// ob_clean();
				flush ();
				readfile ( $fullPath );
				exit ();
			} else {
				echo "File not Found";
			}
	 
		
		
		
		
	}
	


	/*
	 * Attach File into the  workroom
	 * 2014  :
	 *
	 */
 
	private function attachFile($tmp, $name, $room, $project_id) {
		$this->loadModel ( "Workroom" );
		$work = new Workroom ();
		$fullPath = str_replace ( '{project_id}', $project_id, PROJECT_BUSINESS_PLAN_PATH_FOLDER ) . str_replace ( " ", "_", $name );
		$file = str_replace ( " ", "_", $name );
	  	$namex= strtolower( array_shift ( explode ( ".", $file ) )) ;
		 parent::__upload ( $_FILES ["attach"], str_replace ( '{project_id}', $project_id, PROJECT_BUSINESS_PLAN_PATH_FOLDER ),$namex );
	  	$work->uploadFile ( $file, $this->Auth->user ( "id" ), $room );
		return $work->attach ; 
		
	}
	
	  
	
	
	/*
	 * ChatRooms :   
	 * pashkovdenis@gmail.com  
	 * 2014  
	 * 
	 */
 
	public function chatroom($user_id , $id=false ){ 
		
		$this->loadModel ( "Workroom" );
		$this->layout = 'workrooms';
		$workroom = new Workroom ();   
		$workroom->current_user  =  $this->Auth->user("id");
        $this->set("title_for_layout","Chatrooms ");
		 if (!$this->Auth->user ( "id" ) ||    empty($user_id) ||  $this->Auth->user("id")  ==  $user_id )
			$this->redirect ( "/" );
		 $user_id  =  (int)   $user_id ;
		  
		  
		  if (!$id){ 
		   	
	 	 $id  = $workroom->createPrivate($this->Auth->user("id"),  $user_id) ;   

	 	
	 	
	 	 
	 	 if (!isset($_SESSION["R".$id])){
	 	  		$this->Session->setFlash ( __ ( 'Chatroom created' ), 'default', array (
	 	 		"class" => "success"
	 	 ) ); 

	 	 	$_SESSION["R".$id] = 1 ;
	 	 }
		   
		   }
	 	    
	 	 
	 	 if (empty($id)){
	 	 	 $this->Session->setFlash ( __ ( 'There are no available Chatrooms ' ), 'default', array (
	 	 			"class" => "error"
	 	 	) ); 
	 	 	$this->redirect( "/Inbox/index") ;
	 	 }
	 	  
	 	 
	 	 
	 	 // Mark as Removed :   
	 	 if (isset($_POST["mark_removed"])){
	 	  
	 	 	
	 	 	
	 	  	 if  ($workroom->markChatroomRemoved($this->Auth->user("id"),$_POST["mark_removed"] )){
	 	  	 	$this->Session->setFlash ( __ ( ' Chatroom  Was Removed  ' ), 'default', array (
	 	  	 			"class" => "success"
	 	  	 	) ); 
	 	  	 }else{
	 	  	 	$this->Session->setFlash ( __ ( 'Unable  to remove' ), 'default', array (
	 	  	 			"class" => "error"
	 	  	 	) ); 
	 	  	 }
	 	 	 	$this->redirect( "/") ; // redirect to it 
	 	 }
	 	  
	 	 $workroom->loadWorkRoom ( $id ); 
		 	
	 	 // Check Access :  
	 	 if (!$this->hasAccess($id)){
	 	 	
	 	 	$this->Session->setFlash ( __ (  WORKROOM_DENIED ), 'default', array (
	 	 			"class" => "error"
	 	 	) );  
	 	 	$this->redirect("/");  
	 	 	
	 	 	
	 	 }
	 	 
	 	  
	 	 if ($workroom->removed_by != false  && $workroom->removed_by != 0 &&  $workroom->removed_by != $this->Auth->user("id") ){
	 	 	 $removed = ""; 
	 	 	 $this->loadModel("User");   
	 	 	 $user_model =  new User();   
	 	 	 $removed_user = $user_model->find("first", array("conditions"=>array("id"=> $workroom->removed_by   )));  
	 	 	 $removed = $removed_user["User"]["username"]; 
	 	 	 $this->set("removed",  $removed); 
	 	 	
	 	 }
	 	 
	 	 
		 if (isset ( $_POST ["chat"] )) {

          $attach='';
				if (isset ( $_FILES ["attach"] ["tmp_name"] )) {
	 			$attach = $this->attachFile ( $_FILES ["attach"] ["tmp_name"], $_FILES ["attach"] ["name"],  $id, $workroom->project_id );
	 			} 



	 		$workroom->postchat ( $this->Auth->user ( "id" ), $_POST ["chat"], $attach,  $this->Auth->user("id")  );
	 	 	//$this->redirect($this->here);
			$chat_id=explode('/',$this->here);
			//echo "/Workrooms/chatroom/{$chat_id[4]}";
			if(isset($chat_id[5]))
				$this->redirect ( "/Workrooms/chatroom/{$chat_id[4]}/{$chat_id[5]}" );
			else
				$this->redirect ( "/Workrooms/chatroom/{$chat_id[4]}" );
	 	} 
	 	 
		 
		  
	 	 $workroom = new Workroom ();
	 	 $workroom->loadWorkRoom ( $id );
	 	 $this->loadModel("UserDetail") ;  
	 	$workroom->current_user  =  $this->Auth->user("id"); 
   		  $this->loadModel("User") ; 
	 	 $this->User->bindModel(array('hasOne'=>array('UserDetail')),false); 
	 	 $me  = 		 $this->User->find("first" ,  array("conditions"=>array("User.id"=>$this->Auth->user("id"))));  
	 	 $other =     $this->User->find("first" ,  array("conditions"=>array("User.id"=>$user_id)));   
	 	 $this->set("me" ,  $me);
	 	 $this->set("to_user",   $other); 
	 	 
	 	 // Chat Room  title :  
		 $this->loadModel('User');
			$curr_user_id = parent::__get_session_user_id();
			
	 	 $title  = $me["User"]["username"]." @ ". $other["User"]["username"]; 
	 	 $this->set ( "title",$title );
	 	 $this->set ( "room", $id ); //
	 	 $this->set ( "date", $workroom->date );
	 	 $this->set ( "projectid", $workroom->project_id );
	 	 $this->set ( "projectimage", $workroom->project_image );
	 	 $this->set ( "files", $workroom->files );
	 	 $this->set ( "leader", $workroom->leader );
	 	 $this->set ( "user", $this->Auth->user ( "id" ) );
	 	 $this->set ( "experts", $workroom->experts );
	 	 $this->set ( "leaders", $workroom->leaders );
	 	 $this->set ( "chat", $workroom->chat );
	 	 $this->set ( "user", $this->Auth->user ( "id" ) );
	     $this->set ( "type", $workroom->type );
		 $this->set("curr_user_id1",$curr_user_id);
		 
	}
	
	
   	/*
   	 * 	 Single Workroom  are  
   	 *   pashkovdenis@gmail.com  
   	 */
	
	public function workroom($id , $teamup=false ){  
 
	  
		 $this->loadModel ( "Workroom" );
		 $this->loadModel("Job")  ; 
		 $this->loadModel("Project") ;  
		 
		 $this->layout = 'workrooms';
		 $workroom = new Workroom (); 
		 $paging = '';
			$workroom->current_user  =  $this->Auth->user("id");
		
		if (! $id)
			$this->redirect ( "/" );
			$workroom->loadWorkRoom ( $id ); 
	 
			if (! $workroom->hasUserAccess ( $id, $this->Auth->user ( "id" ) ))
			$this->redirect ( "/" );


        
  			if (!$workroom->existsWorkroom($id))
			$this->redirect ( "/" );

			$this->loadModel('User');
			$curr_user_id = parent::__get_session_user_id();
			
        
  		 	if (isset ( $_POST ["chat"] )) {
				$attach='';
				if (isset ( $_FILES ["attach"] ["tmp_name"] )) {
	 			$attach = $this->attachFile ( $_FILES ["attach"] ["tmp_name"], $_FILES ["attach"] ["name"],  $id, $workroom->project_id );
	 			} 
            $workroom->postchat ( $this->Auth->user ( "id" ), $_POST ["chat"], $attach , $this->Auth->user("id") );
			$this->redirect ( "/Workrooms/workroom/{$id}" );
		} 
		
		
			$this->isactive($id) ;  
		
		
		
	
		 $this->loadModel("Teamup");
		 $workroom = new Workroom (); 
		 $workroom->current_user  =  $this->Auth->user("id"); 
		 $workroom->is_project  =true ;
			 
		 $workroom->loadWorkRoom ( $id ); 
		 
		 // Check Access :
		 if (!$this->hasAccess($id)){
		 		
		 	$this->Session->setFlash ( __ (  WORKROOM_DENIED . " ". $id ), 'default', array (
		 			"class" => "error"
		 	) );
		 	$this->redirect("/");
		 		
		 		
		 }
		 
		 
		 $temp  =  new Teamup(); 
		 if ($workroom->removed_by != false  && $workroom->removed_by != 0 &&  $workroom->removed_by != $this->Auth->user("id") ){
		 	$removed = "";
		 	$this->loadModel("User");
		 	$user_model =  new User();
		 	$removed_user = $user_model->find("first", array("conditions"=>array("id"=> $workroom->removed_by   )));
		 	$removed = $removed_user["User"]["username"];
		 	$this->set("removed",  $removed);
		 		
		 }
		 
		 
		 // Mark as Removed :
		 if (isset($_POST["mark_removed"])){
		 	if  ($workroom->markChatroomRemoved($this->Auth->user("id"),$_POST["mark_removed"] )){
		 		$this->Session->setFlash ( __ ( ' Chatroom  Was Removed  ' ), 'default', array (
		 				"class" => "success"
		 		) );
		 	}else{
		 		$this->Session->setFlash ( __ ( 'Unable  to remove' ), 'default', array (
		 				"class" => "error"
		 		) );
		 	}
		 	$this->redirect("/Inbox/index") ;
		 }
		 // end Remove chatroom  :
		  
	 	 if  (isset($_POST["members"])){ 
	 	 	
		 	  foreach($_POST["members"] as $member){
		 	  	if ($this->Auth->user("id") == $workroom->leader)
		 	  	$workroom->addNewExpert($member, $id) ;
		 	  	else die("You are not Authorized For  This Action."); 
		 	   } 
		 	  $this->Session->setFlash ( __ ( ' Experts Was Added ' ), 'default', array (
		 	  		"class" => "success"
		 	  ) ); 
		  	   $this->redirect($_SERVER["REQUEST_URI"]);   
		  	   
		 }
		 
		 
		 
		 $this->set("type_room", $workroom->type ) ;

		 
		  
		 
		if ( $workroom->job_id  != "" && count($workroom->experts)){ 
			
			   
			$ex =  array_keys($workroom->experts) ;  


		  	$t = $temp->find(  "first" , array("conditions"=>array("job_id"=>$workroom->job_id, "to_user"=>$ex[0],  "user_id"=>$this->Auth->user("id"))))  ;  
			$t2 = $temp->find( "first" , array("conditions"=>array("job_id"=>$workroom->job_id,    "to_user"=>$this->Auth->user("id"))))  ;



			
			if ($t["Teamup"]["id"]!="") 
					$teamup = $t["Teamup"]["id"];  
			
			if ($t2["Teamup"]["id"]!="")
				$teamup = $t2["Teamup"]["id"];
		  
				
			
		} 
		


		 // Show   Teamup Stack   For   it  :   
		  if ($teamup!=false   &&  isset ( $_POST ["chat"] )==false )  {
		  	$t = $temp->find("first" , array("conditions"=>array("id"=>$teamup))) ; 
		  	
		  	 
			  	 $this->set("to_user2",   $t["Teamup"]["to_user"]) ; 
			   	 $first_time  =   $this->Session->read("team_".  $t["Teamup"]["to_user"].$workroom->job_id); 
			   	  
			   	 
			   	 if ( $t["Teamup"]["is_send"] != 1     ){
			   	 	
			 	   if ( $first_time =="" &&  $this->Auth->user("id") !=  $t["Teamup"]["to_user"] ){
			 	 	$this->set("show_team", true); 
			 	 	$this->Session->write("team_".  $t["Teamup"]["to_user"].$workroom->job_id, "1") ;
			 	   } 
			 	   
			 	   
			   	 }


			 	   
			 	   $this->set("teamup", $teamup);
			 
			 
			 
		  }
		 
		  
		 $title         =   $workroom->title ; 
	     $raw           =   $workroom->find("first",  array("conditions"=>array("id"=>$id))) ; 
		 $job_model     =  new Job() ; 
		 $project_model =  new Project() ; 
		 
 
		 	$job =  $job_model->find("first",  array("conditions"=>array("id"=> $raw["Workroom"]["job_id"] ))); 
		 	$project = $project_model->find("first", array("conditions"=>array("id"=>$raw["Workroom"]["project_id"]))); 
		 	$title = $job["Job"]["title"]. " @ " . $project["Project"]["title"]  ;
		 
 
		 $project_id =  $raw ["Workroom"]["project_id"];
		 if (empty($project_id)){
		 	$this->loadModel("Job") ;
		 	$job_model=  new Job() ;
		 	$job  = $job_model->find("first", array("conditions"=>array("id"=>$raw["Workroom"]["job_id"]))) ;
		 	if (!empty($job["Job"]["id"]))
		 		$project_id =  $job["Job"]["project_id"];
		 		
		 		
		 		
		 }
		   
		 $pmembers  =  $workroom->getMemberListForProject( $project_id) ;  
		 
		 foreach($pmembers as $ids => $username){
		 	
		 	$c= $workroom->query("SELECT COUNT(*) as c FROM  workroom_experts WHERE room_id='{$id}' AND expert='{$ids}' ");
		    if ($c[0][0]["c"] >  0)  
		    	unset($pmembers[$ids]); 
		      
		 	
		 	
		 }
		   
		 
		 	$this->set("pmembers",  $pmembers);
		 
		 $this->set ( "title", $title   );
		 $this->set ( "room",  $id ); //
		 $this->set ( "date", $workroom->date );
		 $this->set ( "projectid", $workroom->project_id );
		 $this->set ( "projectimage", $workroom->project_image );
		 $this->set ( "files", $workroom->files );
		 $this->set ( "leader", $workroom->leader );
		 $this->set ( "user", $this->Auth->user ( "id" ) );
		 $this->set ( "experts", $workroom->experts );
		 $this->set ( "leaders", $workroom->leaders );
		 $this->set ( "chat", $workroom->chat ); 
		 $this->set ( "job_id",  $workroom->job_id);  
		 $this->set ( "to_user",  $workroom->to_user); 
		 $this->set ( "user", $this->Auth->user ( "id" ) );
		 $this->set ( "type", $workroom->type );
	 	$this->set("curr_user_id1",$curr_user_id);
		
	}
	 
	 
	/*
	 * ProejctO  
	 * Workroom  of   the project :  
	 * 2014   
	 * pashkovdenis@gmail.com  
	 * rev 2
	 */


	public function projecto($id) {

        $this->loadModel ( "Workroom" );
		$this->layout = 'workrooms';
		$workroom = new Workroom (); 
		$workroom->current_user  =  $this->Auth->user("id");
		 
		$paging = '';
		if (! $id)
			$this->redirect ( "/" );
		if (! $workroom->hasUserAccess ( $id, $this->Auth->user ( "id" ) ))
			$this->redirect ( "/" );
		$workroom->loadWorkRoom ( $workroom->getWorkRoom ( $id ) );
		if (isset($_POST["mark_removed"])){
			if  ($workroom->markChatroomRemoved($this->Auth->user("id"),$_POST["mark_removed"] )){
				$this->Session->setFlash ( __ ( ' Chatroom  Was Removed  ' ), 'default', array (
						"class" => "success"
				) );
			}else{
				$this->Session->setFlash ( __ ( 'Unable  to remove' ), 'default', array (
						"class" => "error"
				) );
			}
			$this->redirect("/Inbox/index") ;
		}
		 $this->loadModel('User');
	 	$curr_user_id = parent::__get_session_user_id();
		
		if (isset ( $_POST ["chat"] )) {
			$attach='';
			if (isset ( $_FILES ["attach"] ["tmp_name"] )) {
	 			$attach = $this->attachFile ( $_FILES ["attach"] ["tmp_name"], $_FILES ["attach"] ["name"],  $id, $workroom->project_id );
	 		}
			
            $workroom->postchat ( $this->Auth->user ( "id" ), $_POST ["chat"] , $attach  ,  $this->Auth->user("id") );
			$this->redirect("/workrooms/projecto/{$id}");
		}
		$workroom = new Workroom ();
		$workroom->current_user  =  $this->Auth->user("id");
		$workroom->is_project = true ; 
		$workroom->loadWorkRoom ( $workroom->getWorkRoom ( $id ) );
		$this->isactive( $workroom->getWorkRoom ( $id ) ) ;
		
		// Check Access :


		if (!$this->hasAccess( $workroom->getWorkRoom ( $id ))){
				
			$this->Session->setFlash (   WORKROOM_DENIED , 'default', array (
					"class" => "error"
			) );
			$this->redirect("/");
				
				
		}
		 
		
		// Remove Workroom  
		if (isset($_POST["mark_removed"])){
			if  ($workroom->markChatroomRemoved($this->Auth->user("id"),$_POST["mark_removed"] )){
				$this->Session->setFlash ( __ ( ' Chatroom  Was Removed  ' ), 'default', array (
						"class" => "success"
				) );
			}else{
				$this->Session->setFlash ( __ ( 'Unable  to remove' ), 'default', array (
						"class" => "error"
				) );
			}
			$this->redirect("/Inbox/index") ;
		}
		 
		
		 
		if  (isset($_POST["members"])){
				
			foreach($_POST["members"] as $member){
				if ($this->Auth->user("id") == $workroom->leader)
					$workroom->addNewExpert($member,  $workroom->getWorkRoom ( $id ) ,$id) ;
				else die("You are not Authorized For  This Action.");
			}
			$this->Session->setFlash ( __ ( ' Experts Was Added to Project Workroom ' ), 'default', array (
					"class" => "success"
			) );
			$this->redirect($_SERVER["REQUEST_URI"]);
		
		}
		

		$raw           =   $workroom->find("first",  array("conditions"=>array("id"=> $workroom->getWorkRoom ( $id )))) ;
		 
	 
		$project_id =  $raw ["Workroom"]["project_id"];  
		if (empty($project_id)){
			$this->loadModel("Job") ; 
			$job_model=  new Job() ; 
			$job  = $job_model->find("first", array("conditions"=>array("id"=>$raw["Workroom"]["job_id"]))) ;  
			if (!empty($job["Job"]["id"]))	
				$project_id =  $job["Job"]["project_id"];  
			
			
			
		}
		
		 
		
		
		if ($raw["Workroom"]["job_id"]!="")
		$this->set("pmembers", $workroom->getMemberListForProject( $project_id ,$raw["Workroom"]["job_id"] ));
		else 
			$this->set("pmembers", $workroom->getMemberListForProject( $project_id ));
				 
		 
		$title         =   $workroom->title ;

		$this->loadModel("Job");
		$this->loadModel("Project") ;  
		 
		if ($workroom->removed_by != false  && $workroom->removed_by != 0 &&  $workroom->removed_by != $this->Auth->user("id") ){
			$removed = "";
			$this->loadModel("User");
			$user_model =  new User();
			$removed_user = $user_model->find("first", array("conditions"=>array("id"=> $workroom->removed_by   )));
			$removed = $removed_user["User"]["username"];
			$this->set("removed",  $removed);
			 
		}
		
		$job_model     =  new Job() ;
		$project_model =  new Project() ;
		 
			$job =  $job_model->find("first",  array("conditions"=>array("id"=> $raw["Workroom"]["job_id"] )));
	
		 
			
			$project = $project_model->find("first", array("conditions"=>array("id"=>$raw["Workroom"]["project_id"])));
			$title =  $project["Project"]["title"]  ; 
	 
		 
		$this->set ( "title", $title);
		$this->set ( "room", $workroom->getWorkRoom ( $id ) ); //
		$this->set ( "date", $workroom->date );
		$this->set ( "projectid", $workroom->project_id );
		$this->set ( "projectimage", $workroom->project_image );
		$this->set ( "files", $workroom->files );
		$this->set ( "leader", $workroom->leader );
		$this->set ( "user", $this->Auth->user ( "id" ) );
		$this->set ( "experts", $workroom->experts );
		$this->set ( "leaders", $workroom->leaders );
		$this->set ( "chat", $workroom->chat );
		$this->set ( "user", $this->Auth->user ( "id" ) );
		
		$this->set ( "type", $workroom->type );
	}
	
	// laod more chatAction :
	// 2013  
	// LoadMore Action    
	public function loadmore($last, $room) {
		$this->autoRender = false;
		$this->loadModel ( "Workroom" );
		$workroom = new Workroom ();
		$workroom->loadWorkRoom ( $room );
		if ($this->Auth->user ( "id" )) {
			$chat = "";
			$chat_array = $workroom->loadChat ( $last );
			
			foreach ( $chat_array as $c ) {
				$chat .= '
 	   	  	<tr class="even"  id="' . $c->id . '">
 	   	  	<td align="left" valign="top"><span class="blue">' . $c->user . ':</span></td>
 	   	  						<td align="left" valign="top"><p> ' . $c->text . '</a>
 	   	  							</p></td>
 	   	  						<td align="left" valign="top"><code> ' . $c->ago . '</code></td>
 	   	  					</tr> ';
			}
			
			echo $chat;
		}
	} 
	
	
	
	
	/*
	 * Set Empty  Room  :   
	 * 
	 * 
	 */
	
	public function emptyroom($type)  {  
		$this->layout = 'workrooms';
		if ($type==1)
		$this->set("error", "There are no available Chatrooms");
		if ($type==2)
		 $this->set("error", "There are no available Workrooms");
	 }
	



    /*
     * chat room  ,  workroom  ,  attach   file
     * pashkovdenis@gmail.com
     * 2014
     */



    public function chatattach($room_id=null){

        $this->autoRender  =  false;
        $this->loadModel("Workroom") ;
        $model =  new Workroom();
        $base_path =  "tmp/".$room_id."/" ;
        @mkdir($base_path) ;
        @chmod($base_path, 0755);

        if ($room_id){
             if (isset ( $this->request->data ["JobFileTemp"] ['job_file'] ['tmp_name'] )) {
                 if (! empty ( $this->request->data ["JobFileTemp"] ['job_file'] ['tmp_name'] )) {
                    $file_array = $this->request->data ["JobFileTemp"] ['job_file'];
                    $this->request->data ["JobFileTemp"] ['job_file'] = $this->request->data ["JobFileTemp"] ['job_file'] ['name'];
                }
			
             if (! empty ( $file_array )) {
                    $n = explode ( ".", $this->request->data ["JobFileTemp"] ['job_file'] );
                    $file_name = array_shift ( $n );
                    $file_name = str_replace ( " ", "_", $file_name );
                   $filename = parent::__upload ( $file_array, $base_path, $file_name );
                }
				
            }
			
             $model->query("INSERT INTO workroom_chat_files SET record='0' , attach='{$filename}' , room_id='{$room_id}' ,  user_id = '".$this->Auth->user("id")."'   ");
             $las = $model->query("SELECT id FROM workroom_chat_files ORDER BY id DESC LIMIT 1");
             $lastInsert_id=   $las[0]["workroom_chat_files"]["id"];
            echo "success|" . $filename . "|" . $lastInsert_id;

        }


    }




    /*
     * Public  Remove File From  Chat
     * pashkovdenis@gmail.com
     *
     *
     */
     public function removechatfile($file_id){
        $this->autoRender  =  false;
        $this->loadModel("Workroom") ;
        $model =  new Workroom();
        $model->query("DELETE FROM  workroom_chat_files WHERE id='{$file_id}' AND user_id  ='".$this->Auth->user("id")."' ");
        echo "file _remoed " ;
    }

	
	
	
	
	
	
	
}