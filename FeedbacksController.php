<?php
/**
 * Feedbacks Controller
 *
 * PHP version 5.4
 *
 */
class FeedbacksController extends AppController {
	/**
	 * SliderVisibilities name
	 *
	 * @var string
	 * @access public
	 */
	var	$name	=	'Feedbacks';
	var	$uses	=	array('Feedback');
	var $helpers = array('Html','General');
	var $components = array('General');
	var $model='Feedback';
	var $controller='feedbacks';
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->set('controller',$this->controller);
		$this->set('model',$this->model);
		$this->Auth->allow('feedback');
	}


	public function admin_index($defaultTab='All') {
		$model=$this->model;
		$controller=$this->controller;

		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}
		$filters_without_status = $filters = array();

		if(!empty($this->request->data)){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
				
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data[''.$model.'']['name'])){
				$name = Sanitize::escape($this->request->data[''.$model.'']['name']);
				$this->Session->write('AdminSearch.name', $name);
			}
				
			if(!empty($this->request->data[''.$model.'']['email'])){
				$email = Sanitize::escape($this->request->data[''.$model.'']['email']);
				$this->Session->write('AdminSearch.email', $email);
			}
		}

		$search_flag=0;	$search_status='';
		if($this->Session->check('AdminSearch')){
			$keywords  = $this->Session->read('AdminSearch');
				
			foreach($keywords as $key=>$values){
				$filters[] = array(''.$model.'.'.$key.' LIKE'=>"%".$values."%");
				$filters_without_status[] = array(''.$model.'.'.$key.' LIKE'=>"%".$values."%");
			}
				
			$search_flag=1;
		}
		$this->set(compact('search_flag','defaultTab'));
		$this->paginate = array(
			''.$model.''=>array(
				'limit'=>Configure::read('App.AdminPageLimit'), 
				'order'=>array(''.$model.'.id'=>'desc'),
				'conditions'=>$filters,
				'recursive'=>1
		)
		);

		$data = $this->paginate(''.$model.'');

		$this->set(compact('data'));
		$this->set('title_for_layout',  __(''.$model.'s', true));

		if(isset($this->request->params['named']['page']))
		$this->Session->write('Url.page', $this->request->params['named']['page']);
		if(isset($this->request->params['named']['sort']))
		$this->Session->write('Url.sort', $this->request->params['named']['sort']);
		if(isset($this->request->params['named']['direction']))
		$this->Session->write('Url.direction', $this->request->params['named']['direction']);
		$this->Session->write('Url.defaultTab', $defaultTab);

		if($this->request->is('ajax')){
			$this->render('ajax/admin_index');
		}
		else
		{
				
			$tabs = array('All'=>count($data));
			$this->set(compact('tabs'));
		}
	}

	public function referer($default = NULL, $local = false)
	{
		$defaultTab = $this->Session->read('Url.defaultTab');
		$Page = $this->Session->read('Url.Page');
		$sort = $this->Session->read('Url.sort');
		$direction = $this->Session->read('Url.direction');

		return Router::url(array('action'=>'index', $defaultTab,'Page'=>$Page,'sort'=>$sort,'direction'=>$direction),true);
	}


	/*delete feedbacks*/
	public function admin_delete($id = null) {
		$model=$this->model;
		$controller=$this->controller;

		$this->$model->id = $id;
		if (!$this->$model->exists()) {
			throw new NotFoundException(__('Invalid '.$model.''));
		}
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}
		if ($this->$model->delete()) {
			$this->Session->setFlash(__(''.$model.' deleted successfully'), 'admin_flash_success');
			$this->redirect($this->referer());
		}
		$this->Session->setFlash(__(''.$model.' was not deleted', 'admin_flash_error'));
		$this->redirect($this->referer());
	}

	/*delete selected process*/
	public function admin_process(){
		$model=$this->model;
		$controller=$this->controller;
		if (!$this->request->is('post')) {
			throw new MethodNotAllowedException();
		}

		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}

		if(!empty($this->request->data)){
			App::uses('Sanitize', 'Utility');
			$action = Sanitize::escape($this->request->data[''.$model.'']['pageAction']);

			$ids = $this->request->data[''.$model.'']['id'];
				
			if (count($this->request->data) == 0 || $this->request->data[''.$model.''] == null) {
				$this->Session->setFlash('No items selected.', 'admin_flash_error');
				$this->redirect($this->referer());
			}
				
			if($action == "delete"){
				$this->$model->deleteAll(array(''.$model.'.id'=>$ids));
				$this->Session->setFlash(''.$model.' have been deleted successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
		}
		else{
			$this->redirect(array('controller'=>''.$controllers.'', 'action'=>'index'));
		}
	}


	/*
	 * Add new Agreement
	 */
	public function feedback() {
		
		if($this->RequestHandler->isAjax())
		{	
			$this->autoRender	= false;
			$this->layout= false;
			$this->loadModel('User');
			$this->loadModel('Template');
			
			if ($this->request->is('post')) {
					
				if(!empty($this->request->data)) {
				
			
					if ($this->Feedback->save($this->request->data))
					{
					$this->loadModel("Settings") ; 
					$email =  $this->Settings->find("first" , array("conditions"=>array("name"=>"site_email")));  
				  	$user_details = $this->User->find('first',array('conditions'=>array( "User.role_id !="=>Configure::read('App.Role.Admin'))));
			 
			 
					if(isset($user_details) && !empty($user_details))
					{
					   
						$first_name=$user_details['User']["first_name"];
					    $from = Configure::read('Site.title')." <".Configure::read('App.AdminMail').">";
						$mailMessage='';
						$feedback = $this->Template->find('first', array('conditions' => array('Template.slug' => 'feedback')));
						$email_subject =$feedback['Template']['subject'];
						$subject = '['.Configure::read('Site.title').']'. $email_subject;
					 	$mailMessage = str_replace(array('{NAME}','{FEEDBACK}'), array(  $this->request->data['name']. " " .$this->request->data['email'] ,$this->request->data['message']),$feedback['Template']['content']);
						$this->sendMail( User::getAdminEmail(),$subject,$mailMessage,$this->request->data["email"],'general');
					  	echo('success');
						die;
							
					}

				}
					
			}
		}
	}

}
}