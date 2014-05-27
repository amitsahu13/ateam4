<?php
/**
 * Categories Controller
 *
 * PHP version 5.4
 *
 */
class CategoriesController extends AppController {
	/**
	 * Controller name
	 *
	 * @var string
	 * @access public
	 */
	var	$name	=	'Categories';
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('login');
		$this->loadModel('Category');
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
			$filters[] = array('Category.status'=>array_search($defaultTab, Configure::read('Status')));
		}

		if(!empty($this->request->data)){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
				
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data['Category']['name'])){
				$name = Sanitize::escape($this->request->data['Category']['name']);
				$this->Session->write('AdminSearch.name', $name);
			}
			if(!empty($this->data['Category']['parent_id'])){
				$parent_id = Sanitize::escape($this->request->data['Category']['parent_id']);
				$this->Session->write('AdminSearch.parent_id', $parent_id);
			}
			if(isset($this->request->data['Category']['status']) && $this->request->data['Category']['status']!=''){
				$status = Sanitize::escape($this->request->data['Category']['status']);
				$this->Session->write('AdminSearch.status', $status);
				$defaultTab = Configure::read('Status.'.$status);
			}
				
				
		}


		$search_flag=0;
		if($this->Session->check('AdminSearch')){
			$keywords  = $this->Session->read('AdminSearch');
				
			foreach($keywords as $key=>$values){
				if($key == 'status' && $key == 'parent_id'){
					$filters[] = array('Category.'.$key =>$values);
				}
				else{
				 $filters[] = array('Category.'.$key.' LIKE'=>"%".$values."%");
				 $filters_without_status = array('Category.'.$key.' LIKE'=>"%".$values."%");
				}
			}
				
			$search_flag=1;
		}
		$this->set(compact('search_flag','defaultTab'));

		//$filters[] = array('Category.role_id'=>Configure::read('App.Role.Admin'));



		$this->paginate = array(
			'Category'=>array(	
				'limit'=>Configure::read('App.AdminPageLimit'), 
				'order'=>array('Category.name'=>'ASC'),
				'conditions'=>$filters,
				'recursive'=>1
		)
		);

		$data = $this->paginate('Category');
		$parents = $this->Category->parentsList();

		$this->set(compact('data','parents'));
		$this->set('title_for_layout',  __('Categories', true));

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
			$temp=$filters_without_status;
			$temp[] = array('Category.status'=>1);
			$active = $this->Category->find('count',array('conditions'=>$temp));
				
			$temp=$filters_without_status;
			$temp[] = array('Category.status'=>0);
			$inactive = $this->Category->find('count',array('conditions'=>$temp));
				
				
			$tabs = array('All'=>$active+$inactive, 'Active'=>$active,'Inactive'=>$inactive);
			$this->set(compact('tabs'));
		}
	}

	public function admin_add() {
		if ($this->request->is('post')) {
			//check empty
			if(!empty($this->request->data))
			{
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				//validate user data
				// pr($this->request->data); die;
				$this->Category->set($this->request->data);
				$this->Category->setValidation('admin');
				if ($this->Category->validates()) {
					if(empty($this->request->data['Category']['parent_id']))
					{
						$this->request->data['Category']['parent_id'] = 0;
					}
					if ($this->Category->save($this->request->data)) {
						$this->Session->setFlash(__('Category has been added successfully'), 'admin_flash_success');
						$this->redirect(array('action' => 'index'));
					} else {
						$this->Session->setFlash(__('The Category could not be added. Please, try again.'), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash('The Category could not be added.  Please, correct errors.', 'admin_flash_error');
				}
			}
		}
		$parents = array();
		if(isset($this->request->data['Category']['type_for']))
		{
			//$parents = $this->Category->find('list',array('conditions'=>array('Category.type_for'=>$this->request->data['Category']['type_for'])));
				
			$conditions=array('Category.type_for'=>$this->request->data['Category']['type_for']);
			$parents =$this->Category->displayCategoryTreeFront(0,0,$conditions);
		}

		//$cat = $this->Category->generateTreeList(null,null,null, "--");
		//array('conditions'=>array('Category.type_for'=>2))
		//$cat = $this->Category->generateTreeList(null,null,null, null);
		//pr($parents);die;
		//$this->set('parents',$parents);
		$this->set(compact(array('parents')));
	}

	/**
	 * edit existing category
	 */
	public function admin_edit($id = null) {
		$this->Category->id = $id;
		if (!$this->Category->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}

		if ($this->request->is('post') || $this->request->is('put')) {
				
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				//validate user data
				$this->Category->set($this->request->data);
				$this->Category->setValidation('admin');
				if ($this->Category->validates()) {
					if ($this->Category->save($this->request->data)) {
						$this->Session->setFlash(__('The Category has been updated successfully',true), 'admin_flash_success');
						$this->redirect(array('action' => 'index'));
					} else {
						$this->Session->setFlash(__('The Category could not be saved. Please, try again.',true), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash(__('The Category could not be saved. Please, correct errors.', true), 'admin_flash_error');
				}
			}
		}
		else {
			$this->request->data = $this->Category->read(null, $id);
			$parents = array();
			if($this->request->data['Category']['type_for'])
			{
				//$parents = $this->Category->find('list',array('conditions'=>array('Category.type_for'=>$this->request->data['Category']['type_for'])));
				$conditions=array('Category.type_for'=>$this->request->data['Category']['type_for']);
				$parents =$this->Category->displayCategoryTreeFront(0,0,$conditions);
			}
			$this->set('parents',$parents);
		}
	}
	/**
	 * delete existing user
	 */
	public function admin_delete($id = null) {
		$this->Category->id = $id;
		if (!$this->Category->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}
		if ($this->Category->delete()) {
			$this->Session->setFlash(__('Category deleted successfully'), 'admin_flash_success');
			$this->redirect($this->referer());
		}
		$this->Session->setFlash(__('Category was not deleted', 'admin_flash_error'));
		$this->redirect($this->referer());
	}

	/**
	 * toggle status existing user
	 */
	public function admin_status($id = null) {
		$this->Category->id = $id;
		if (!$this->Category->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}

		if ($this->Category->toggleStatus($id)) {
			$this->Session->setFlash(__('Category status has been changed'), 'admin_flash_success');
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Category status was not changed', 'admin_flash_error'));
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
			$action = Sanitize::escape($this->request->data['Category']['pageAction']);

			$ids = $this->request->data['Category']['id'];
				
			if (count($this->request->data) == 0 || $this->request->data['Category'] == null) {
				$this->Session->setFlash('No items selected.', 'admin_flash_error');
				$this->redirect($this->referer());
			}
				
			if($action == "delete"){
				$this->Category->deleteAll(array('Category.id'=>$ids));
				$this->Session->setFlash('Categories have been deleted successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
				
			if($action == "activate"){
				$this->Category->updateAll(array('Category.status'=>Configure::read('App.Status.active')),array('Category.id'=>$ids));
					

				$this->Session->setFlash('Categories have been activated successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
				
			if($action == "deactivate"){
				$this->Category->updateAll(array('Category.status'=>Configure::read('App.Status.inactive')),array('Category.id'=>$ids));

				$this->Session->setFlash('Categories have been deactivated successfully', 'admin_flash_success');
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

	function admin_get_skill_and_Subcate(){
		$this->layout = false;
		$this->loadModel('Skill');
		if(!empty($this->request->data))
		{
				
			$ModelName=array_keys($this->request->data);
			if(array_key_exists('category_id',$this->request->data[''.$ModelName[0].''])){
				$categories=$this->Category->get_categories('list','Category.name',array('Category.parent_id'=>$this->request->data[''.$ModelName[0].'']['category_id']));
			}
			if(array_key_exists('category_id',$this->request->data[''.$ModelName[0].''])){
				$category_id=$this->request->data[''.$ModelName[0].'']['category_id'];
			}else if(array_key_exists('sub_category_id',$this->request->data[''.$ModelName[0].''])){
				$category_id=$this->request->data[''.$ModelName[0].'']['sub_category_id'];
			}

			$skills=$this->Skill->get_skills('list','Skill.name',array('Skill.category_id'=>$category_id));
			$model=$ModelName[0];
			$this->set(compact('categories','skills','model'));
				
		}else {
			$this->set('categories','');
		}
		$this->render('get_skills_and_subcates');
	}

	function admin_get_skill_and_Subcate_project(){
		$this->layout = false;
		$this->loadModel('Skill');
		$this->loadModel('Project');
		if(!empty($this->request->data))
		{
			$cat_id = $this->request->data['Job']['category_id'];
			$ModelName=array_keys($this->request->data);
			if($cat_id){
				$categories=$this->Category->get_categories('list','Category.name',array('Category.parent_id'=>$cat_id,'Category.type_for'=>Configure::read('App.Category.Job')));
			}
			/* if(array_key_exists('category_id',$this->request->data[''.$ModelName[0].''])){
				$category_id=$this->request->data[''.$ModelName[0].'']['category_id'];
				} */
			else if(array_key_exists('sub_category_id',$this->request->data[''.$ModelName[0].''])){
				$category_id=$this->request->data[''.$ModelName[0].'']['sub_category_id'];
			}

			$skills=$this->Skill->get_skills('list','Skill.name',array('Skill.category_id'=>$cat_id));
			$model=$ModelName[0];
			$this->set(compact('categories','skills','model'));
				
		}else {
			$this->set('categories','');
		}
		$this->render('get_skills_and_subcates_project');
	}



	function get_skill_and_subcate_front(){

		if($this->RequestHandler->isAjax())
		{
			$this->layout = false;
			$this->autoRender = true;
			$this->loadModel('Skill');
			$this->loadModel('Project');
			$cat_id = $this->request->data['Job']['category_id'];
			$ModelName=array_keys($this->request->data);
				
			$sub_categories=$this->Category->get_categories('list','Category.name',array('Category.parent_id'=>$cat_id,'Category.type_for'=>Configure::read('App.Category.Job'),'Category.status'=>Configure::read('App.Status.active')));

			$skills=$this->Skill->get_skills('list','Skill.name',array('Skill.category_id'=>$cat_id));
			$this->set(compact('sub_categories','skills'));
				
		}
	}





	/***********************Front End Start From Here******************************/

	public function get_sub_category_for_user_registration()
	{
		$sub_categories=array();
		$skills = array();
		if($this->request->data[$model]['category_id'])
		{
			$sub_categories = $this->Category->getSubCategory($this->request->data[$model]['category_id']);
			$skills = $this->Skill->get_skills('list','Skill.name',array('Skill.category_id'=>$this->request->data[$model]['category_id']));
		}
		$this->set('sub_categories',$sub_categories);
		$this->set('skills',$skills);
	}


	public function admin_sub_category_for_project()
	{
		$sub_categories=array();
		//$skills = array();
		if($this->request->data['Project']['category_id'])
		{
			//$sub_categories = $this->Category->getSubCategory($this->request->data[$model]['category_id']);
			$sub_categories=$this->Category->find('list',array('conditions'=>array('Category.status'=>Configure::read('App.Status.active'),'Category.type_for'=>Configure::read('App.Category.Project'),'Category.parent_id'=>$this->request->data['Project']['category_id'])));
			//$skills = $this->Skill->get_skills('list','Skill.name',array('Skill.category_id'=>$this->request->data[$model]['category_id']));
		}
		$this->set('sub_categories',$sub_categories);
		//$this->set('skills',$skills);
		$this->render('admin_sub_category_for_project');
	}

	public function admin_category_type()
	{
		$sub_categories=array();
		$skills = array();
		$parents = array();
		if($this->request->data['Category']['type_for'])
		{
			$conditions=array('Category.type_for'=>$this->request->data['Category']['type_for']);
			$parents =$this->Category->displayCategoryTreeFront(0,0,$conditions);
		}
		$this->set('parents',$parents);
		$this->render('admin_category_type');
	}


	public function get_project_subcategory_front()
	{
		if($this->RequestHandler->isAjax())
		{
			$this->layout = false;
			$this->autoRender = true;
			$category_id = $this->request->data['Project']['category_id'];
		  	$child_categories = $this->Category->get_project_job_child_category_lists($category_id,Configure::read('App.Category.Project'));
			$this->set(compact('child_categories'));
				
		}
	}


	function get_skill_and_subcate_jobs(){
		if($this->RequestHandler->isAjax())
		{
			$this->layout = false;
			$this->autoRender = true;
			$this->loadModel('Skill');
			$this->loadModel('Project');
			$cat_id = $this->request->data['User']['category_id'];
			$ModelName=array_keys($this->request->data);
				
			$sub_categories=$this->Category->get_categories('list','Category.name',array('Category.parent_id'=>$cat_id,'Category.type_for'=>Configure::read('App.Category.Job'),'Category.status'=>Configure::read('App.Status.active')));

			$skills=$this->Skill->get_skills('list','Skill.name',array('Skill.category_id'=>$cat_id));
			$this->set(compact('sub_categories','skills'));
				
		}
	}



}