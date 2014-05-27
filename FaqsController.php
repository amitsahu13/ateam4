<?php
/**
 * Faqs Controller
 *
 * PHP version 5.4
 *
 */
class FaqsController extends AppController {
	/**
	 * Faqs name
	 *
	 * @var string
	 * @access public
	 */
	var	$name	=	'Faqs';
	var	$uses	=	array('Faq');
	var $helpers = array('Mailto','Html','General');
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();
	}


	public function admin_index($defaultTab='All') {

		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}

		$filters_without_status = $filters = array();
		if($defaultTab!='All'){
			$filters[] = array('Faq.status'=>array_search($defaultTab, Configure::read('Status')));
		}

		if(!empty($this->request->data)){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
				
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data['Faq']['question'])){
				$question = Sanitize::escape($this->request->data['Faq']['question']);
				$this->Session->write('AdminSearch.question', $question);
			}
				
				
			if(isset($this->request->data['Faq']['status']) && $this->request->data['Faq']['status']!=''){
				$status = Sanitize::escape($this->request->data['Faq']['status']);
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
					$filters[] = array('Faq.'.$key =>$values);
				}
				else{
					$filters[] = array('Faq.'.$key.' LIKE'=>"%".$values."%");
					$filters_without_status[] = array('Faq.'.$key.' LIKE'=>"%".$values."%");
				}
			}
				
			$search_flag=1;
		}
		$this->set(compact('search_flag','defaultTab'));
		$this->paginate = array(
			'Faq'=>array(	
				'limit'=>Configure::read('App.AdminPageLimit'), 
				'order'=>array('Faq.id'=>'desc'),
				'conditions'=>$filters,
				'recursive'=>1
		)
		);

		$data = $this->paginate('Faq');
		$this->set(compact('data'));
		$this->set(compact('data'));
		$this->set('title_for_layout',  __('Faqs', true));

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
				$temp[] = array('Faq.status'=>Configure::read('App.Status.active'));
				$active = $this->Faq->find('count',array('conditions'=>$temp));
			}
				
			if($search_status=='' || $search_status==Configure::read('App.Status.inactive')){
				$temp=$filters_without_status;
				$temp[] = array('Faq.status'=>Configure::read('App.Status.inactive'));
				$inactive = $this->Faq->find('count',array('conditions'=>$temp));
			}

			$tabs = array('All'=>$active+$inactive, 'Active'=>$active,'Inactive'=>$inactive);
			$this->set(compact('tabs'));
		}
	}

	function admin_view($id)
	{
		$this->Faq->id = $id;
		if (!$this->Faq->exists()) {
			throw new NotFoundException(__('Invalid Contact'));
		}
		$this->set('faq', $this->Faq->read(null, $id));
	}

	function referer($default = NULL, $local = false)
	{
		$defaultTab = $this->Session->read('Url.defaultTab');
		$Page = $this->Session->read('Url.Page');
		$sort = $this->Session->read('Url.sort');
		$direction = $this->Session->read('Url.direction');

		return Router::url(array('action'=>'index', $defaultTab,'Page'=>$Page,'sort'=>$sort,'direction'=>$direction),true);
	}


	/*delete feedbacks*/
	public function admin_delete($id = null) {
		$this->Faq->id = $id;
		if (!$this->Faq->exists()) {
			throw new NotFoundException(__('Invalid Contact'));
		}
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}
		if ($this->Faq->delete()) {
			$this->Session->setFlash(__('Faq deleted successfully'), 'admin_flash_success');
			$this->redirect($this->referer());
		}
		$this->Session->setFlash(__('Faq was not deleted', 'admin_flash_error'));
		$this->redirect($this->referer());
	}

	/*delete selected process*/
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
			$action = Sanitize::escape($this->request->data['Faq']['pageAction']);

			$ids = $this->request->data['Faq']['id'];
				
			if (count($this->request->data) == 0 || $this->request->data['Faq'] == null) {
				$this->Session->setFlash('No items selected.', 'admin_flash_error');
				$this->redirect($this->referer());
			}
				
			if($action == "delete"){
				$this->Faq->deleteAll(array('Faq.id'=>$ids));
				$this->Session->setFlash('Faq have been deleted successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
			if($action == "activate"){
				$this->Faq->updateAll(array('Faq.status'=>Configure::read('App.Status.active')),array('Faq.id'=>$ids));
					

				$this->Session->setFlash('Faq have been activated successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
				
			if($action == "deactivate"){
				$this->Faq->updateAll(array('Faq.status'=>Configure::read('App.Status.inactive')),array('Faq.id'=>$ids));

				$this->Session->setFlash('Faq have been deactivated successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
		}
		else{
			$this->redirect(array('controller'=>'faqs', 'action'=>'index'));
		}
	}
	/**
	 * edit existing Faq
	 */
	public function admin_edit($id = null) {
		$this->Faq->id = $id;
		if (!$this->Faq->exists()) {
			throw new NotFoundException(__('Invalid Faq'));
		}

		if ($this->request->is('post') || $this->request->is('put')) {
				
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				//validate Faq data
				$this->Faq->set($this->request->data);
				$this->Faq->setValidation('admin');
				if ($this->Faq->validates()) {
					if ($this->Faq->save($this->request->data)) {
						$this->Session->setFlash(__('The Faq information has been updated successfully',true), 'admin_flash_success');
						$this->redirect(array('action' => 'index'));
					} else {
						$this->Session->setFlash(__('The Faq could not be saved. Please, try again.',true), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash(__('The Faq could not be saved. Please, correct errors.', true), 'admin_flash_error');
				}
			}
		}
		else {
			$this->request->data = $this->Faq->read(null, $id);
		}
	}

	/**
	 * toggle status existing Faq
	 */
	public function admin_status($id = null) {

		$this->Faq->id = $id;
		if (!$this->Faq->exists()) {
			throw new NotFoundException(__('Invalid Faq'));
		}
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}

		if ($this->Faq->toggleStatus($id)) {
			$this->Session->setFlash(__('Faq\'s status has been changed'), 'admin_flash_success');
			$this->redirect($this->referer());
		}
		$this->Session->setFlash(__('Faq\'s status was not changed', 'admin_flash_error'));
		$this->redirect($this->referer());
	} /*
	* Add new Faq
	*/
	public function admin_add() {
		if ($this->request->is('post')) {
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}

				//validate user data

				$this->Faq->set($this->request->data);
				$this->Faq->setValidation('admin');
				if ($this->Faq->validates()) {
					if ($this->Faq->saveAll($this->request->data)) {
						$this->Session->setFlash(__('Faq has been saved successfully'), 'admin_flash_success');
						$this->redirect(array('action'=>'index'));
					} else {
						$this->Session->setFlash(__('The Faq could not be saved. Please, try again.'), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash('The Faq could not be saved.  Please, correct errors.', 'admin_flash_error');
				}
			}
		}

	}


}