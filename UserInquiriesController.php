<?php
/**
 * UserInquiries Controller
 *
 * PHP version 5.4
 *
 */
class UserInquiriesController extends AppController {
	/**
	 * UserInquiries name
	 *
	 * @var string
	 * @access public
	 */
	var	$name	=	'UserInquiries';
	var	$uses	=	array('UserInquiry');
	var $helpers = array('Html','General');
	var $model='UserInquiry';
	var $controller='user_inquiries';
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();

		$this->set('controller',$this->controller);
		$this->set('model',$this->model);

	}


	public function admin_index($defaultTab='All',$project_id=null) {
		$model=$this->model;
		$controller=$this->controller;
		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}
		$filters =  array();

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

		$search_flag=0;
		if($this->Session->check('AdminSearch')){
			$keywords  = $this->Session->read('AdminSearch');
				
			foreach($keywords as $key=>$values){
				$filters[] = array(''.$model.'.'.$key.' LIKE'=>"%".$values."%");
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
		$this->set(compact('data','project_id'));
		$this->set('title_for_layout',  __("$model", true));

		if(isset($this->request->params['named']['page']))
		$this->Session->write('Url.page', $this->request->params['named']['page']);
		if(isset($this->request->params['named']['sort']))
		$this->Session->write('Url.sort', $this->request->params['named']['sort']);
		if(isset($this->request->params['named']['direction']))
		$this->Session->write('Url.direction', $this->request->params['named']['direction']);
		$this->Session->write('Url.defaultTab', $defaultTab);

		if($this->request->is('ajax')){
			$this->render('ajax/admin_index');
		}else{
				
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
	/**
	 * edit existing Agreement
	 */
  

}