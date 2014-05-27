<?php
/*
 * Inbox Controller For Users pashkovdenis@gmail.com rommie 2013
 */
class InboxController extends AppController {
	var $name = 'Inbox';
	public $helpers = array (
			'General',
			'Html' 
	);
	public function beforeFilter() {
		parent::beforeFilter ();
		$this->loadModel ( "Inbox" );
	}
	
	// index List For inbox
	public function index($type = null) {
		$user = $this->Auth->user ( "id" );
		if (empty ( $user ))
			$this->redirect ( "/" );
		
		$this->loadModel ( "Workroom" );
		$work = new Workroom ();
		
		// Redirect if latest Workroom Exists :
		
		if ($type == 1) {
			$work->query ( "DELETE FROM chat_logs WHERE user_id = '{$user}' AND room_type=0  " );
			$url = $work->getLatestChatRoom ( $this->Auth->user ( "id" ) );
			if (! empty ( $url ))
				$this->redirect ( $url );
			else{
                $this->Session->setFlash ( __ ( 'There are no available Chatrooms' ), 'default', array (
                    "class" => "error"
                ) );
                $this->redirect("/workrooms/emptyroom/1") ;
            }

			 
		}
		
		if ($type == 2) {
			$work->query ( "DELETE FROM chat_logs WHERE user_id = '{$user}' AND  (room_type=2 OR room_type=1) " );
			$url = $work->getLatestWorkRoom ( $this->Auth->user ( "id" ) );
			if (! empty ( $url ))
				$this->redirect ( $url );
			else{
                $this->Session->setFlash ( __ ( 'There are no available Workrooms' ), 'default', array (
                    "class" => "error"
                ) );
                $this->redirect("/workrooms/emptyroom/2") ;
            }

		}
		
		$this->set ( "inbox", $this->Inbox->getInbox ( $user, $type ) );
		$this->layout = 'workrooms';
	}
}
 