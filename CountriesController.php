<?php
/**
 * Countries Controller
 *
 * PHP version 5.4
 *
 */
class CountriesController extends AppController {
	/**
	 * Controller name
	 *
	 * @var string
	 * @access public
	 */
	var	$name	=	'Countries';
	var $helpers = array('General');
	public $components = array('General','Cookie','Upload','RequestHandler');
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('login','get_county_region');
		$this->loadModel('Country');
		$this->loadModel('Region');
	}
	/*
	 * List all admin users in admin panel
	 */
	public function admin_index($defaultTab='All') {
		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}

		$filters_without_status = $filters = array();
		if($defaultTab!='All'){
			$filters[] = array('Country.status'=>array_search($defaultTab, Configure::read('Status')));
		}
		//pr($filters); die;
		if(!empty($this->request->data)){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
				
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data['Country']['name'])){
				$name = Sanitize::escape($this->request->data['Country']['name']);
				$this->Session->write('AdminSearch.name', $name);
			}

			if(isset($this->request->data['Country']['status']) && $this->request->data['Country']['status']!=''){
				$status = Sanitize::escape($this->request->data['Country']['status']);
				$this->Session->write('AdminSearch.status', $status);
				$defaultTab = Configure::read('Status.'.$status);
			}
				
				
		}


		$search_flag=0;$search_status='';
		if($this->Session->check('AdminSearch')){
			$keywords  = $this->Session->read('AdminSearch');
				
			foreach($keywords as $key=>$values){

				if($key == 'status'){
					$search_status=$values;
					$filters[] = array('Country.'.$key =>$values);
				}
				else{
				 $filters[] = array('Country.'.$key.' LIKE'=>"%".$values."%");
				 $filters_without_status = array('Country.'.$key.' LIKE'=>"%".$values."%");
				}
			}
				
			$search_flag=1;
		}
		$this->set(compact('search_flag','defaultTab'));

		$this->Country->bindModel(
		array(
				'belongsTo'=>array(
					'Region'=>array(
						'className'=>'Region',
						'foreignKey'=>'region_id'
						)
						)
						),false
						);

						$this->paginate = array(
			'Country'=>array(	
				'limit'=>Configure::read('App.AdminPageLimit'), 
				'order'=>array('Country.id'=>'desc'),
				'conditions'=>$filters,
				'recursive'=>1
						)
						);

						$data = $this->paginate('Country');

						$this->set(compact('data'));
						$this->set('title_for_layout',  __('Countries', true));

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
								$temp[] = array('Country.status'=>Configure::read('App.Status.active'));
								$active = $this->Country->find('count',array('conditions'=>$temp));
							}
								
							if($search_status=='' || $search_status==Configure::read('App.Status.inactive')){
								$temp=$filters_without_status;
								$temp[] = array('Country.status'=>Configure::read('App.Status.inactive'));
								$inactive = $this->Country->find('count',array('conditions'=>$temp));
							}
								
							$tabs = array('All'=>$active+$inactive, 'Active'=>$active,'Inactive'=>$inactive);
							$this->set(compact('tabs'));
						}
	}

	public function admin_add() {
		if ($this->request->is('post')) {
			//check empty
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}


				//validate user data
				$this->Country->set($this->request->data);
				$this->Country->setValidation('admin');
				
				if ($this->Country->validates()) {
					if(!empty($this->request->data['Country']['country_flag']['tmp_name'])){
						$file_array = $this->request->data['Country']['country_flag']; 
						
						//$this->request->data['Country']['country_flag'] = $this->request->data['Country']['country_flag']['name'];
					}
					else{
						$file_array = "";
					}
					
					if(!empty($file_array)){
							$file_name = time();
							$filename = parent::__upload($file_array,FLAG_DIR_TEMP,$file_name); 
						
							$this->request->data['Country']['country_flag'] = $filename;
					}
						
					if ($this->Country->save($this->request->data)) {
						
						$this->Session->setFlash(__('Country has been added successfully'), 'admin_flash_success');
						$this->redirect(array('action' => 'index'));
					} else {
						$this->Session->setFlash(__('The Country could not be added. Please, try again.'), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash('The Country could not be added.  Please, correct errors.', 'admin_flash_error');
				}
			}
		}
		$regions = $this->Region->find('list',array('order'=>array('Region.name'=>'ASC')));
		$this->set('regions',$regions);
	}


	/**
	 * edit existing admin
	 */
	public function admin_edit($id = null) {
		$this->Country->id = $id;
		if (!$this->Country->exists()) {
			throw new NotFoundException(__('Country does not exists.'));
		}

		if ($this->request->is('post') || $this->request->is('put')) {
				
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				//validate user data

				$this->Country->set($this->request->data);
				$this->Country->setValidation('admin');
				
				if(!empty($this->request->data['Country']['country_flag']['tmp_name'])){
						$file_array = $this->request->data['Country']['country_flag']; 
						
						//$this->request->data['Country']['country_flag'] = $this->request->data['Country']['country_flag']['name'];
				}
				else{
					$file_array = "";
				}
					
				//pr($this->request->data);die;
				if ($this->Country->validates()) {
					if(!empty($file_array)){
						$file_name = time();
						$filename = parent::__upload($file_array,FLAG_DIR_TEMP,$file_name); 
					
						$this->request->data['Country']['country_flag'] = $filename;
					}
					if ($this->Country->save($this->request->data)) {
						$this->Session->setFlash(__('The Country has been updated successfully',true), 'admin_flash_success');
						$this->redirect(array('action' => 'index'));
					} else {
						$this->Session->setFlash(__('The Country could not be saved. Please, try again.',true), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash(__('The Country could not be saved. Please, correct errors.', true), 'admin_flash_error');
				}
			}
		}
		else {
			$this->request->data = $this->Country->read(null, $id);
		}
		$regions = $this->Region->find('list',array('order'=>array('Region.name'=>'ASC')));
		$this->set('regions',$regions);
	}
	/**
	 * delete existing user
	 */
	public function admin_delete($id = null) {
		$this->Country->id = $id;
		if (!$this->Country->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}
		$this->Country->bindModel(
		array(
				'hasMany'=>array(
					'State'=>array(
						'className'=>'State',
						'foreignKey'=>'country_id',
						'dependent'=>true
		)
		)
		),false
		);
		if ($this->Country->delete()) {
			$this->Session->setFlash(__('Country deleted successfully'), 'admin_flash_success');
			$this->redirect($this->referer());
		}
		$this->Session->setFlash(__('Country was not deleted', 'admin_flash_error'));
		$this->redirect($this->referer());
	}

	/**
	 * toggle status existing user
	 */
	public function admin_status($id = null) {
		$this->Country->id = $id;
		if (!$this->Country->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}

		if ($this->Country->toggleStatus($id)) {
			$this->Session->setFlash(__('Country\'s status has been changed'), 'admin_flash_success');
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Country\'s status was not changed', 'admin_flash_error'));
		$this->redirect(array('action' => 'index'));
	}

	/*activate, deactivate and delete process*/
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
			$action = Sanitize::escape($this->request->data['Country']['pageAction']);

			$ids = $this->request->data['Country']['id'];
				
			if (count($this->request->data) == 0 || $this->request->data['Country'] == null) {
				$this->Session->setFlash('No items selected.', 'admin_flash_error');
				$this->redirect($this->referer());
			}
				
			if($action == "delete"){
				$this->Country->bindModel(
				array(
						'hasMany'=>array(
						'State'=>array(
								'className'=>'State',
								'foreignKey'=>'country_id',
								'dependent'=>true
				)
				)
				),false
				);
				$this->Country->deleteAll(array('Country.id'=>$ids));
				$this->Session->setFlash('Countries have been deleted successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
				
			if($action == "activate"){
				$this->Country->updateAll(array('Country.status'=>Configure::read('App.Status.active')),array('Country.id'=>$ids));
					

				$this->Session->setFlash('Countries have been activated successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
				
			if($action == "deactivate"){
				$this->Country->updateAll(array('Country.status'=>Configure::read('App.Status.inactive')),array('Country.id'=>$ids));

				$this->Session->setFlash('Countries have been deactivated successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
		}
		else{
			$this->redirect(array('action'=>'index'));
		}
	}

	function referer($default = NULL, $local = false)
	{
		$defaultTab = $this->Session->read('Url.defaultTab');
		$page = $this->Session->read('Url.page');
		$sort = $this->Session->read('Url.sort');
		$direction = $this->Session->read('Url.direction');

		return Router::url(array('action'=>'index', $defaultTab,'page'=>$page,'sort'=>$sort,'direction'=>$direction),true);
	}

	public function admin_get_county()
	{
		$this->loadModel('Region');
		$this->loadModel('Country');
		$this->loadModel('State');
		$countries = array();
		$states = array();
		if(!empty($this->request->data))
		{
			if(isset($this->request->data['Job']['region_id']))
			{
				$region_id = $this->request->data['Job']['region_id'];
				$countries = $this->Country->find('list',array('conditions'=>array('Country.region_id'=>$region_id)));
			}
		}
		$this->set(compact('countries','states'));
		$this->render('admin_get_county');
	}

	public function admin_get_county_user()
	{  
		$this->loadModel('Region');
		$this->loadModel('Country');
		$this->loadModel('State');
		$this->loadModel('User');
		$countries = array();
		$states = array();
		if(!empty($this->request->data))
		{  
			if(isset($this->request->data['UserDetail']['region_id']))
			{ 
				$region_id = $this->request->data['UserDetail']['region_id'];
				$countries = $this->Country->find('list',array('conditions'=>array('Country.region_id'=>$region_id)));
			}
		}
		$this->set(compact('countries','states'));
		$this->render('admin_get_county_user');
	}

	/**************************Front End Start From Here***************************/

	public function get_country_for_user_registration()
	{
		$countries=array();
		if($this->request->data[$model]['region_id'])
		{
			$countries = $this->Country->getCountryListByRegionId($this->request->data[$model]['region_id']);
		}
		$this->set('countries',$countries);
	}


	public function get_county_region()
	{

		$this->loadModel('Region');
		$this->loadModel('Country');
		$this->loadModel('State');
		$this->loadModel('UserDetail');
		$countries = array();
		$states = array();
		if(!empty($this->request->data))
		{
			if(isset($this->request->data['UserDetail']['region_id']))
			{
				$region_id = $this->request->data['UserDetail']['region_id'];
				
				$countries = $this->Country->find('list',array('conditions'=>array('Country.region_id'=>$region_id)));
				
			}
		}
		$this->set(compact('countries','states'));
		$this->render('get_county_region');
	}


	public function get_city_front()
	{
		$this->loadModel('Region');
		$this->loadModel('Country');
		$this->loadModel('State');
		$this->loadModel('Job');
		$countries = array();
		$states = array();
		if(!empty($this->request->data))
		{
			if(isset($this->request->data['Job']['region_id']))
			{
				$region_id = $this->request->data['Job']['region_id'];
				$countries = $this->Country->find('list',array('conditions'=>array('Country.region_id'=>$region_id)));
			}
		}
		$this->set(compact('countries','states'));
		$this->render('get_city_front');
	}

	public function get_country_region_for_project()
	{

		$this->loadModel('Region');
		$this->loadModel('Country');
		$this->loadModel('State');
		$this->loadModel('Project');
		$countries = array();
		$states = array();
		if(!empty($this->request->data))
		{
			if(isset($this->request->data['Project']['region_id']))
			{
				$region_id = $this->request->data['Project']['region_id'];
				$countries = $this->Country->find('list',array('conditions'=>array('Country.region_id'=>$region_id)));
			}
		}
		$this->set(compact('countries','states'));
		$this->render('get_country_region_for_project');
	}
	
	public function get_search_country_front($model= null)
	{
		$this->loadModel('Region');
		$this->loadModel('Country');
		$this->loadModel('State');
		$this->loadModel($model);
		$countries = array();
		$states = array();
		if(!empty($this->request->data))
		{
			if(isset($this->request->data[$model]['region_id']))
			{
				$region_id = $this->request->data[$model]['region_id'];
				$countries = $this->Country->find('list',array('conditions'=>array('Country.region_id'=>$region_id)));
			}
		}
		$this->set(compact('countries','states','model'));
		$this->render('get_search_country_front');
	}


}