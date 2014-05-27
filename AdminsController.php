<?php
/**
 * Admins Controller
 *
 * PHP version 5.4
 *
 */
class AdminsController extends AppController {
	/**
	 * Controller name
	 *
	 * @var string
	 * @access public
	 */
	var	$name	=	'Admins';
	
	var $uses = array('User');
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();
		 
	}

	/*
	 * Dashboard
	 */
	public function admin_dashboard() {
		$this->set('title_for_layout',  __('Dashboard', true));


			
	}


	/*
	 * Admin Login
	 * auth magic
	 */
	public function admin_login() {

		if ($this->Auth->login()) {
			$this->redirect($this->Auth->redirect());
		} else {
			if($this->request->is('post')){
				$this->Session->setFlash(__('Invalid username or password, try again'));
			}
		}
		$this->layout = 'admin_login';
	}

	/*
	 * Admin Logout
	 * auth magic
	 */
	public function admin_logout() {
		$this->redirect($this->Auth->logout());

	}
	/**
	 * toggle featrued existing user
	 */
	public function admin_feature($id = null) {

		$this->User->id = $id;

		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
			
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}
		if ($this->User->toggleFeatured($id)) {
			$this->Session->setFlash(__('Admin\'s featured has been changed'), 'admin_flash_success');
			$this->redirect($this->referer());
		}
		$this->Session->setFlash(__('Admin\'s featured was not changed', 'User_flash_error'));
		$this->redirect($this->referer());
	}
	/**
	 * delete existing user
	 */
	public function admin_delete($id = null) {
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}
		if ($this->User->delete()) {
			$this->Session->setFlash(__('Admin deleted successfully'), 'admin_flash_success');
			$this->redirect($this->referer());
		}
		$this->Session->setFlash(__('Admin was not deleted', 'admin_flash_error'));
		$this->redirect($this->referer());
	}

	/*
	 * List all admins in admin panel
	 */
	public function admin_index($role='Admin', $defaultTab='All') {
		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}

		$filters_without_status = $filters = array('User.role_id'=>array(Configure::read('App.Role.'.ucfirst($role)),Configure::read('App.Role.SubAdmin')));

		if($defaultTab!='All'){
			$filters[] = array('User.status'=>array_search($defaultTab, Configure::read('Status')));
		}

		if(!empty($this->request->data)){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
				
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data['User']['email'])){
				$email = Sanitize::escape($this->request->data['User']['email']);
				$this->Session->write('AdminSearch.email', $email);
			}
			if(!empty($this->request->data['User']['username'])){
				$username = Sanitize::escape($this->request->data['User']['username']);
				$this->Session->write('AdminSearch.username', $username);
			}
				
			if(isset($this->request->data['User']['status']) && $this->request->data['User']['status']!=''){
				$status = Sanitize::escape($this->request->data['User']['status']);
				$this->Session->write('AdminSearch.status', $status);
				$defaultTab = Configure::read('Status.'.$status);
			}
		}

		$search_flag=0;	$search_status='';
		if($this->Session->check('AdminSearch')){
			$keywords  = $this->Session->read('AdminSearch');

			foreach($keywords as $key=>$values){
				if($key == 'status'){
					$search_status=$values;
					$filters[] = array('User.'.$key =>$values);
				}
				if($key == 'email'){
					$filters[] = array('User.'.$key =>$values);
					$filters_without_status[] = array('User.'.$key =>$values);
				}
				if($key == 'username'){
					$filters[] = array('User.'.$key.' LIKE'=>"%".$values."%");
					$filters_without_status[] = array('User.'.$key.' LIKE'=>"%".$values."%");
				}

			}
				
			$search_flag=1;
		}
		$this->set(compact('search_flag','defaultTab'));

		//pr($filters); die;
		$this->paginate = array(
								'User'=>array(	
									'limit'=>Configure::read('App.UserPageLimit'), 
									'order'=>array('User.created'=>'ASC'),
									'conditions'=>$filters
		));

		$data = $this->paginate('User');
		//pr($data); die;
		$this->set(compact('data', 'role'));
		$this->set('title_for_layout',  __('Admins', true));


		if(isset($this->request->params['named']['page']))
		$this->Session->write('Url.page', $this->request->params['named']['page']);
		if(isset($this->request->params['named']['sort']))
		$this->Session->write('Url.sort', $this->request->params['named']['sort']);
		if(isset($this->request->params['named']['direction']))
		$this->Session->write('Url.direction', $this->request->params['named']['direction']);
		$this->Session->write('Url.type', $role);
		$this->Session->write('Url.defaultTab', $defaultTab);

		if($this->request->is('ajax')){
			$this->render('ajax/admin_index');
		}else{
			$active=0;$inactive=0;
				
				
				
			if($search_status=='' || $search_status==Configure::read('App.Status.active')){
				$temp=$filters_without_status;
				$temp[] = array('User.status'=>1,$filters);
				$active = $this->User->find('count',array('conditions'=>$temp));
			}
			if($search_status=='' || $search_status==Configure::read('App.Status.inactive')){
				$temp=$filters_without_status;
				$temp[] = array('User.status'=>0,$filters);
				$inactive = $this->User->find('count',array('conditions'=>$temp));
			}
				
			//pr($temp); die;
			$tabs = array('All'=>$active+$inactive, 'Active'=>$active,'Inactive'=>$inactive);
			$this->set(compact('tabs'));

			//pr($defaultTab);die;
		}

	}


	/*
	 * Add new user
	 */
	public function admin_add() {
		//$this->redirect($this->referer());
		$this->loadModel('Template');
		if ($this->request->is('post')) {
				
			if(!empty($this->request->data)) {
					
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}

				$this->User->set($this->request->data);
				$this->User->setValidation('User');
				if ($this->User->validates()) {
					$this->request->data['User']['password'] = Security::hash($this->request->data['User']['password2'], null, true);
					$this->request->data['User']['role_id'] = Configure::read('App.Role.SubAdmin');
					/* send email confirmation link to user*/
					/* $AdminRegistration = $this->Template->find('first', array('conditions' => array('Template.slug' => 'user_registration')));
						$email_subject = $AdminRegistration['Template']['subject'];
						$subject = __('[' . Configure::read('Site.title') . '] ' .
						$email_subject . '', true);

						$mailMessage = str_replace(array('{NAME}','{USERNAME}','{SITE}','{ACTIVATION_LINK}'), array($this->request->data['Admin']['first_name'],$this->request->data['Admin']['username'], Configure::read('Site.title'),$url), $AdminRegistration['Template']['content']);

						if($this->sendMail($this->request->data["Admin"]["email"],
						$subject,$mailMessage,array(Configure::read('App.AdminMail')=>Configure::read('Site.title')), $AdminRegistration['Template']['id'])){
						$this->Session->setFlash(__('Admin has been saved successfully'), 'admin_flash_success');
							
						}else{
						$this->Session->setFlash('Congrates! You have succesfully registered on '.Configure::read('Site.title').'.','admin_flash_success');
						}
						*/
					if ($this->User->saveAll($this->request->data)) {
						/* send email confirmation link to user*/
						/* $AdminRegistration = $this->Template->find('first', array('conditions' => array('Template.slug' => 'user_registration')));
						 $email_subject = $AdminRegistration['Template']['subject'];
						 $subject = __('[' . Configure::read('Site.title') . '] ' .
						 $email_subject . '', true);

						 $mailMessage = str_replace(array('{NAME}','{USERNAME}','{SITE}','{ACTIVATION_LINK}'), array($this->request->data['Admin']['first_name'],$this->request->data['Admin']['username'], Configure::read('Site.title'),$url), $AdminRegistration['Template']['content']);
						 */
						/* if($this->sendMail($this->request->data["Admin"]["email"],
							$subject,$mailMessage,array(Configure::read('App.AdminMail')=>Configure::read('Site.title')), $AdminRegistration['Template']['id'])){
							$this->Session->setFlash(__('Admin has been saved successfully'), 'admin_flash_success');
								
							}else{
							$this->Session->setFlash('Congrates! You have succesfully registered on '.Configure::read('Site.title').'.','admin_flash_success');
							} */
						$this->Session->setFlash(__('Admin has been saved successfully'), 'admin_flash_success');
						$this->redirect(array('action'=>'index','Admin'));
					} else {
						$this->Session->setFlash(__('The Admin could not be saved. Please, try again.'), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash('The Admin could not be saved.  Please, correct errors.', 'admin_flash_error');
				}
			}
		}


	}

	
	/*
	 * LinkIn Id  Status :  
	 * 2013 
	 */
	
	public function admin_linkid(){
		$this->loadModel("User"); 
		$users  =  $this->User->query("SELECT * FROM users WHERE linkid <>''   ");
		$this->set("users",  $users); 
		
		if (isset($_POST["approve"])){
		 
			foreach($_POST["approve"] as $id => $v){
				$this->User->query("UPDATE users SET linkid_confirmed = 1 WHERE id='{$id}' ");
			 } 
		 
			 $this->redirect("/admins/linkid") ;
		}
		
		
		
	}

 
	public function admin_edit($id = null) {
		$this->User->id = $id;
		$role_id = $this->User->field('role_id');
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}

		if ($this->request->is('post') || $this->request->is('put')) {
				
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				//validate user data
				$this->User->set($this->request->data);
				
				$this->User->setValidation('admin');
				if ($this->User->validates()) {
					if ($this->User->save($this->request->data)) {
						$this->Session->setFlash(__('The admin information has been updated successfully',true), 'admin_flash_success');
						if($this->request->data['User']['id'] == $this->Auth->user('id'))
						$this->redirect('dashboard');
						else
						$this->redirect(array('action' => 'index','Admin'));
					} else {
						$this->Session->setFlash(__('The Admin could not be saved. Please, try again.',true), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash(__('The Admin could not be saved. Please, correct errors.', true), 'admin_flash_error');
				}
			}
		}
		else {
			$this->request->data = $this->User->read(null, $id);
			unset($this->request->data['User']['password']);
		}
		$this->set('role_id',$role_id);
	}


	public function admin_process(){

		if (!$this->request->is('post')) {
			throw new MethodNotAllowedException();
		}

		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}

		if(!empty($this->request->data)){
			App::uses('Sanitize', 'Utility');
			$action = Sanitize::escape($this->request->data['User']['pageAction']);

			$ids = $this->request->data['User']['id'];
				
				
			if (count($this->request->data) == 0 || $this->request->data['User'] == null) {
				$this->Session->setFlash('No items selected.', 'admin_flash_error');
				$this->redirect($this->referer());
			}
				
			if($action == "delete"){
				$this->User->deleteAll(array('User.id'=>$ids));

				$this->loadModel('Template');


				$this->Session->setFlash('Admins have been deleted successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
				
			if($action == "activate"){
				$this->User->updateAll(array('User.status'=>Configure::read('App.Status.active')),array('User.id'=>$ids));
				/* $this->loadModel('Template');
				 foreach($ids as $id){
					$user_info=$this->Admin->find('first',array('conditions'=>array('Admin.id'=>$id)));
						
					$change_status = $this->Template->find('first', array('conditions' => array('Template.slug' => 'user_status_change')));
					$email_subject = $change_status['Template']['subject'];
					$subject = __('[' . Configure::read('Site.title') . '] ' .
					$email_subject . '', true);
						
					$mailMessage = str_replace(array('{NAME}','{STATUS}'), array($user_info['Admin']['first_name'].' '.$user_info['Admin']['last_name'],($user_info['Admin']['status']==1)?'Active':'InActive'), $change_status['Template']['content']);
						
					$this->sendMail($user_info["Admin"]["email"],$subject,$mailMessage,array(Configure::read('App.AdminMail')=>Configure::read('Site.title')),$change_status['Template']['id']);
					} */

				$this->Session->setFlash('Admins have been activated successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
				
			if($action == "deactivate"){
					
				$this->User->updateAll(array('User.status'=>Configure::read('App.Status.inactive')),array('User.id'=>$ids));
				/* $this->loadModel('Template');

				foreach($ids as $id){
				$user_info=$this->Admin->find('first',array('conditions'=>array('Admin.id'=>$id)));
					
				$change_status = $this->Template->find('first', array('conditions' => array('Template.slug' => 'user_status_change')));
				$email_subject = $change_status['Template']['subject'];
				$subject = __('[' . Configure::read('Site.title') . '] ' .
				$email_subject . '', true);
					
				$mailMessage = str_replace(array('{NAME}','{STATUS}'), array($user_info['Admin']['first_name'].' '.$user_info['Admin']['last_name'],($user_info['Admin']['status']==1)?'Active':'Inactive'), $change_status['Template']['content']);
					
				$this->sendMail($user_info["Admin"]["email"],$subject,$mailMessage,array(Configure::read('App.AdminMail')=>Configure::read('Site.title')),$change_status['Template']['id']);
				} */

				$this->Session->setFlash('Admins have been deactivated successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
		}
		else{
			$this->redirect(array('controller'=>'admins', 'action'=>'index', 'User'));
		}
	}
	/*
	 * View existing user
	 */
	public function admin_view($id = null) {
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid Admin'));
		}

		$this->set('user', $this->User->read(null, $id));
	}
	/**
	 * toggle status existing user
	 */
	public function admin_status($id = null) {

		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}
		$this->loadModel('Template');
		$this->loadModel('Admin');
		if ($this->User->toggleStatus($id)) {
			$user_info=$this->User->find('first',array('conditions'=>array('User.id'=>$id)));
				
			$change_status = $this->Template->find('first', array('conditions' => array('Template.slug' => 'user_status_change')));
			$email_subject = $change_status['Template']['subject'];
			$subject = __('[' . Configure::read('Site.title') . '] ' .
			$email_subject . '', true);
				
			$mailMessage = str_replace(array('{NAME}','{STATUS}'), array($user_info['User']['first_name'].' '.$user_info['User']['last_name'],($user_info['User']['status']==1)?'Active':'InActive'), $change_status['Template']['content']);
				
			$this->sendMail($user_info["User"]["email"],$subject,$mailMessage,array(Configure::read('App.AdminMail')=>Configure::read('Site.title')),$change_status['Template']['id']);
				
			$this->Session->setFlash(__('Admin\'s status has been changed'), 'admin_flash_success');
			$this->redirect($this->referer());
		}
		$this->Session->setFlash(__('Admin\'s status was not changed', 'admin_flash_error'));
		$this->redirect($this->referer());
	}

	/* public function get_users($type,$fields='*',$cond=array(),$order='Admin.id desc',$limit=999,$offset=0){
		$users=$this->find($type,array('conditions'=>array('Admin.status'=>Configure::read('App.Status.active'),$cond),'fields'=>array($fields),'order'=>array($order),'offset'=>$offset,'limit'=>$limit));

		return $users;
		} */
	/**
	 * Change Password
	 */
	public function admin_change_password($id = null) {
		//die("hii");
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}


		/* if($id == $this->Auth->user('id'))
			$this->redirect(array('action'=>'admin_index','admin')); */

		if ($this->request->is('post') || $this->request->is('put')) {
				
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}


				//validate user data
				$this->User->set($this->request->data);
				$this->User->setValidation('change_password');
				if ($this->User->validates()) {
					$this->request->data['User']['password'] = Security::hash($this->request->data['User']['new_password'], null, true);
					if ($this->User->saveAll($this->request->data)) {

						$new_password = $this->request->data['User']['new_password'];

						$user_pass_reset=$this->User->find('first',array('conditions'=>array('User.id'=>$this->request->data["User"]["id"])));


						$this->loadModel('Template');
						$change_password = $this->Template->find('first', array('conditions' => array('Template.slug' => 'change_password')));
						$email_subject = $change_password['Template']['subject'];
						$subject = __('[' . Configure::read('Site.title') . '] ' .
						$email_subject . '', true);
							
						$mailMessage = str_replace(array('{NAME}','{SITE}','{PASSWORD}'), array($user_pass_reset['User']['first_name'].' '.$user_pass_reset['User']['last_name'],Configure::read('Site.title'),$new_password), $change_password['Template']['content']);
							
						if($this->sendMail($user_pass_reset["User"]["email"],
						$subject,$mailMessage,array(Configure::read('App.AdminMail')=>Configure::read('Site.title')),'general')){
							$this->Session->setFlash('Your Password has been changed. Your New Password detail has been sent to your email address.','admin_flash_success');
						}else{
							$this->Session->setFlash('Your Password has been changed. Your New Password detail has not been sent to your email address.','admin_flash_error');
						}


						$this->redirect(array('controller'=>'admins','action'=>'admin_index','admin'));
					}
					else
					{
						$this->Session->setFlash(__('The Password could not be changed. Please, try again.',true), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash(__('The Password could not be changed. Please, correct errors.', true), 'admin_flash_error');
				}
			}
		}
		else {

			$this->request->data = $this->User->read(null, $id);

			unset($this->request->data['User']['password']);
		}

	}

	
	
	
	
	
	
	
	
	
	/*
	 * Collaberation Types  goes here   
	 * pashkovdenis@gmail.com   
	 * 2014  
	 * 
	 * 
	 */   
	
	public function admin_clb(){ 
	 	$this->loadModel("Colloberation") ;  
		$model  = new Colloberation()  ;  
 		if (isset($_POST["remove"])) 
			 $model->query("DELETE FROM clb WHERE id IN  (".join(",",$_POST["remove"]).") ") ;
		 $this->set("list",  $model->getAll()); 
 	}
	
	
	/*
	 * Create new Record    
	 * 
	 */
	
	public function admin_clb_create(){ 
		$this->loadModel("Colloberation") ;
		$model  = new Colloberation()  ; 
		
		if (isset($_POST["title"])){
			$model->saveAll($_POST) ;  
		 	$this->Session->setFlash( 'Done ' ,'admin_flash_success'); 
			$this->redirect("/admin/admins/clb") ; 
		}
		
		
	}
	 
	
	/*
	 * Edit Single Record    
	 * pashkovdenis@gmail.com   
	 * 2014    
	 * 
	 */
	public function admin_clb_edit($id){ 
		$this->loadModel("Colloberation") ;
		$model  = new Colloberation()  ; 
		$this->set("r",  $model->loadSingle($id));  
		
		if (isset($_POST["title"])){ 
			
		 
			 
			$model->saveAll($_POST) ;
			$this->Session->setFlash( 'saved   ' ,'admin_flash_success');
			$this->redirect("/admin/admins/clb") ;
		}
		
		
	}
	
	
	
	
	
	
	
	
	
	

}