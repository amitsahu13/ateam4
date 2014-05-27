<?php
/**
 * SecurityQuestions Controller
 *
 * PHP version 5.4
 *
 */
class SecurityQuestionsController extends AppController {
	/**
	 * SecurityQuestions name
	 *
	 * @var string
	 * @access public
	 */
	var	$name	=	'SecurityQuestions';
	var	$uses	=	array('SecurityQuestion');
	var $helpers = array('Html','General');
	var $model='SecurityQuestion';
	var $controller='security_questions';
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();

		$this->set('controller',$this->controller);
		$this->set('model',$this->model);

	}


	public function admin_index($defaultTab='All') {
		$model=$this->model;
		$controller=$this->controller;
		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}

		$filters_without_status = $filters = array();
		if($defaultTab!='All'){
			$filters[] = array($model.'.status'=>array_search($defaultTab, Configure::read('Status')));
		}

		if(!empty($this->request->data)){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
				
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data[''.$model.'']['name'])){
				$name = Sanitize::escape($this->request->data[''.$model.'']['name']);
				$this->Session->write('AdminSearch.name', $name);
			}

			if(isset($this->request->data[''.$model.'']['status']) && $this->request->data[''.$model.'']['status']!=''){
				$status = Sanitize::escape($this->request->data[''.$model.'']['status']);
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
					$filters[] = array(''.$model.'.'.$key =>$values);
				}
				else{
					$filters[] = array(''.$model.'.'.$key.' LIKE'=>"%".$values."%");
					$filters_without_status[] = array(''.$model.'.'.$key.' LIKE'=>"%".$values."%");
				}
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
		}else{
				
			$active=0;$inactive=0;
			if($search_status=='' || $search_status==Configure::read('App.Status.active')){
				$temp=$filters_without_status;
				$temp[] = array(''.$model.'.status'=>Configure::read('App.Status.active'));
				$active = $this->$model->find('count',array('conditions'=>$temp));
			}
				
			if($search_status=='' || $search_status==Configure::read('App.Status.inactive')){
				$temp=$filters_without_status;
				$temp[] = array(''.$model.'.status'=>Configure::read('App.Status.inactive'));
				$inactive = $this->$model->find('count',array('conditions'=>$temp));
			}

			$tabs = array('All'=>$active+$inactive, 'Active'=>$active,'Inactive'=>$inactive);
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
			if($action == "activate"){
				$this->$model->updateAll(array(''.$model.'.status'=>Configure::read('App.Status.active')),array(''.$model.'.id'=>$ids));

				$this->Session->setFlash(''.$model.' have been activated successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
				
			if($action == "deactivate"){
				$this->$model->updateAll(array(''.$model.'.status'=>Configure::read('App.Status.inactive')),array(''.$model.'.id'=>$ids));

				$this->Session->setFlash(''.$model.' have been deactivated successfully', 'admin_flash_success');
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
	public function admin_edit($id = null) {
		$model=$this->model;
		$controller=$this->controller;

		$this->$model->id = $id;
		if (!$this->$model->exists()) {
			throw new NotFoundException(__('Invalid '.$model.''));
		}

		if ($this->request->is('post') || $this->request->is('put')) {
				
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				//validate Agreement data
				$this->$model->set($this->request->data);
				$this->$model->setValidation('admin');
				if ($this->$model->validates()) {
					if ($this->$model->save($this->request->data)) {
						$this->Session->setFlash(__('The '.$model.' information has been updated successfully',true), 'admin_flash_success');
						$this->redirect(array('action' => 'index'));
					} else {
						$this->Session->setFlash(__('The '.$model.' could not be saved. Please, try again.',true), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash(__('The '.$model.' could not be saved. Please, correct errors.', true), 'admin_flash_error');
				}
			}
		}
		else {
			$this->request->data = $this->$model->read(null, $id);
		}
	}

	/**
	 * toggle status existing Agreement
	 */
	public function admin_status($id = null) {
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

		if ($this->$model->toggleStatus($id)) {
			$this->Session->setFlash(__(''.$model.'\'s status has been changed'), 'admin_flash_success');
			$this->redirect($this->referer());
		}
		$this->Session->setFlash(__(''.$model.'\'s status was not changed', 'admin_flash_error'));
		$this->redirect($this->referer());
	} /*
	* Add new Agreement
	*/
	public function admin_add() {
		$model=$this->model;
		$controller=$this->controller;

		if ($this->request->is('post')) {
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}

				//validate user data

				$this->$model->set($this->request->data);
				$this->$model->setValidation('admin');
				if ($this->$model->validates()) {
					if ($this->$model->saveAll($this->request->data)) {
						$this->Session->setFlash(__(''.$model.' has been saved successfully'), 'admin_flash_success');
						$this->redirect(array('action'=>'index'));
					} else {
						$this->Session->setFlash(__('The '.$model.' could not be saved. Please, try again.'), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash('The '.$model.' could not be saved.  Please, correct errors.', 'admin_flash_error');
				}
			}
		}

	}


}