<?php
/**
 * States Controller
 *
 * PHP version 5
 *
 * @category Controller
 */
class StatesController extends AppController{
	/**
	 * Controller name
	 *
	 * @var string
	 * @access public
	 */
	var $name	=	'States';
	var $helpers = array('Html', 'Form', 'General');
	var $uses = array('State');
	/**
	 * beforeFilter
	 *
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('getStateList','get_state_front');
		$this->loadModel('State');
	}

	/* function beforeFilter(){
		parent::beforeFilter();
		// CSRF Protection
		if (in_array($this->params['action'], array('admin_process'))) {
		$this->Security->validatePost = false;
		}
		$this->Auth->allowedActions = array('getStateList');
	 }*/

	/*
	 * List all states in admin panel
	 */

	function admin_index($defaultTab='All') {
		$this->loadModel('Country');

		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}

		$filters = $filters_without_status = array();
		if($defaultTab!='All'){
			$filters[] = array('State.status'=>array_search($defaultTab, Configure::read('Status')));
		}

		if(!empty($this->request->data)){

			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
				
			if(!empty($this->data['State']['country_id'])){
				$country_id = $this->data['State']['country_id'];
				$this->Session->write('AdminSearch.country_id', $country_id);
			}
				
			if(!empty($this->data['State']['name'])){
				$keyword = $this->data['State']['name'];
				$this->Session->write('AdminSearch.keyword', $keyword);
			}
				
			if(isset($this->data['State']['status']) && $this->data['State']['status']!=''){
				$status = $this->data['State']['status'];
				$this->Session->write('AdminSearch.status', $status);
			}
				
		}

		$search_flag=0;	$search_status='';
		if($this->Session->check('AdminSearch')){
			$keywords  = $this->Session->read('AdminSearch');
				
			foreach($keywords as $key=>$values){

				if($key == 'keyword'){
					$filters[] = array('State.name LIKE'=>"%".$values."%");
					$filters_without_status[] = array('State.name LIKE'=>"%".$values."%");
				}else if($key == 'country_id'){
					$filters[] = array('State.country_id'=>$values);
					$filters_without_status[] = array('State.country_id'=>$values);
				}
				else{
					$filters[] = array('State.'.$key =>$values);
					$search_status=$values;
				}
			}

			$search_flag=1;
				
		}

		$this->set(compact('search_flag','defaultTab'));
		$this->State->bindModel(
		array(
				'belongsTo'=>array(
					'Country'=>array(
						'className'=>'Country',
						'foreignKey'=>'country_id'
						)
						)
						),false
						);
						$this->paginate = array(
			'State'=>array(	
				'limit'=>Configure::read('App.AdminPageLimit'), 
				'order'=>array('State.id'=>'desc'),
				'conditions'=>$filters,
				'recursive'=>1
						)
						);


						$data = $this->paginate('State');

						//$countries	=	$this->State->State->getStateList();
						$this->set(compact('data', 'countries'));
						$this->set('title_for_layout',  __('State Management', true));

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
								$temp[] = array('State.status'=>Configure::read('App.Status.active'));
								$active = $this->State->find('count',array('conditions'=>$temp));
							}
								
							if($search_status=='' || $search_status==Configure::read('App.Status.inactive')){

								$temp=$filters_without_status;
								$temp[] = array('State.status'=>Configure::read('App.Status.inactive'));
								$inactive = $this->State->find('count',array('conditions'=>$temp));
							}
								
							$tabs = array('All'=>$active+$inactive, 'Active'=>$active,'Inactive'=>$inactive);
							$this->set(compact('tabs'));
						}
						$countries=$this->Country->getCountryList();
						$this->set('countries',$countries);
	}

	/*
	 * Add new state in admin panel
	 */
	function admin_add(){

		if ($this->request->is('post')) {
			//check empty
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}


				//validate user data
				$this->State->set($this->request->data);
				$this->State->setValidation('admin');
				if ($this->State->validates()) {
						
					if ($this->State->save($this->request->data)) {
						$this->Session->setFlash(__('State has been added successfully'), 'admin_flash_success');
						$this->redirect(array('action' => 'index'));
					} else {
						$this->Session->setFlash(__('The State could not be added. Please, try again.'), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash('The State could not be added.  Please, correct errors.', 'admin_flash_error');
				}
			}
		}
			
		$countries=$this->State->getCountryList();
			
		$this->set('countries',$countries);
	}

	/*
	 * Edit existing state in admin panel
	 */
	function admin_edit($id = null){
		$this->set("title_for_layout", __('State Management', true));
		$countries	=	$this->State->getCountryList();
		$this->set(compact('countries'));

		if(!$id && empty($this->data)) {
			$this->Session->setFlash('Invalid state id', 'admin_flash_bad');
			$this->redirect(array('action' => 'index'));
		}

		if(!empty($this->data)) {
				
			// CSRF Protection
			if ($this->params['_Token']['key'] != $this->data['State']['token_key']) {
				$blackHoleCallback = $this->Security->blackHoleCallback;
				$this->$blackHoleCallback();
			}
				
			// validate & save data
			$this->State->setValidation('admin');
			if ($this->State->validates()) {
				if ($this->State->save($this->data)) {
					$this->Session->setFlash(__('State has been saved', true), 'admin_flash_good');
					$this->redirect(array('controller'=>'states', 'action' => 'index'));
				}
				else {
					$this->Session->setFlash(__('Please correct the errors listed below.', true), 'admin_flash_bad');
				}
			}
			else {
				$this->Session->setFlash(__('State could not be saved. Please, try again.', true), 'admin_flash_bad');
			}
		}
		else{
			$this->data = $this->State->read(null, $id);
		}
	}
		
	/* delete exiting states*/
	function admin_delete($id = null){

		if (!$id) {
			$this->Session->setFlash(__('Invalid state id', true), 'admin_flash_good');
			$this->redirect(array('action' => 'index'));
		}

		if (!isset($this->params['named']['token']) || ($this->params['named']['token'] != $this->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}
		if ($this->State->deleteAll(array('State.id' => $id))) {
			$this->Session->setFlash(__('State has been deleted successfully', true), 'admin_flash_good');
			$this->redirect($this->referer());
		}
	}

	function admin_process(){
		if(!empty($this->data)){

			App::uses('Sanitize', 'Utility');
			$action = Sanitize::escape($this->data['State']['pageAction']);
				
			/* foreach ($this->data['State'] AS $value) {
				if ($value != 0) {
				$ids[] = $value;
				}
				} */
			$ids = $this->request->data['State']['id'];
			//pr($ids); die;
			if (count($this->data) == 0 || $this->data['State'] == null) {
				$this->Session->setFlash('No items selected.', 'admin_flash_bad');
				$this->redirect($this->referer());
			}
				
			if($action == "delete"){
				$this->State->deleteAll(array('State.id'=>$ids));
				$this->Session->setFlash('States have been deleted successfully', 'admin_flash_good');
				$this->redirect($this->referer());
			}
				
			if($action == "activate"){
				$this->State->updateAll(array('State.status'=>'"'.Configure::read('App.Status.active').'"'),array('State.id'=>$ids));
				$this->Session->setFlash('States have been activated successfully', 'admin_flash_good');
				$this->redirect($this->referer());
			}
				
			if($action == "deactivate"){
				$this->State->updateAll(array('State.status'=>'"'.Configure::read('App.Status.inactive').'"'),array('State.id'=>$ids));
				$this->Session->setFlash('States have been deactivated successfully', 'admin_flash_good');

				$this->redirect($this->referer());
			}
		}
		else{
			$this->redirect(array('controller'=>'states', 'action'=>'index'));
		}
	}
	/* get state list in admin panel*/
	function admin_getStateList($data = null){

		$ajax=parent::is_ajax();
		$this->loadModel('Country');
		$this->layout = false;
		//$this->autoRender = true;
		$label=false;
		if(!empty($type) && $type=='Profile'){
			$label='State*';
		}
		$this->set('label',$label);
		if(!empty($this->request->data))
		{
			$states = $this->State->getStateList($this->request->data['User']['country_id']);
			$this->set('states',$states);
		}else {
			$this->set('states','');
		}
		$this->set('cid',$this->request->data['User']['country_id']);
		$this->render('admin_get_state_list');

		// $this->getStateList($data = null);
	}

	/**
	 * toggle status existing user
	 */
	public function admin_status($id = null) {
		$this->State->id = $id;
		if (!$this->State->exists()) {
			throw new NotFoundException(__('Invalid state'));
		}
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}

		if ($this->State->toggleStatus($id)) {
			$this->Session->setFlash(__('State\'s status has been changed'), 'admin_flash_success');
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('State\'s status was not changed', 'admin_flash_error'));
		$this->redirect(array('action' => 'index'));
	}

	public function admin_get_state()
	{

		$this->loadModel('State');
		$states = array();
		if(!empty($this->request->data))
		{
			if(isset($this->request->data['Job']['country_id']))
			{
				$state_id = $this->request->data['Job']['country_id'];
				$states = $this->State->find('list',array('conditions'=>array('State.country_id'=>$state_id)));
			}
		}
		$this->set(compact('states'));
		$this->render('admin_get_state');
	}

	public function admin_get_state_user()
	{

		$this->loadModel('State');
		$states = array();
		if(!empty($this->request->data))
		{
			if(isset($this->request->data['UserDetail']['country_id']))
			{
				$state_id = $this->request->data['UserDetail']['country_id'];
				$states = $this->State->find('list',array('conditions'=>array('State.country_id'=>$state_id)));
			}
		}
		$this->set(compact('states'));
		$this->render('admin_get_state_user');
	}

	/**************************Front End Start From Here***************************/

	public function get_state_for_user_registration()
	{
		$states =array();
		if($this->request->data[$model]['country_id'])
		{
			$states = $this->State->getStateList($this->request->data[$model]['country_id']);
		}
		$this->set('states',$states);
	}

	public function get_state_front()
	{

		$this->loadModel('State');
		$states = array();
		if(!empty($this->request->data))
		{
			if(isset($this->request->data['UserDetail']['country_id']))
			{
				$state_id = $this->request->data['UserDetail']['country_id'];
				$states = $this->State->find('list',array('conditions'=>array('State.country_id'=>$state_id)));
			}
		}
		$this->set(compact('states'));
		$this->render('get_state_front');
	}

	public function get_job_state_front()
	{

		$this->loadModel('State');
		$states = array();
		if(!empty($this->request->data))
		{
			if(isset($this->request->data['Job']['country_id']))
			{
				$state_id = $this->request->data['Job']['country_id'];
				$states = $this->State->find('list',array('conditions'=>array('State.country_id'=>$state_id)));
			}
		}
		$this->set(compact('states'));
		$this->render('get_job_state_front');
	}

	public function get_state_for_project()
	{

		$this->loadModel('State');
		$states = array();
		if(!empty($this->request->data))
		{
			if(isset($this->request->data['Project']['country_id']))
			{
				$state_id = $this->request->data['Project']['country_id'];
				$states = $this->State->find('list',array('conditions'=>array('State.country_id'=>$state_id)));
			}
		}
		$this->set(compact('states'));
		$this->render('get_state_for_project');
	}
	
	public function get_search_state_front($model=null)
	{

		$this->loadModel('State');
		$states = array();
		if(!empty($this->request->data))
		{
			if(isset($this->request->data[$model]['country_id']))
			{
				$state_id = $this->request->data[$model]['country_id'];
				$states = $this->State->find('list',array('conditions'=>array('State.country_id'=>$state_id)));
			}
		}
		$this->set(compact('states','model'));
		$this->render('get_search_state_front');
	}


}
?>