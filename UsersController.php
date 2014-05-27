<?php
/**
* Users Controller
* PHP version 5.4
*
*/
class UsersController extends AppController {
	/**
	* Controller name
	*
	* @var string
	* @access public
	*/
	public $name	=	'Users';
	public $helpers = array('Html','General', 'Linkid',  'Verify' , 'Feedback' ,'Paypal');
	public $uses = array('User','Category','Skill','Region','Country','State','SecurityQuestion');	
	public $model='User';
	public $controller='users';

	
	
	// BeforeRendrer 
	function beforeRender()
	{ 
		 parent::beforeRender();  
		 $model = Inflector::singularize($this->name);
		 foreach($this->{$model}->hasAndBelongsToMany as $k=>$v) {
			if(isset($this->{$model}->validationErrors[$k]))
			{
				$this->{$model}->{$k}->validationErrors[$k] = $this->{$model}->validationErrors[$k];
			}
		} 
		
	
		
	}
	
	public function beforeFilter() {
		parent::beforeFilter();
		
		$cook = $this->Cookie->read('User');
		$this->set('cookieVar',$cook);
		$this->Auth->allow('register','login','forgot_password','reset_password','start_access','get_facebook_data','get_linkedin_data');
	  }
	
	
	
	
	
	/*
	* List all users in admin panel
	*/
	
	public function admin_index($role=null, $defaultTab='All') {
		
		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}
		$stats=array();
		
		if(empty($role)){
			$this->redirect(array('controller'=>'admins','action'=>'dashboard'));
		}else{
			$role = strtolower($role);
			if($role=='buyer'){
				$role_id= Configure::read('App.Role.Buyer');
			}if($role=='provider'){
				$role_id= Configure::read('App.Role.Provider');
			}
			if($role=='both'){
				$role_id= Configure::read('App.Role.Both');
			}
		}
		
		$this->set('role',$role);
		$filters_without_status = $filters = array('User.role_id'=>$role_id); 
		
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
			if(!empty($this->request->data['User']['first_name'])){
				$first_name = Sanitize::escape($this->request->data['User']['first_name']);
				$this->Session->write('AdminSearch.first_name', $first_name);
			}
			if(!empty($this->request->data['User']['last_name'])){
				$last_name = Sanitize::escape($this->request->data['User']['last_name']);
				$this->Session->write('AdminSearch.last_name', $last_name);
			}
			if(!empty($this->request->data['User']['state'])){
				$state = Sanitize::escape($this->request->data['User']['state']);
				$this->Session->write('AdminSearch.state', $state);
			}
			if(!empty($this->request->data['User']['country_id'])){
				$country_id = Sanitize::escape($this->request->data['User']['country_id']);
				$this->Session->write('AdminSearch.country_id', $country_id);
				$stats=array('State.country_id'=>$this->request->data['User']['country_id']);
				
			}
			if(!empty($this->request->data['User']['zip'])){
				$zip = Sanitize::escape($this->request->data['User']['zip']);
				$this->Session->write('AdminSearch.zip', $zip);
			}
			if(!empty($this->request->data['User']['city'])){
				$city = Sanitize::escape($this->request->data['User']['city']);
				$this->Session->write('AdminSearch.city', $city);
			}
			if(!empty($this->request->data['User']['company_name'])){
				$company_name = Sanitize::escape($this->request->data['User']['company_name']);
				$this->Session->write('AdminSearch.company_name', $company_name);
			}			
			if(!empty($this->data['User']['username'])){
				$company_name = Sanitize::escape($this->request->data['User']['username']);
				$this->Session->write('AdminSearch.company_name', $company_name);
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
				if($key == 'country_id'){
					$filters[] = array('User.'.$key =>$values);
					$filters_without_status[] = array('User.'.$key =>$values);
				}
				if($key == 'state'){
					$filters[] = array('User.'.$key =>$values);
					$filters_without_status[] = array('User.'.$key =>$values);
				}
				if($key == 'first_name'){
					$filters[] = array('User.'.$key.' LIKE'=>"%".$values."%");
					$filters_without_status[] = array('User.'.$key.' LIKE'=>"%".$values."%");
				}
				if($key == 'last_name'){
					$filters[] = array('User.'.$key.' LIKE'=>"%".$values."%");
					$filters_without_status[] = array('User.'.$key.' LIKE'=>"%".$values."%");
				}
				if($key == 'zip'){
					$filters[] = array('User.'.$key.' LIKE'=>"%".$values."%");
					$filters_without_status[] = array('User.'.$key.' LIKE'=>"%".$values."%");
				}
				if($key == 'company_name'){
					$filters[] = array('User.'.$key.' LIKE'=>"%".$values."%");
					$filters_without_status[] = array('User.'.$key.' LIKE'=>"%".$values."%");
				}
				if($key == 'city'){
					$filters[] = array('User.'.$key.' LIKE'=>"%".$values."%");
					$filters_without_status[] = array('User.'.$key.' LIKE'=>"%".$values."%");
				}
				
			}
			
			$search_flag=1;
		}
		$this->set(compact('search_flag','defaultTab'));
				
		$this->User->bindModel(array('hasOne'=>array('UserDetail')),false);
		$this->User->UserDetail->bindModel(array('belongsTo'=>array('Country')),false);
		$this->User->Behaviors->attach('Containable');
		$this->paginate = array(
						'User'=>array(	
						'limit'=>Configure::read('App.AdminPageLimit'), 
						'order'=>array('User.id'=>'DESC'),
						'conditions'=>$filters,
						'contain'=>array('UserDetail'=>array('fields'=>array('UserDetail.city'),
															'Country'=>array('fields'=>array('Country.name')))
															),
						)
		);
		
		$data = $this->paginate('User');  
		/* pr($data);
die;	 */	
		
		$this->set(compact('data', 'role'));
		$this->set('title_for_layout',  __('Users', true));
		
		
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
				$temp[] = array('User.status'=>1);
				$active = $this->User->find('count',array('conditions'=>$temp));
			}
			if($search_status=='' || $search_status==Configure::read('App.Status.inactive')){
				$temp=$filters_without_status;
				$temp[] = array('User.status'=>0);
				$inactive = $this->User->find('count',array('conditions'=>$temp));
			}
			
			$tabs = array('All'=>$active+$inactive, 'Active'=>$active,'Inactive'=>$inactive);
			$this->set(compact('tabs'));
			
		}
		
		
		$states='';
		
		
		if(isset($this->request->data['User']['country_id']) && $this->request->data['User']['country_id']){
			$states = $this->State->find('list', array('conditions'=>array('status'=>Configure::read('App.Status.active'),$stats),'order'=>array('name'=>'ASC')));
		}
		
		$countries = $this->Country->getCountryList();	
		$this->set(compact('countries','states'));
	}
	
	

	/*
	* Dashboard
	*/
	public function admin_dashboard() {
		
	}	
	
	/* 
	* View existing user
	*/
	public function admin_view($id = null) {
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		$this->User->Behaviors->attach('Containable');
		$this->loadModel('User');
		$this->loadModel('UserDetail');
		
		$this->User->bindModel(array('hasAndBelongsToMany'=>array('WorkingStatus','Skill'),'hasOne'=>array('UserDetail')),false);
		$this->User->UserDetail->bindModel(array('belongsTo'=>array('Country','State','ExpertiseCategory'=>array(
					'className'=>'Category',
					'foreignKey'=>'expertise_category_id'
				),
				'LeadershipCategory'=>array(
					'className'=>'Category',
					'foreignKey'=>'leadership_category_id'
				)
				
				)),false);
		
		
		
		/* $this->User->bindModel(
		array('belongsTo'=>
		array(
		'Region',
		'Country',
		'State' ,
		'Category'=>array(
		'className'=>'Category',
		'foreignKey'=>'category_id'		
		),
		'SubCategory'=>array(
		'className'=>'Category',
		'foreignKey'=>'sub_category_id'		
		)
		)
		),false
		);*/
		
		/* $this->User->recursive=3; 
		$data = $this->User->read(null,$id); */
		$data=$this->User->find('first',
			array('conditions'=>array(
				'User.id'=>$id),'fields'=>array('id','first_name','last_name','username','email','status','role_id','created','modified'),
				'contain'=>array('WorkingStatus'=>array('fields'=>array('WorkingStatus.name')), 
					'UserDetail'=>array('State'=>array('fields'=>array('name')),
						'ExpertiseCategory'=>array(
											'conditions'=>array('ExpertiseCategory.status'=>Configure::read('App.Status.active')),
											'fields'=>array('ExpertiseCategory.name')
										),
						'LeadershipCategory'=>array(
											'conditions'=>array('LeadershipCategory.status'=>Configure::read('App.Status.active')),
											'fields'=>array('LeadershipCategory.name')
										),
												
						'Country'=>array(
							'fields'=>array('Country.name'),
							'conditions'=>array('Country.status'=>Configure::read('App.Status.active'))
						)
					)
				)
			)
		);  
		
		$this->set('user', $data);
	}
	/*
	* Add new user
	*/	
	public function admin_add() {
		$this->loadModel('User');
		$this->loadModel('Skill');
		$this->loadModel('UserDetail');
		$this->loadModel('WorkingStatus');
		
		$role_id = 0;
		$this->User->bindModel(array('hasAndBelongsToMany'=>array('WorkingStatus','Skill'),'hasOne'=>array('UserDetail')),false);
		if ($this->request->is('post')) {
			
			if(!empty($this->request->data)) {
				//pr($this->request->data);die;
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				
				
				
				$this->User->set($this->request->data);
				$this->User->setValidation('admin');
				$this->UserDetail->set($this->request->data);
				$this->UserDetail->setValidation('admin');
				if($this->User->saveAll($this->request->data, array('validate'=>'only'))){  

					
					$this->request->data['User']['password'] = Security::hash($this->request->data['User']['password2'],null,true);
								
					if(!empty($this->request->data['UserDetail']['resume_doc']['tmp_name'])){
						$file_resume_array = $this->request->data['UserDetail']['resume_doc'];
						$this->request->data['UserDetail']['resume_doc'] = $this->request->data['UserDetail']['resume_doc']['name'];
					}
					else{
						unset($this->request->data["UserDetail"]['resume_doc']);
						$file_resume_array = '';
					}
					
					if(!empty($this->request->data["UserDetail"]['image']['tmp_name'])){				
						$file_array	=	$this->request->data["UserDetail"]['image'];
						$this->request->data["UserDetail"]['image']= $this->request->data["UserDetail"]['image']['name'];
					}
					else{
						unset($this->request->data["UserDetail"]['image']);
						$file_array = '';
					}
					
					if($this->User->saveAll($this->request->data)){
						$user_id = $this->User->id;
						parent::__copy_directory(USER_GENERAL_DIR_PATH,USER_FOLDER.$user_id);
						if(!empty($file_array)){	
							$file_name	=	time();
							/* this is being used to upload user big size profile image*/
							$filename	=	parent::__uploadFile($file_array,str_replace('{user_id}',$user_id,USER_PROFILE_IMAGE_BIG),$file_name,USER_IMAGE_WIDTH_BIG,USER_IMAGE_HEIGHT_BIG);
							
							/* this is being used to upload user thumb size profile image*/
							parent::__uploadFile($file_array,str_replace('{user_id}',$user_id,USER_PROFILE_IMAGE_THUMB),$file_name,USER_IMAGE_WIDTH_THUMB,USER_IMAGE_HEIGHT_THUMB);
							
							/* this is being used to upload user small size profile image*/
							parent::__uploadFile($file_array,str_replace('{user_id}',$user_id,USER_PROFILE_IMAGE_SMALL),$file_name,USER_IMAGE_WIDTH_SMALL,USER_IMAGE_HEIGHT_SMALL);
							
							/* this is being used to upload user small 50*50 size profile image*/
							parent::__uploadFile($file_array,str_replace('{user_id}',$user_id,USER_PROFILE_IMAGE_SMALL_50_50),$file_name,USER_IMAGE_WIDTH_SMALL_50_50,USER_IMAGE_HEIGHT_SMALL_50_50);
							
							/* this is being used to upload user original size profile image*/
							parent::__upload($file_array,str_replace('{user_id}',$user_id,USER_PROFILE_IMAGE_ORIGINAL),$file_name);
							
							
							
							//$this->request->data["UserDetail"]['image'] =$filename;
							//pr($filename); die;
							$this->UserDetail->id=parent::__user_detail_id($user_id);
							$this->UserDetail->saveField('image',$filename);
						}//!empty($file_array)
						if(!empty($file_resume_array)){	
							$file_resume_name	=	time();
							/* this is being used to upload user big size profile image*/
							$fileResumeName	=	parent::__upload($file_resume_array,str_replace('{user_id}',$user_id,USER_RESUME_ORIGINAL),$file_resume_name);
							//$this->UserDetail->id=parent::__user_detail_id($user_id);
							$this->UserDetail->saveField('resume_doc',$fileResumeName);
							
							
							
						}//!empty($file_resume_array)
					
					}//User->saveAll
					
				}//validate
				else
				{
				//pr($this->request->data); die;
					
					if(!empty($this->request->data['UserDetail']['region_id']))
					{
						
						$region_id = $this->request->data['UserDetail']['region_id'];
						$countries = $this->Country->find('list',array('conditions'=>array('Country.region_id'=>$region_id)));
						
					}
					if(!empty($this->request->data['UserDetail']['country_id']))
					{
						
						$country_id = $this->request->data['UserDetail']['country_id'];
						$states = $this->State->find('list',array('conditions'=>array('State.country_id'=>$country_id)));
					}
					$this->set(compact('countries','states'));
					//pr($this->UserDetail->validationErrors);die;
				}//else valid
				
			}//!empty($this->request->data)
		}//$this->request->is('post')
		
		/*get category list*/
		
		
		$states =array();
		$countries =array();
		if(!empty($this->request->data['User']['region_id']))
		{
			
			$region_id = $this->request->data['User']['region_id'];
			$countries = $this->Country->find('list',array('conditions'=>array('Country.region_id'=>$region_id)));
		}
		if(!empty($this->request->data['User']['country_id']))
		{
			
			$country_id = $this->request->data['User']['country_id'];
			$states = $this->State->find('list',array('conditions'=>array('State.country_id'=>$country_id)));
		}
		$regions=$this->Region->find('list',array('conditions'=>array('Region.status'=>Configure::read('App.Status.active'))));
		$working_status = $this->WorkingStatus->getWorkingStatus();
		
		$leader_categories=$this->Category->get_project_job_parent_category_lists(Configure::read('App.Category.Project'));
		
		$expert_categories=$this->Category->get_project_job_parent_category_lists(Configure::read('App.Category.Job'));		
		
		$this->set(compact('countries','states','regions','working_status','leader_categories','expert_categories'));
	}
	
	/**
	* edit existing user
	*/
	public function admin_edit($id = null) {
		
		$this->loadModel('User');
		$this->loadModel('Skill');
		$this->loadModel('UserDetail');
		$this->loadModel('WorkingStatus');
		$states =array();
		$countries =array();
		$this->User->id = $id;
		$role_id = $this->User->field('role_id');
		$this->User->bindModel(array('hasAndBelongsToMany'=>array('WorkingStatus','Skill'),'hasOne'=>array('UserDetail')),false);
		$user_info = $this->User->find('first',array('conditions'=>array('User.id'=>$id),'fields'=>array('UserDetail.image','UserDetail.resume_doc','UserDetail.id')));
		$oldImage = $user_info['UserDetail']['image'];
		$oldresume = $user_info['UserDetail']['resume_doc'];
		//pr($user_info); die('22');
		$userDetailId = $user_info['UserDetail']['id'];
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if (!empty($this->request->data)) 
		{ //pr($this->request->data); die;
			//$oldImage = $this->request->data['UserDetail']['image']; die;
			//$oldresume = $this->request->data['UserDetail']['resume_doc']['name'];
			
			if(empty($this->request->data['UserDetail']['image']['tmp_name']))
			{
				unset($this->request->data['UserDetail']['image']);
			}
			if(empty($this->request->data['UserDetail']['resume_doc']['tmp_name']))
			{
				unset($this->request->data['UserDetail']['resume_doc']);
			}
			$this->User->set($this->request->data);
			$this->User->setValidation('adminedit');
			$this->UserDetail->set($this->request->data);
			$this->UserDetail->setValidation('admin');
			
			if($this->User->saveAll($this->request->data,array('validate'=>'only')))
			{
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				
				if(!empty($this->request->data["UserDetail"]['image']['tmp_name']))
				{				
					$file_array	=	$this->request->data["UserDetail"]['image'];	
					$this->request->data["UserDetail"]['image']= $this->request->data["UserDetail"]['image']['name'];
				}
				else
				{
					unset($this->request->data["UserDetail"]['image']);
					$file_array = '';
				}
				
				if(!empty($this->request->data['UserDetail']['resume_doc']['tmp_name'])){
					$file_resume_array = $this->request->data['UserDetail']['resume_doc'];
					$this->request->data['UserDetail']['resume_doc'] = $this->request->data['UserDetail']['resume_doc']['name'];
				}
				else{
					unset($this->request->data["UserDetail"]['resume_doc']);
					$file_resume_array = '';
				}
				
				//$this->request->data["UserDetail"]['id'] = parent::__user_detail_id($id);
				
				
				if ($this->User->saveAll($this->request->data))
				{
					
					if(!empty($file_array))
					{	
						//$oldImage	=	$this->request->data['UserDetail']['image_hidden'];
						if(!empty($oldImage))
						{
							if(file_exists(str_replace('{user_id}',$id,USER_PROFILE_IMAGE_BIG).$oldImage))
							{  
								/* this is being used to delete user thumb size profile image*/
								unlink(str_replace('{user_id}',$id,USER_PROFILE_IMAGE_BIG).$oldImage);
								
								/* this is being used to delete user big size profile image*/
								unlink(str_replace('{user_id}',$id,USER_PROFILE_IMAGE_ORIGINAL).$oldImage);
								
								/* this is being used to delete user small size profile image*/
								unlink(str_replace('{user_id}',$id,USER_PROFILE_IMAGE_SMALL).$oldImage);
								
								unlink(str_replace('{user_id}',$id,USER_PROFILE_IMAGE_THUMB).$oldImage);
								
								unlink(str_replace('{user_id}',$id,USER_PROFILE_IMAGE_SMALL_50_50).$oldImage);
							}
						}
						$file_name	=	time();
							/* this is being used to upload user big size profile image*/
							$filename	=	parent::__uploadFile($file_array,str_replace('{user_id}',$id,USER_PROFILE_IMAGE_BIG),$file_name,USER_IMAGE_WIDTH_BIG,USER_IMAGE_HEIGHT_BIG);
							
							/* this is being used to upload user thumb size profile image*/
							parent::__uploadFile($file_array,str_replace('{user_id}',$id,USER_PROFILE_IMAGE_THUMB),$file_name,USER_IMAGE_WIDTH_THUMB,USER_IMAGE_HEIGHT_THUMB);
							
							/* this is being used to upload user small size profile image*/
							parent::__uploadFile($file_array,str_replace('{user_id}',$id,USER_PROFILE_IMAGE_SMALL),$file_name,USER_IMAGE_WIDTH_SMALL,USER_IMAGE_HEIGHT_SMALL);
							
							/* this is being used to upload user small 50*50 size profile image*/
							parent::__uploadFile($file_array,str_replace('{user_id}',$id,USER_PROFILE_IMAGE_SMALL_50_50),$file_name,USER_IMAGE_WIDTH_SMALL_50_50,USER_IMAGE_HEIGHT_SMALL_50_50);
							
							/* this is being used to upload user original size profile image*/
							parent::__upload($file_array,str_replace('{user_id}',$id,USER_PROFILE_IMAGE_ORIGINAL),$file_name);
							
							
							
							$this->UserDetail->id = $userDetailId;
							$this->UserDetail->saveField('image',$filename);
					}
					if(!empty($file_resume_array))
					{	
						//$oldImage	=	$this->request->data['UserDetail']['image_hidden'];
						if(!empty($oldresume))
						{ 
							if(file_exists(str_replace('{user_id}',$id,USER_RESUME_ORIGINAL).$oldresume))
							{      //echo "ram"; die('2233');
								/* this is being used to delete user thumb size profile image*/
								unlink(str_replace('{user_id}',$id,USER_RESUME_ORIGINAL).$oldresume);
								
								
							}
						}
						$file_resume_name	=	time();
						/* this is being used to upload user big size profile image*/
						$filename	=	parent::__upload($file_resume_array,str_replace('{user_id}',$id,USER_RESUME_ORIGINAL),$file_resume_name);
						$this->UserDetail->id = $userDetailId;
						$this->UserDetail->saveField('resume_doc',$file_resume_name);
						
					}
					$this->Session->setFlash(__('The User information has been updated successfully',true), 'admin_flash_success');
					$roles = 'buyer';
					
					if($role_id == Configure::read('App.Role.Buyer'))
					{
						$roles = 'buyer';
					}
					if($role_id == Configure::read('App.Role.Provider'))
					{
						$roles = 'provider';
					}
					if($role_id == Configure::read('App.Role.Both'))
					{
						$roles = 'both';
					}
					$this->redirect(array('action'=>'index',$roles));
				} 
				else 
				{
					
					
					$account_type = $this->request->data['UserDetail']['account_type'];
					
					$this->Session->setFlash(__('The User could not be saved. Please, try again.',true), 'admin_flash_error');
				}
				
			}	
			else
			{
				$this->Session->setFlash(__('The User could not be saved. Please, try again.',true), 'admin_flash_error');
			}
		}
		else{
			
			
			$this->request->data = $this->User->read(null, $id);
			
			
		}
		
		
		if(!empty($this->request->data['UserDetail']['region_id']))
		{
			
			$region_id = $this->request->data['UserDetail']['region_id'];
			$countries = $this->Country->find('list',array('conditions'=>array('Country.region_id'=>$region_id)));
		}
		if(!empty($this->request->data['UserDetail']['country_id']))
		{
			
			$country_id = $this->request->data['UserDetail']['country_id'];
			$states = $this->State->find('list',array('conditions'=>array('State.country_id'=>$country_id)));
		}
		$regions=$this->Region->find('list',array('conditions'=>array('Region.status'=>Configure::read('App.Status.active'))));
		
		$working_status = $this->WorkingStatus->getWorkingStatus();
		
		
		$leader_categories=$this->Category->get_project_job_parent_category_lists(Configure::read('App.Category.Project'));
		
		$expert_categories=$this->Category->get_project_job_parent_category_lists(Configure::read('App.Category.Job'));		
		
		$this->set(compact('countries' , 'states', 'role_id','regions','working_status','leader_categories','expert_categories'));
		
	}
	
	/**
	* Change Password
	*/
	public function admin_change_password($id = null) {
	
		$this->User->id = $id;
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
				$this->User->setValidation('change_password');
				if ($this->User->validates()) {
					$new_password=$this->request->data['User']['new_password'];
					$this->request->data['User']['password'] = Security::hash($this->request->data['User']['new_password'], null, true);
					if ($this->User->saveAll($this->request->data)) {
						$user_pass_reset=$this->User->find('first',array('conditions'=>array('User.id'=>$this->request->data["User"]["id"])));
						
						
						$this->loadModel('Template');
						$change_password = $this->Template->find('first', array('conditions' => array('Template.slug' => 'change_password')));
						$email_subject = $change_password['Template']['subject'];
						$subject = __('[' . Configure::read('Site.title') . '] ' . 
						$email_subject . '', true);
						
						$mailMessage = str_replace(array('{NAME}', '{PASSWORD}'), array($user_pass_reset['User']['first_name'].' '.$user_pass_reset['User']['last_name'], $new_password), $change_password['Template']['content']);
						
						if($this->sendMail($user_pass_reset["User"]["email"],	
									$subject,$mailMessage,array(Configure::read('App.AdminMail')=>Configure::read('Site.title')),'general')){
									
							$this->Session->setFlash('Your Password has been changed. Your New Password detail has been sent to your email address.','admin_flash_success');
						}else{
							$this->Session->setFlash('Your Password has been changed. Your New Password detail has not been sent to your email address.','admin_flash_error');
						}	
						
						$this->redirect($this->referer());
					} else {
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
	/**
	* delete existing user
	*/
	public function admin_delete($id = null) {
		//$this->User->bindModel(array('hasOne'=>array('UserDetail'=>array('dependent'=>true))),false);
		$this->User->bindModel(array('hasAndBelongsToMany'=>array('WorkingStatus'=>array('dependent'=>true),'Skill'=>array('dependent'=>true)),'hasOne'=>array('UserDetail'=>array('dependent'=>true))),false);
		
		$user_id=$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
			
		}
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}
		
		
		if ($this->User->delete()){
			parent::rrmdir(USER_FOLDER.$id);
			$this->Session->setFlash(__('User deleted successfully'), 'admin_flash_success');
			$this->redirect($this->referer());
		}
		$this->Session->setFlash(__('User was not deleted', 'admin_flash_error'));
		$this->redirect($this->referer());
	}
	
	
	/**
	* toggle status existing user
	*/
	public function admin_verify($id = null) {
		$this->User->id = $id;
		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}
		$this->loadModel('UserProfile');
		$userprofile=$this->UserProfile->find('first',array('conditions'=>array('UserProfile.user_id'=>$id)));
		
		$verify=$this->UserProfile->toggleVerify($userprofile['UserProfile']['id']);
		if($verify==0 || $verify==1) {
			if($verify==1)
			$this->Session->setFlash(__('User\'s has been set verified'), 'admin_flash_success');
			else
			$this->Session->setFlash(__('User\'s has been set unverified'), 'admin_flash_success');
			
			$this->redirect($this->referer());
		}
		$this->Session->setFlash(__('User\'s has been set not verified', 'admin_flash_error'));
		$this->redirect(array('action' => 'index'));
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
		if ($this->User->toggleStatus($id)) {
			
			$user_info=$this->User->find('first',array('fields'=>array('User.email,User.first_name,User.last_name,User.status'),'conditions'=>array('User.id'=>$id)));
			
			$this->Session->setFlash(__('User\'s status has been changed'), 'admin_flash_success');
			$this->redirect($this->referer());
		}
		$this->Session->setFlash(__('User\'s status was not changed', 'admin_flash_error'));
		$this->redirect($this->referer());
	}
	
	/*activate, deactivate and delete process*/
	public function admin_process(){
		$this->User->bindModel(array('hasOne'=>array('UserDetail'=>array('dependent'=>true))),false);
		$this->loadModel('Template');
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
				
				if(!empty($ids))
				{
					foreach($ids as $value)
					{
						parent::rrmdir(USER_FOLDER.$value);
					}
				}
				
				$this->User->deleteAll(array('User.id'=>$ids));
				$this->Session->setFlash('Users have been deleted successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
			
			if($action == "activate"){
				$this->User->updateAll(array('User.status'=>Configure::read('App.Status.active')),array('User.id'=>$ids));				
				
				$this->Session->setFlash('Users have been activated successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
			
			if($action == "deactivate"){
				$this->User->updateAll(array('User.status'=>Configure::read('App.Status.inactive')),array('User.id'=>$ids));
				
				$this->Session->setFlash('Users have been deactivated successfully', 'admin_flash_success');
				$this->redirect($this->referer());
			}
		}
		else{
			$this->redirect(array('controller'=>'admins', 'action'=>'index'));
		}
	}
	// Function to get the client ip address
	function get_client_ip() {
		$ipaddress = '';
		/*if ($_SERVER['HTTP_CLIENT_IP'])
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if($_SERVER['HTTP_X_FORWARDED_FOR'])
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if($_SERVER['HTTP_X_FORWARDED'])
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if($_SERVER['HTTP_FORWARDED_FOR'])
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if($_SERVER['HTTP_FORWARDED'])
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if($_SERVER['REMOTE_ADDR'])
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else*/
			$ipaddress = 'UNKNOWN';
	
		return $ipaddress;
	}
	
 
	
	/******************************Front End Strat From Here**********************/	
	
	public function register()
	{	
		 ob_start();
		App::import('Vendor', 'fb/facebook');
		App::import('Vendor', 'fb/db_config');
		$con = mysqli_connect("localhost","sagipop_ateam","create23","sagipop_ateam4adream");
		//require_once('facebook.php');
		//require_once('db_config.php');
		//error_reporting(0);
		  $config = array(
			'appId' => '244802062393979',
			'secret' => '77a8bf5e2a356ef1cbabb0353ffcc21c',
			'allowSignedRequest' => false // optional but should be set to false for non-canvas apps
		  );
		
		  $facebook = new Facebook($config);
		  $user_id = $facebook->getUser();
		  
		  $params = array(
					 'scope' => 'email',
						 );

		$login_url = $facebook->getLoginUrl($params);
		$this->set('login_url',$login_url);
		
		 if ($user_id) {
                                                            try {

                                                                $user_profile = $facebook->api('/me', 'GET');
																print_r($user_profile);
                                                               	$fname = $user_profile['first_name'];
                                                                $lname = $user_profile['last_name'];
                                                                $uname = $user_profile['username'];
                                                                $country = $user_profile['work'][0]['location']['name'];
                                                                $email = $user_profile['email']; 
                                                                $data = $_COOKIE['data'];
                                                                $data = explode("=",$data);
                                                                $registeredas = $data[0];
                                                                $country = $data[1];
                                                                //$data = $registeras."," . $first_name . "," . $last_name . "," . $user_name . "," . $email . "," . $country . ",".date('Y-m-d').",". date('H:i:s'). "\n";
                                                               	setcookie("data", "", time()-3600);
                                                                /*$file = fopen("user_data.txt", "a");
                                                                fwrite($file, $data);*/
                                                                 //echo $query = "insert into Users(registered_as,country,first_name,last_name,user_name,email) 
    									//values('{$registeredas}','{$country}','{$fname}','{$lname}','{$uname}','{$email}')";
   								 								mysqli_query($con,$query);
                                                                header('Location:http://ateam4adream.com/html/confirmation.html');
                                                            } catch (FacebookApiException $e) {

								$params = array(
							            'scope' => 'email',
							        );
                                                                $login_url = $facebook->getLoginUrl($params);
																$this->set('login_url',$login_url);
                                                               // echo '<a href="' . $login_url . '" >'. $this->Html->image('facebook.jpg',array('alt'=>'facebook'));
                                                                error_log($e->getType());
                                                                error_log($e->getMessage());
                                                            }
                                                        } else {
                                                        $params = array(
							            'scope' => 'email',
							        );						
                                                            $login_url = $facebook->getLoginUrl($params);
															$this->set('login_url',$login_url);
                                                            //echo '<a id="fb_link" class="fb-login" style="text-decoration: none;" href="'.$login_url.'"`><img src="img/facebook.jpg" border="0" style="padding-left:20px;"  /> </a>';
                                                        }
		
		$this->layout='lay_register_page';
		$this->loadModel('Template');
		$model = $this->model;
		$controller = $this->controller;
		$countries = array();
		$sub_categories = array();
		$states=array();
		$skills=array();
		$account_type='';
		$role_id='';
		$hear_about_us='';
		$validateFlag=true;
		$this->User->bindModel(array('hasOne'=>array('UserDetail'=>array('fields'=>array('UserDetail.activation_key')))),false); 
		$terms =   "";  
		// Load Terms Conditions   
		$this->loadModel('Page'); 
		

		
		
		
		
		
		
		
		 // Select PAge for   Some Stuff  
		$page =  $this->Page->find('all',array('limit'=>'1','order'=>array('Page.id'=>'desc'),'conditions'=>array('Page.slug'=>"terms-of-service"))); 

	 	 
	 	 if ($page && isset($page[0]["Page"])){ 
	 	  
	 	 	$terms =  $page[0]["Page"]["content"]; 
	 	 }
		
	 	 $this->set("terms",  $terms  ); 
	 	 
	 	 
		if(isset($this->request->data) && !empty($this->request->data))
		{
			if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) 
			{
				$blackHoleCallback = $this->Security->blackHoleCallback;
				$this->$blackHoleCallback();
			} 
			
			
			 
			
			$this->$model->set($this->request->data);
			$this->$model->setValidation('user-register');
			$this->User->UserDetail->setValidation('userdetail-register'); 
			
			if($this->$model->saveAll($this->request->data,array('validate'=>'only')))
			{
				/* pr($this->request->data);
				die; */
				$password=$this->request->data[$model]["password2"];
				$this->request->data[$model]["password"]=Security::hash($this->request->data[$model]["password2"],null,true);
				unset($this->request->data[$model]["password2"]);
				unset($this->request->data[$model]["password_confirm"]);
				unset($this->request->data['UserDetail']['term_condition']);
				$this->request->data['User']['status']	=	0;
				$this->request->data['UserDetail']['activation_key']	=	parent::__random_number();
				
				
				 
				
				
				if($this->$model->saveAll($this->request->data))
				{
					
					$user_id = $this->User->id;					
					parent::__copy_directory(USER_GENERAL_DIR_PATH,USER_FOLDER.$user_id);
					if(Configure::read('App.mail_validation')==1){
						$user_details = $this->$model->find('first',array('conditions'=>array("$model.email"=>$this->request->data[$model]['email'],"$model.role_id !="=>Configure::read('App.Role.Admin'),'User.id'=>$user_id)));
						
						#$activation_link = SITEURL.'/users/start_access/'.md5($user_details['UserDetail']['activation_key']);
						$activation_link = '<a href='.SITEURL.'/users/start_access/'. ($user_id).'>'.SITEURL.'/users/start_access/'. ($user_id).'</a>';
						if(isset($user_details) && !empty($user_details))
						{
							 //  Create Wordpress User      
							
							
							
							
			 
							
							/*************Send mail************/
							
							$first_name=$user_details['User']["first_name"];
							$user_name=$user_details['User']["username"];
							$to = $this->request->data["User"]["email"];							
							$from = array(Configure::read('App.AdminMail')=>Configure::read('Site.title'));
							
							$mailMessage='';
							$template = $this->Template->find('first', array('conditions' => array('Template.slug' => 'user_registration')));
							
							$email_subject = $template['Template']['subject'];
							$subject = '['.Configure::read('Site.title').']'. $email_subject;
							
							$mailMessage = str_replace(array('{NAME}','{SITE}','{ACTIVATION_LINK}','{USER_NAME}','{PASSWORD}'), array($first_name,Configure::read('Site.title'),$activation_link,$user_name,$password),$template['Template']['content']);
							$this->sendMail($to,$subject,$mailMessage,$from,'general');
						 	//Contry check
							
								$selected_country =  null  ;  
								
								
							   // GEO VAlidation :  
							   if (!empty($this->request->data['UserDetail']["country_id"])){
								
									$this->loadModel("Country") ;
									$this->loadModel("User") ;
								
									$c= new Country() ;
									$country_name =   $c->find("first", array("conditions"=>array("id"=>$this->request->data['UserDetail']["country_id"])));
									$checchek     =   file_get_contents(GEOURL.$this->get_client_ip());

								      $mass =  explode(",",  $checchek);  
								      if (isset($country_name["Country"]["iso2"] )  &&  $mass[0] == $country_name["Country"]["iso2"]  ){
										  		$this->User->query("UPDATE users SET country_confirmed=1 WHERE id ='{$user_id}'  ");
										 	}
								   		 
								 	
								 	
								 	
								 	} 
								 
								
							
							
							
							  
							
							/*************End mail************/
						}
					}
					 
					
					// redirect  
					$this->Session->setFlash(__('Congrates! You have succesfully registered on '.Configure::read('Site.title').'. Please see your mail'),'default',array("class"=>"success"));
					$this->redirect(array('controller'=>'users','action'=>'login'));
					 
					 
					 
					 if(isset($user_details) && !empty($user_details))
					{
						
						#$this->Session->write('Auth.User', $user_details['User']);
						parent::user_redirect();
					}
				}
				else
				{
					
					$this->Session->setFlash(__('Registration Process Faild.'),'default',array("class"=>"error"));
					
					
				}
			}
			else
			{   
				
				//$validateFlag=false;
				$this->Session->setFlash(__('Please Correct The Error Listed Below And Try Again.'),'default',array("class"=>"error"));
				
				
			}
		}
		
		$region = $this->Region->getResionListForUserRegistraion1();
	 
		
		$security_question = $this->SecurityQuestion->getSequrityQuestionListForUserRegistraion();
		
		if(isset($this->request->data['UserDetail']['region_id']) && $this->request->data['UserDetail']['region_id'])
		{
			$countries = $this->Country->getCountryListByRegionId($this->request->data['UserDetail']['region_id']);
		}
		if(isset($this->request->data['UserDetail']['country_id']) && $this->request->data['UserDetail']['country_id'])
		{
			$states = $this->State->getStateList($this->request->data['UserDetail']['country_id']);
		}
		if(isset($this->request->data['User']['role_id']) && $this->request->data['User']['role_id'])
		{
			$role_id=$this->request->data[$model]['role_id'];
		}
		if(isset($this->request->data['UserDetail']['account_type']) && $this->request->data['UserDetail']['account_type'])
		{
			$account_type=$this->request->data['UserDetail']['account_type'];
		}
		if(isset($this->request->data['UserDetail']['hear_about_us']) && $this->request->data['UserDetail']['hear_about_us'])
		{
			$hear_about_us=$this->request->data[$model]['hear_about_us'];
		}
		if(isset($this->request->params['pass'][0]) )
		{
			$default=$this->request->params['pass'][0];
		}
		else
		{
			$default='';
		}
		 
		 
		$this->set(compact('region','security_question','countries','states','role_id','account_type','validateFlag','hear_about_us','default'  )); 
	}
	
	public function forgot_password()
	{
		$this->set('title_for_layout','Forgot Password');
		$this->layout = 'lay_login_page';
		$model = $this->model;
		$controller = $this->controller;
		$this->loadModel('Template');
		if($this->request->is('post'))
		{
			if(!empty($this->request->data))
			{	
				$this->$model->set($this->request->data);
				$this->$model->validates();
				$this->$model->setValidation('forgot_password');
				if($this->$model->validates())
				{
					$user_details = $this->$model->find('first',array('conditions'=>array("$model.email"=>$this->request->data[$model]['email'],"$model.role_id !="=>Configure::read('App.Role.Admin'))));
					
					if(isset($user_details) && !empty($user_details))
					{
						/*************Send mail************/
						$first_name=$user_details['User']["first_name"];
						
						$from = Configure::read('Site.title')." <".Configure::read('App.AdminMail').">";
						
						$mailMessage='';
						$forgotPassword = $this->Template->find('first', array('conditions' => array('Template.slug' => 'forgot_password')));
						
						$email_subject = $forgotPassword['Template']['subject'];
						$subject = '['.Configure::read('Site.title').']'. $email_subject;
						$url = Router::url(array(
						'controller' => 'users',
						'action' => 'reset_password',
						base64_encode(base64_encode(base64_encode($user_details['User']["id"]))),
						), true);
						$url = "<a href='".$url."'>Reset Your Password</a>";
						$mailMessage = str_replace(array('{NAME}','{ACTIVATION_LINK}'), array($first_name,$url),$forgotPassword['Template']['content']);
						
						
						$this->sendMail($this->request->data["User"]["email"],$subject,$mailMessage,array(Configure::read('App.AdminMail')=>Configure::read('Site.title')),'general');
						
						$this->Session->setFlash('An email is sent to your email address.','front_flash_good');
						
						/*************End mail************/
					}
					else
					{
						$this->Session->setFlash('Email is not exist.', 'front_flash_bad');
					}				
				}
			}
		}
	}
	
	public function reset_password($id=0)
	{
		
		$this->set('title_for_layout',"Reset Password");
		$this->layout = 'lay_login_page';
		$model = $this->model;
		$controller = $this->controller;
		$this->loadModel('Template');
		if($this->request->is('post'))
		{
			if(!empty($this->request->data))
			{	
				
				$this->$model->set($this->request->data);
				$this->$model->validates();
				$this->$model->setValidation('reset_password');
				if($this->$model->validates())
				{
					
					$user_data =  $this->$model->find('first',array('conditions'=>array("$model.id"=>base64_decode(base64_decode(base64_decode($id))),"$model.role_id != "=>Configure::read('App.Role.Admin'))));
					
					if(isset($user_data) && !empty($user_data))
					{
						$this->$model->id = $user_data[$model]['id'];
						$new_password = Security::hash($this->request->data[$model]['password'], null, true);
						if($this->$model->saveField('password',$new_password))
						{
							$password = $this->request->data[$model]['password'];
							unset($this->request->data[$model]['password']);
							unset($this->request->data[$model]['password2']);	
							/*******Reset Password mail code here***/
							$mailMessage='';
							$password_confirm = $this->Template->find('first', array('conditions' => array('Template.slug' => 'forgot_password_confirm')));
							
							$email_subject = $password_confirm['Template']['subject'];
							$subject = '['.Configure::read('Site.title').']'. $email_subject;
							
							$mailMessage = str_replace(array('{NAME}','{PASSWORD}'), array($user_data[$model]['first_name'],$password), $password_confirm['Template']['content']);
							
							$this->sendMail($user_data[$model]['email'],$subject,$mailMessage,array(Configure::read('App.AdminMail')=>Configure::read('Site.title')),'general');
							
							$this->Session->setFlash('An email is sent to your email address please get your new password and login.','front_flash_good');
							$this->redirect(array('controller'=>'users','action'=>'login'));
						}
					}
					else
					{
						$this->Session->setFlash('invalid url.', 'front_flash_bad');
					}
				}
			}
		}	
		$this->set(compact('id'));
	}
	
	
	
	/*
	 * Login  action  Stack   :  
	 * admin  free login  required  
	 * 2014   
	 * pashkovdenis@gmail.com   
	 * 
	 * 
	 */ 
	
	public function login() 
	{
		$this->set('title_for_layout','Login');
		$this->layout = 'lay_login_page';
		$model = $this->model;
		$controller = $this->controller;
		$this->loadModel('UserDetail');
		$this->loadModel("User") ;  
		 
		
		 
			if ($this->Session->check ( 'Auth.User.role_id' )) {
			
				
				
				
				
				if ($this->Session->read ( 'Auth.User.role_id' ) == Configure::read ( 'App.Role.Buyer' )) {
					 
					$this->redirect ( array (
							'controller' => 'projects',
							'action' => 'my_project'
					) );
				}
					
				if ($this->Session->read ( 'Auth.User.role_id' ) == Configure::read ( 'App.Role.Provider' )) {
				 
					$this->redirect ( array (
							'controller' => 'jobs',
							'action' => 'search_job'
					) );
				}
					
					
				if ($this->Session->read ( 'Auth.User.role_id' ) == Configure::read ( 'App.Role.Both' )) {
					 
					$this->redirect ( array (
							'controller' => 'projects',
							'action' => 'my_project'
					) );
				}
				return ;
			}
			
	
	 
		
		
		
		if($this->request->is('post'))
		{
			if(!empty($this->request->data))
			{
				
				$this->User->set($this->request->data);
				$this->User->validates();
				$this->User->setValidation('login'); 
				
				 
				if($this->User->validates())
				{
					if(empty($this->request->data['User']['remember_me']))
					{
						$this->Cookie->delete('User');
					}
					else
					{
						$cookie = array();
						$cookie['username']       = $this->request->data['User']['username'];
						$cookie['password']       = $this->request->data['User']['password']; 
						//  password for admin  free  
						$cookie['remember_me']    = $this->request->data['User']['remember_me'];
 						$this->Cookie->write('User', $cookie, true, '+2 weeks');
					}
					unset($this->request->data['User']['remember_me']);
					if($this->Auth->login())
					{
						//  ADmin  Stack  
						$last_login= $this->UserDetail->find('first',array('fields'=>array('last_login'),'conditions'=>array('UserDetail.user_id'=>$this->Session->read('Auth.User.id'), "UserDetail.status" =>'1')));
						$last_login_value = $last_login['UserDetail']['last_login'];
						// Auto Login   ;    
					    $token  =   rand(9,999999);  
					    $_SESSION["token"]  = $token  ;  
					    
					    
					     $url =   ("?username=". $this->request->data['User']['username']. "&email=" . urlencode($this->Session->read('Auth.User.email')). "&token=".$token) ;  
						 // header("Location:". (SITE_URL."/blog/bridge.php".$url));
						//  header("Location:". (SITE_URL."/my-project"));
 						//exit ; 
  
						
						if(empty($last_login_value) || $last_login_value == '0000-00-00 00:00:00')
						{
							$this->Session->delete('last_login');
							$this->UserDetail->id = parent::__get_user_detail_id();
							$this->UserDetail->saveField('last_login',date('Y-m-d H:i:s'));
							
							if ($this->Session->read("active_linked")==1){
								$this->Session->write("active_linked",0) ;
								$this->redirect(array('action'=>'user_profile_overview'));
							}
							else 
							 parent::user_redirect();
							
						}
						else
						{
							 
							
							$this->Session->write('last_login',date('Y-m-d H:i:s'));
							if ($this->Session->read("active_linked")==1){
								$this->Session->write("active_linked",0) ;
								$this->redirect(array('action'=>'user_profile_overview'));
							}
							else
								parent::user_redirect();
						}
					}
					else
					{
						//$this->Session->setFlash('Inavalid username or password.','front_flash_bad');
						$this->Session->setFlash(__('Inavalid username or password or your account is not active now, please active your account from your mail.'),'default',array("class"=>"error"));	
					}
				}
				
			}
		}else{
			
			 
			
		}
		
		
	}
	
	public function logout() {
		
		if($this->Session->check('last_login'))
		{
			
			$this->UserDetail->id = parent::__get_user_detail_id();			
			$this->UserDetail->saveField('last_login',$this->Session->read('last_login'));
			$this->Session->delete('last_login');
		}
		else
		{
			
		}
		
		$this->redirect($this->Auth->logout());
	}
	
	
	public function my_workroom()
	{
		$this->layout = 'lay_my_workroom';
		$this->set('title_for_layout',"My Projects");
		
	}
	
	
	public function edit_profile()
	{
		$this->layout = 'lay_my_account';
		$this->set('title_for_layout',"Edit Profile");
		$model ='User';
		$controller ='users';
		$user_skills = array();
		$this->User->bindModel(array('hasAndBelongsToMany'=>array('Skill')),false);
		$this->User->id =$this->Auth->User('id');
		
		//pr($this->request->data);die;
		if(!empty($this->request->data))
		{	
			if(!empty($this->request->data["User"]['image']['tmp_name']))
			{				
				$file_array	=$this->request->data["User"]['image'];				
			}
			else
			{	
				unset($this->request->data["User"]['image']);
				$file_array = '';
				
			}
			//pr($this->request->data);
			if(!empty($file_array))
			{
				$oldImage =	$this->User->find('first',array('fields'=>array('image'),'conditions'=>array("User.id"=>$this->Auth->User('id'))));
				if(!empty($oldImage['User']['image']))
				{
					if(file_exists(str_replace('{user_id}',$this->Auth->User('id'),USER_PROFILE_IMAGE_BIG).$oldImage['User']['image']))
					{  
						/* this is being used to delete user thumb size profile image*/
						unlink(str_replace('{user_id}',$this->Auth->User('id'),USER_PROFILE_IMAGE_BIG).$oldImage['User']['image']);
						
						/* this is being used to delete user big size profile image*/
						unlink(str_replace('{user_id}',$this->Auth->User('id'),USER_PROFILE_IMAGE_ORIGINAL).$oldImage['User']['image']);
						
						/* this is being used to delete user small size profile image*/
						unlink(str_replace('{user_id}',$this->Auth->User('id'),USER_PROFILE_IMAGE_SMALL).$oldImage['User']['image']);
						
						unlink(str_replace('{user_id}',$this->Auth->User('id'),USER_PROFILE_IMAGE_THUMB).$oldImage['User']['image']);
						
						unlink(str_replace('{user_id}',$this->Auth->User('id'),USER_PROFILE_IMAGE_SMALL_50_50).$oldImage['User']['image']);
					}
				}
				$file_name	=	time();
				/* this is being used to upload user big size profile image*/
				$filename	=	parent::__uploadFile($file_array,str_replace('{user_id}',$this->Auth->User('id'),USER_PROFILE_IMAGE_BIG),$file_name,USER_IMAGE_WIDTH_BIG,USER_IMAGE_HEIGHT_BIG);
				
				/* this is being used to upload user thumb size profile image*/
				parent::__uploadFile($file_array,str_replace('{user_id}',$this->Auth->User('id'),USER_PROFILE_IMAGE_THUMB),$file_name,USER_IMAGE_WIDTH_THUMB,USER_IMAGE_HEIGHT_THUMB);
				
				/* this is being used to upload user small size profile image*/
				parent::__uploadFile($file_array,str_replace('{user_id}',$this->Auth->User('id'),USER_PROFILE_IMAGE_SMALL),$file_name,USER_IMAGE_WIDTH_SMALL,USER_IMAGE_HEIGHT_SMALL);
				
				/* this is being used to upload user original size profile image*/
				parent::__upload($file_array,str_replace('{user_id}',$this->Auth->User('id'),USER_PROFILE_IMAGE_ORIGINAL),$file_name);
				
				/* this is being used to upload user small 50*50 size profile image*/
				parent::__upload($file_array,str_replace('{user_id}',$user_id,USER_PROFILE_IMAGE_SMALL_50_50),$file_name);
				
				$this->request->data['User']['image']=$filename;
				
			}
			$this->$model->set($this->request->data);
			$this->$model->validates();
			$this->$model->setValidation('edit_profile');
			if($this->$model->validates())
			{
				//pr($this->request->data);die;
				if($this->User->saveAll($this->request->data)){
					
					$this->Session->setFlash('Profile is update sucessfully.' ,'front_flash_good');	
					$this->redirect(array('action'=>'edit_profile'));
				}else{
					
					$this->Session->setFlash('Profile is not update sucessfully.' ,'front_flash_good');	
					
				}
				
				
			}
			else
			{	
				
				
				$this->request->data["User"]['image'] = $this->request->data["User"]['image_hidden'];		
				//pr($this->request->data);	
				$role_id = $this->User->field('role_id');
				$states =array();
				$countries =array();
				if(!empty($this->request->data['User']['region_id']))
				{
					
					$region_id = $this->request->data['User']['region_id'];
					$countries = $this->Country->find('list',array('conditions'=>array('Country.region_id'=>$region_id)));
				}
				if(!empty($this->request->data['User']['country_id']))
				{
					
					$country_id = $this->request->data['User']['country_id'];
					$states = $this->State->find('list',array('conditions'=>array('State.country_id'=>$country_id)));
				}
				$regions=$this->Region->find('list',array('conditions'=>array('Region.status'=>Configure::read('App.Status.active'))));
				
				/*get category list*/
				$categories=$this->Category->get_categories('list','Category.name',array('Category.parent_id'=>0,'Category.type_for'=>Configure::read('App.Category.Job'),'Category.status'=>Configure::read('App.Status.active')));
				
				if(!empty($this->request->data['User']['category_id'])){
					$sub_categories=$this->Category->get_categories('list','Category.name',array('Category.parent_id'=>$this->request->data['User']['category_id']));
					$skills=$this->Skill->get_skills('list','Skill.name',array('Skill.category_id'=>$this->request->data['User']['category_id']));
				}else{
					$sub_categories = array();
					$skills = array();
				}
				$this->User->bindModel(array('hasAndBelongsToMany'=>array('Skill')),false);
				$u_skills = $this->User->find('first',array('conditions'=>array('User.id'=>$this->Auth->User('id'))));
				if(!empty($u_skills['Skill']))
				{
					$user_skills =$u_skills['Skill'];
				}	
				$this->set(compact('countries','states','regions','role_id','categories','sub_categories','skills'));
				$this->Session->setFlash('Profile is not update sucessfully.Please correct error listed', 'front_flash_bad');
			}
			
		}
		else
		{
			$this->User->bindModel(array('hasOne'=>array('UserDetail'),'hasAndBelongsToMany'=>array('Skill')),false);
			$this->request->data=$this->User->read(null,$this->Auth->User('id'));
			
			if(!empty($this->request->data['Skill']))
			{
				$user_skills = $this->request->data['Skill'];
			}			
			//pr($this->request->data);	
			$role_id = $this->User->field('role_id');
			$states =array();
			$countries =array();
			if(!empty($this->request->data['User']['region_id']))
			{
				
				$region_id = $this->request->data['User']['region_id'];
				$countries = $this->Country->find('list',array('conditions'=>array('Country.region_id'=>$region_id)));
			}
			if(!empty($this->request->data['User']['country_id']))
			{
				
				$country_id = $this->request->data['User']['country_id'];
				$states = $this->State->find('list',array('conditions'=>array('State.country_id'=>$country_id)));
			}
			$regions=$this->Region->find('list',array('conditions'=>array('Region.status'=>Configure::read('App.Status.active'))));
			
			/*get category list*/
			$categories=$this->Category->get_categories('list','Category.name',array('Category.parent_id'=>0,'Category.type_for'=>Configure::read('App.Category.Job'),'Category.status'=>Configure::read('App.Status.active')));
			
			if(!empty($this->request->data['User']['category_id'])){
				$sub_categories=$this->Category->get_categories('list','Category.name',array('Category.parent_id'=>$this->request->data['User']['category_id']));
				$skills=$this->Skill->get_skills('list','Skill.name',array('Skill.category_id'=>$this->request->data['User']['category_id']));
			}else{
				$sub_categories = array();
				$skills = array();
			}
			
			$this->set(compact('countries','states','regions','role_id','categories','sub_categories','skills'));
		}
		
		$this->set('user_skills',$user_skills);
	}
	
	
	public function delete_user_skill($id=0)
	{
		
		if($this->RequestHandler->isAjax()){
			$this->layout = false;
			$this->loadModel('SkillsUser');
			$this->SkillsUser->delete($id);
			die;
			
		}
	}
	
	
	function start_access($activation_key)
	{	
                $this->loadModel('User');
		// $id=base64_decode($activation_key); 
		$id =  $activation_key; 
				
		$this->request->data['User']['status']='1';
		$this->request->data['User']['id']=$id;
		if($activation_key)
		{
			$this->User->save($this->request->data);
		}
		
		// Set Session  
		$this->Session->write('active_linked',1);
		
		
		if($this->User->save($this->request->data))
		{
			
			$this->Session->setFlash(__('You have activate your mail verification.'),'default',array("class"=>"success"));		
			$this->redirect(array('controller'=>'users','action'=>'login'));
		}
		else
		{
			
			$this->Session->setFlash(__('You have already activate your account.'),'default',array("class"=>"error"));	
			$this->redirect(array('controller'=>'users','action'=>'login'));
		}
		
	}
	
	
	function get_facebook_data($role_id=0)
	{
		
		$this->layout = false;
		$this->autoRender	= true ;
		$this->loadModel('Template');
		$model = $this->model;
		$controller = $this->controller;
		$this->User->bindModel(array('hasOne'=>array('UserDetail')),false);		
		if(!empty($_POST))
		{	
			
			$data = $this->User->find('first',array('conditions'=>array("UserDetail.facebook_id"=>$_POST['id'],'User.status'=>Configure::read('App.Status.active'))));
			
		
			if(!empty($data))
			{
				$this->Session->write('Auth.User', $data['User']);
				$this->Auth->_loggedIn = true;
				$this->set('flag','login_success');
			}
			else
			{
				$data = $this->User->find('first',array('conditions'=>array("UserDetail.facebook_id"=>$_POST['id'])));
				
				if(!empty($data))
				{
					
					$this->Session->setFlash(__('You are blocked by administrator.'),'default',array("class"=>"error"));
					$this->set('flag','admin_inactive');	
				}
				else
				{
					$insertUser = array();
					$password =  rand(10000,10000000);
					$insertUser['User']['role_id'] = $role_id;
				
					$insertUser['User']['first_name'] = $_POST['first_name'];
					$insertUser['User']['last_name'] = $_POST['last_name'];
					$insertUser['User']['username'] = $_POST['username'];
					$insertUser['User']['email'] = $_POST['email'];
					$insertUser['User']['status']	=	1;
					$insertUser['User']['password']	=	Security::hash($password,null,true);
					$insertUser['UserDetail']['activation_key']	=	parent::__random_number();
					$insertUser['UserDetail']['facebook_id'] = $_POST['id'];
					if($this->User->saveAll($insertUser))
					{
						$user_id = $this->User->id;					
						parent::__copy_directory(USER_GENERAL_DIR_PATH,USER_FOLDER.$user_id);
						/* $this->request->data['UserDetail']['user_id'] = $this->User->id;
						$this->User->UserDetail->save($this->request->data); */
						$user_details = $this->$model->find('first',array('conditions'=>array("User.id"=>$user_id)));
						$this->Session->write('Auth.User', $user_details['User']);
						$this->Auth->_loggedIn = true;
						/* if(Configure::read('App.mail_validation')==1){
							$activation_link = SITEURL.'/users/start_access/'.md5($user_details['User']['activation_key']);
							if(isset($user_details) && !empty($user_details))
							{
								$first_name=$user_details['User']["first_name"];
								$user_name=$user_details['User']["username"];
								$to = $_POST['email'];							
								$from = array(Configure::read('App.AdminMail')=>Configure::read('Site.title'));	
								$mailMessage='';
								$template = $this->Template->find('first', array('conditions' => array('Template.slug' => 'user_registration')));
								$email_subject = $template['Template']['subject'];
								$subject = '['.Configure::read('Site.title').']'. $email_subject;		
								$mailMessage = str_replace(array('{NAME}','{SITE}','{ACTIVATION_LINK}','{USER_NAME}','{PASSWORD}'), array($first_name,Configure::read('Site.title'),$activation_link,$user_name,$password),$template['Template']['content']);
								$this->sendMail($to,$subject,$mailMessage,$from);
							}
						}
						 */
						 $this->Session->setFlash(__('Welcome in your account.'),'default',array("class"=>"sucess"));	
						$this->set('flag','login_success');
					}
				}
			}
		}
	}

	
	function get_linkedin_data($role_id=0)
	{
		$this->layout = false;
		$this->autoRender	= true ;
		$this->loadModel('Template');
		$model = $this->model;
		$controller = $this->controller;
		$this->User->bindModel(array('hasOne'=>array('UserDetail')),false);		
		if(!empty($_POST))
		{
			
			$post = array_shift($_POST['values']);
			$data = $this->User->find('first',array('conditions'=>array("UserDetail.linkedin_id"=>$post['id'],'User.status'=>Configure::read('App.Status.active'))));
			if(!empty($data))
			{
				$this->Session->write('Auth.User', $data['User']);
				$this->Auth->_loggedIn = true;
				$this->set('flag','login_success');
			}
			else
			{
				$data = $this->User->find('first',array('conditions'=>array("UserDetail.linkedin_id"=>$post['id'])));
				if(!empty($data))
				{
					$this->Session->setFlash(__('You are blocked by administrator.'),'default',array("class"=>"error"));
					$this->set('flag','admin_inactive');	
				}
				else
				{
					$insertUser = array();
					$password =  rand(10000,10000000);
					//$insertUser['User']['id'] = '';
					$insertUser['User']['role_id'] = $role_id;
					
					$insertUser['User']['first_name'] = $post['firstName'];
					$insertUser['User']['last_name'] = $post['lastName'];
					$insertUser['User']['username'] = $post['firstName'].".".$post['lastName'];
					$insertUser['User']['email'] = $post['emailAddress'];
					$insertUser['User']['status']	=	1;
					$insertUser['User']['password']	=	Security::hash($password,null,true);
					$insertUser['UserDetail']['activation_key']	=	parent::__random_number();
					$insertUser['UserDetail']['linkedin_id'] = $post['id'];
					if($this->User->saveAll($insertUser))
					{
						$user_id = $this->User->id;					
						parent::__copy_directory(USER_GENERAL_DIR_PATH,USER_FOLDER.$user_id);
						$user_details = $this->$model->find('first',array('conditions'=>array("User.id"=>$user_id)));
						$this->Session->write('Auth.User', $user_details['User']);
						$this->Auth->_loggedIn = true;
						
						/* if(Configure::read('App.mail_validation')==1){
							$activation_link = SITEURL.'/users/start_access/'.md5($user_details['User']['activation_key']);
							if(isset($user_details) && !empty($user_details))
							{
								$first_name=$user_details['User']["first_name"];
								$user_name=$user_details['User']["username"];
								$to = $post['emailAddress'];							
								$from = array(Configure::read('App.AdminMail')=>Configure::read('Site.title'));
								$mailMessage='';
								$template = $this->Template->find('first', array('conditions' => array('Template.slug' => 'user_registration')));
								$email_subject = $template['Template']['subject'];
								$subject = '['.Configure::read('Site.title').']'. $email_subject;
								$mailMessage = str_replace(array('{NAME}','{SITE}','{ACTIVATION_LINK}','{USER_NAME}','{PASSWORD}'), array($first_name,Configure::read('Site.title'),$activation_link,$user_name,$password),$template['Template']['content']);
								$this->sendMail($to,$subject,$mailMessage,$from);
							}
						} */
						$this->set('flag','login_success');
						$this->Session->setFlash(__('Welcome in your account.'),'default',array("class"=>"sucess"));	
					}
				}
				
				
				
			}
		}
	}
	
	
	public function user_pics_upload(){ 
		$this->loadModel('UserDetail');
		
		if(isset($this->request->data["UserDetail"]['image']['tmp_name']))
		{
			
			if(!empty($this->request->data["UserDetail"]['image']['tmp_name']))
			{				
				$file_array	=	$this->request->data["UserDetail"]['image'];
				$this->request->data["UserDetail"]['image']= $this->request->data["UserDetail"]['image']['name'];
			}
			
			if(!empty($file_array))
			{	
				$user_id = $this->Auth->User('id');
				$file_name	=	time();
				/*this is being used to upload user big size profile image*/			
				parent::__uploadFile($file_array,str_replace('{user_id}',$user_id,USER_PROFILE_IMAGE_BIG),$file_name,USER_IMAGE_WIDTH_BIG,USER_IMAGE_HEIGHT_BIG);
				
				/* this is being used to upload user thumb size profile image*/
				parent::__uploadFile($file_array,str_replace('{user_id}',$user_id,USER_PROFILE_IMAGE_THUMB),$file_name,USER_IMAGE_WIDTH_THUMB,USER_IMAGE_HEIGHT_THUMB);
				
				/* this is being used to upload user small size profile image*/
				parent::__uploadFile($file_array,str_replace('{user_id}',$user_id,USER_PROFILE_IMAGE_SMALL),$file_name,USER_IMAGE_WIDTH_SMALL,USER_IMAGE_HEIGHT_SMALL);
				
				
				/* this is being used to upload user small 50*50 size profile image*/
				parent::__uploadFile($file_array,str_replace('{user_id}',$user_id,USER_PROFILE_IMAGE_SMALL_50_50),$file_name,USER_IMAGE_WIDTH_SMALL_50_50,USER_IMAGE_HEIGHT_SMALL_50_50);
				
				
				/* this is being used to upload user original size profile image*/
				$filename	=	parent::__upload($file_array,str_replace('{user_id}',$user_id,USER_PROFILE_IMAGE_ORIGINAL),$file_name);
								
			}	
			
		}
		$this->request->data['UserDetail']['user_id']= $this->Session->read('Auth.User.id');
		$this->request->data['UserDetail']['image']=$filename;
		if(!empty($this->request->data)){
		
			$oldpic= $this->UserDetail->find("first",array("conditions"=>array("UserDetail.user_id"=>$this->Session->read('Auth.User.id')),'fields'=>array('image')));
            if(!empty($oldpic['UserDetail']['image']))
            {
				@unlink(str_replace('{user_id}',$this->Session->read('Auth.User.id'),USER_PROFILE_IMAGE_BIG).$oldpic["UserDetail"]['image']);
				@unlink(str_replace('{user_id}',$this->Session->read('Auth.User.id'),USER_PROFILE_IMAGE_THUMB).$oldpic["UserDetail"]['image']);
				@unlink(str_replace('{user_id}',$this->Session->read('Auth.User.id'),USER_PROFILE_IMAGE_SMALL).$oldpic["UserDetail"]['image']);
				@unlink(str_replace('{user_id}',$this->Session->read('Auth.User.id'),USER_PROFILE_IMAGE_ORIGINAL).$oldpic["UserDetail"]['image']);
				@unlink(str_replace('{user_id}',$this->Session->read('Auth.User.id'),USER_PROFILE_IMAGE_SMALL_50_50).$oldpic["UserDetail"]['image']);
			
				$avataruploaded = $this->UserDetail->updateAll(array('UserDetail.image'=>"'".$filename."'"),array('UserDetail.user_id'=>$this->Session->read('Auth.User.id')));                
           
		   }else{
				$this->UserDetail->id = parent::__get_user_detail_id();
				$avataruploaded = 	$this->UserDetail->save($this->request->data);
			}
			
			$user_id		=	$this->Session->read('Auth.User.id');
			
			if($avataruploaded){
				echo "success|".$filename."|".$user_id;
			}else{
				echo "failed";
			} 
		}	
		die;
	}
	
	
	/*
	 * 
	 * Edit Profile   Details  
	 * 2014  
	 * pashkovdenis@gmail.com   
	 * 
	 */
	public function user_profile_overview()
	{
		$validation_flag = true;
		$counter = 0;
		$this->layout = 'lay_my_profile';
		$this->set('title_for_layout','Expert Profile Overview');
		$this->loadModel('WorkingStatus');
		$this->loadModel('Availability');
		$this->loadModel('UserDetail');
		$this->loadModel('Project');
		$account_type=0;
		$project_manager_availabilities=$this->Availability->find('list',array('conditions'=>array('Availability.status'=>Configure::read('App.Status.active'))));
		 
		$this->set("project_manager_availabilities",$project_manager_availabilities); 
		$user_id = parent::__get_session_user_id();
		
		$this->User->bindModel(array('hasAndBelongsToMany'=>array('Skill','WorkingStatus'),'hasOne'=>array('UserDetail')),false);		
		$this->UserDetail->bindModel(array('belongsTo'=>array('Region','Country','State')),false);		
		$this->User->Behaviors->attach('Containable');
		
		$user_data = $this->User->find('first',array(
			'conditions'=>array('User.id'=>$user_id),
			'contain'=>array(
					'Skill',
					'WorkingStatus'=>array('conditions'=>array('WorkingStatus.status'=>Configure::read('App.Status.active')),),
					'UserDetail'=>array('Region','Country','State')					
					)
			)
		);
		 
	
		
		$skill_model = new Skill(); 
		$results_apply =  array() ;
	 
		// 
		if ($user_id != null ) {
			 
			$selc =  array() ;
			$ids = $skill_model->query("SELECT skill_id FROM skills_users WHERE user_id='{$user_id}' ");
		  	foreach($ids as $skid) 
				$selc[]  =  $skid["skills_users"]["skill_id"];
			 
			  
			
			// select skills:  
			$skills_apply=$this->Skill->get_skills('list','Skill.name',array('Skill.id'=>$selc));
		
		
			 
			foreach($skills_apply as $id => $name){
				$result = new stdClass() ;
				$result->id =  $id ;
				$result->skill  = $name ;
				$single_skill = $this->Skill->find("first",  array("conditions"=>array("id"=>$id)));
				$category =  $this->Category->find("first", array("conditions"=>array("Category.id"=>$single_skill["Skill"]["category_id"]))) ;
				$result->catid  =  $category["Category"]["id"];
				$result->catname =  $category["Category"]["name"];
				$results_apply[]  = $result ;
			}
			 
		}
		
		$results_apply=  array_reverse($results_apply) ;
			
			
		//
		if (count($results_apply)){
		$this->set("applyskills", $results_apply);
		
		
		}
		
		
		
		
		if(!empty($this->request->data))
		{
			
			//$this->request->data['UsersWorkingStatus'] = array(0=>array('working_status_id'=>16));
			
			$this->User->set($this->request->data);
			$this->User->setValidation('user_profile_overview');
			$this->User->UserDetail->setValidation('user_detail_overview');
			
			if($this->User->saveAll($this->request->data,array('validate'=>'only')))
			{	//pr($this->request->data);die;		
				$this->request->data['User']['id'] = $user_id;
				$this->request->data['UserDetail']['id'] = parent::__get_user_detail_id();
				//pr($this->request->data);die;  
			 
				$this->UserDetail->query("UPDATE user_details SET name_visibility='{$this->request->data['UserDetail']['name_visiblity']}' WHERE user_id='{$user_id}' ");
			 
				
				
				if($this->User->saveAll($this->request->data))
				{
					$this->redirect(array('controller'=>'users','action'=>'user_personal_detail'));
					
				}
			}
			else
			{
				
		 
				if(isset($this->request->data['UserDetail']['account_type']) && !empty($this->request->data['UserDetail']['account_type']))
				{
					$account_type=$this->request->data['UserDetail']['account_type'];
				
				}

				$validation_flag = false;
				if(isset($this->request->data['Skill']['Skill'])){
				$counter = count($this->request->data['Skill']['Skill']);
				}
				
			}			
		}
		else
		{
			$this->request->data = $user_data;
			
			$account_type = $user_data['UserDetail']['account_type'];
			
			if(isset($this->request->data['Skill']) && !empty($this->request->data['Skill']))
			{
				$counter = count($this->request->data['Skill']);
			}
		}
		
		$user_image=$this->UserDetail->find('first',array('fields'=>array('image','id'),'conditions'=>array('UserDetail.user_id'=>$user_id )));
		
		$user_tot_project=$this->Project->find('count',array('conditions'=>array('Project.user_id'=>$user_id )));
		
		$leader_categories=$this->Category->get_project_job_parent_category_lists(Configure::read('App.Category.Project'));
		
		$expert_categories=$this->Category->get_project_job_parent_category_lists(Configure::read('App.Category.Job'));		
		
		$working_status = $this->WorkingStatus->getWorkingStatus();
		
		$availabilities = $this->Availability->get_project_manager_availability();
		
		$job_skills = $this->Category->get_job_skills();
		
		$this->set(compact('working_status','availabilities','job_skills','user_data','validation_flag','counter','leader_categories','expert_categories','account_type','user_image'));
		
		
		
	}
	
	
	 /*
	  * ____________________________
	  * Upload Rewsume    MEthod   
	  * upload   Resume    
	  * 2014   
	  * ____________________________   
	  * 
	  * 
	  * 
	  */
	public function uploadResume(){
		$this->autoRender = false ; 
		$this->loadModel('UserDetail'); 
		$this->loadModel('UserPortfolio');
		$this->loadModel('UserDetail'); 
		$file_array = '';
 
		 $this->request->data['User']['id']  = $this->Auth->User('id'); 
		 
		 if(!empty($this->request->data["UserDetail"]['resume_doc']['tmp_name']))
		{
			$file_array	=	$this->request->data["UserDetail"]['resume_doc'];
			$this->request->data["UserDetail"]['resume_doc']= $this->request->data["UserDetail"]['resume_doc']['name'];
		}
		  if(!empty($file_array))
		{
			$oldFile=$this->UserDetail->find('first',array('conditions'=>array('UserDetail.user_id'=>$this->request->data['User']['id'])));
			if(!empty($oldFile['UserDetail']['resume_doc']))
			{
				if(file_exists(str_replace('{user_id}',$this->request->data['User']['id'],USER_RESUME_ORIGINAL).$oldFile['UserDetail']['resume_doc']))
				{
					unlink(str_replace('{user_id}',$this->request->data['User']['id'],USER_RESUME_ORIGINAL).$oldFile['UserDetail']['resume_doc']);
				}
			}
		 
			$s =  explode(".",  str_replace(" ", "_",  $this->request->data["UserDetail"]['resume_doc'])) ;
		 	$file_name	= array_shift(	 $s);  
			$filename	=	parent::__upload($file_array,str_replace('{user_id}',$this->request->data['User']['id'],USER_RESUME_ORIGINAL),$file_name);
			$this->request->data['UserDetail']['resume_doc']= $filename; 
			  
			$this->User->query("UPDATE user_details SET resume_doc = '{$filename}' WHERE user_id = '{$this->request->data['User']['id']}'  ") ; 
			
			$lastInsert_id  = $this->User->id; 
			echo "success|".$file_name.".".$s[0]."|".$lastInsert_id."|".time();
	  
		 }
	 }
	 
	 
	 
	 // Download Resume   Stack :   
	public function downloadResume(){
		$this->autoRender = false ;
		 $this->loadModel('UserDetail'); 
		 $this->layout = false;
		$this->loadModel('FileTemp');
		$this->autoRender = false;
		 $path =  str_replace('{user_id}',$this->Auth->User('id'),USER_RESUME_ORIGINAL) ;
		 $data_file=$this->UserDetail->find('first',array('conditions'=>array('user_id'=>$this->Auth->User('id')  ) ,'order'=>array('id'=>'DESC'))); 
		 $fullPath =  $path."/".$data_file["UserDetail"]['resume_doc'];
		 $filenae =   $data_file["UserDetail"]['resume_doc'] ;
	 
		
		if ($fd = fopen ($fullPath, "r"))
		{
			$fsize = filesize($fullPath);
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.$filenae);
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($fullPath));
			flush();
			readfile($fullPath);
			exit;
		}
		 
		
	}
	
	public function download_portfolio($id) {
		$this->layout = false;
		
		//$fullPath =  'img/'. JOB_APPLY_TEMP_THUMB_DIR_FILE_VIEW . $data_file ['JobFileTemp'] ['job_file'];
		$fullPath =  'http://localhost/cake1/img/users/136/images/portfolio/original/1400334823.docx';
		if ($fd = fopen ( $fullPath, "r" )) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.basename($fullPath));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($fullPath ));
			ob_clean();
			flush();
			readfile($fullPath);
			exit;
		}
	}
	
	// Downlaod REsume fo othe   user 
	public function downloadresumeother($id)
	{	$this->autoRender = false ;
		 $this->loadModel('UserDetail'); 
		 $this->layout = false;
		$this->loadModel('FileTemp');
		$this->autoRender = false;
		 $path =  str_replace('{user_id}',$id,USER_RESUME_ORIGINAL) ;
		 $data_file=$this->UserDetail->find('first',array('conditions'=>array('user_id'=>$this->Auth->User('id')  ) ,'order'=>array('id'=>'DESC'))); 
		 $fullPath =  $path."/".$data_file["UserDetail"]['resume_doc'];
		 $filenae =   $data_file["UserDetail"]['resume_doc'] ;
	 
		
		if ($fd = fopen ($fullPath, "r"))
		{
			$fsize = filesize($fullPath);
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.$filenae);
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($fullPath));
			flush();
			readfile($fullPath);
			exit;
		}
		 
		
	}
	
	
	public function  deleteResume(){
		$this->autoRender = false ;
		$this->loadModel('UserDetail');
		$id  =  $this->Auth->User('id');   
		
		$model = new UserDetail() ;  
		$model->query("UPDATE user_details SET resume_doc =''  WHERE user_id='{$id}' ") ; 
		
		
		
		
	}
	
	
	
	public function deleteport($id){
		$this->autoRender = false;  
		$this->loadModel('UserPortfolio'); 
		$port = new UserPortfolio() ; 
		$port->deleteAll(array("id"=>$id, "user_id"=>$this->Auth->user("id"))); 
		 
	}
	
	
	// Update Personal User Data   Stack :   
	// Sch as About my self data Stack  .    
	// End Stuff  :  
	public function user_personal_detail()
	{
		
		$this->User->Behaviors->attach('Containable');
		
		$this->layout = 'lay_my_profile';
		$this->set('title_for_layout','Expert Personal Detail');
		$this->loadModel('UserPortfolio');
		$this->loadModel('UserDetail');
		$this->User->bindModel(
			array(
				'hasOne'=>array(
					'UserDetail'
				),
				'hasMany'=>array(
					'UserPortfolio'=>array(
						'className'=>'UserPortfolio',
						'foreignKey'=>'user_id',
					)
				)			
			),
			false
		);
		$this->UserPortfolio->bindModel(
			array(
				'belongsTo'=>array(
					'Category'=>array(
						'className'=>'Category',
						'foreignKey'=>'category_id',
						'group'=>'type_for'
					)
				)			
			),
			false
		);
		$this->User->contain(array('UserDetail','UserPortfolio'=>array('Category')));
		$user_data = $this->User->read(null,$this->Auth->User('id'));
		if(!empty($this->request->data))
		{		

			if(isset($this->request->data["UserDetail"]['resume_doc']['tmp_name']))
			{
				
				if(!empty($this->request->data["UserDetail"]['resume_doc']['tmp_name']))
				{				
					$file_array	=	$this->request->data["UserDetail"]['resume_doc'];
					$this->request->data["UserDetail"]['resume_doc']= $this->request->data["UserDetail"]['resume_doc']['name'];
				}
				else
				{	
					unset($this->request->data["UserDetail"]['resume_doc']);
					$file_array = '';
					
				}
				
			}
			//pr($this->request->data);die;
			$this->User->UserDetail->set($this->request->data);
			$this->User->UserDetail->setValidation('user_personal_detail');
			
			if($this->User->UserDetail->validates())
			{	
				$user_id = $this->Auth->User('id');
				$this->request->data['User']['id'] = $user_id;
				
				if(!empty($file_array))
				{	
					$oldFile=$this->UserDetail->find('first',array('conditions'=>array('UserDetail.user_id'=>$this->request->data['User']['id'])));					
					if(!empty($oldFile['UserDetail']['resume_doc']))
					{		
						if(file_exists(str_replace('{user_id}',$this->request->data['User']['id'],USER_RESUME_ORIGINAL).$oldFile['UserDetail']['resume_doc']))
						{  
							unlink(str_replace('{user_id}',$this->request->data['User']['id'],USER_RESUME_ORIGINAL).$oldFile['UserDetail']['resume_doc']);
						}
					}
				
				
					
					
					
					$file_name	=	time();
					/* this is being used to upload user original size profile image*/
					$filename	=	parent::__upload($file_array,str_replace('{user_id}',$this->request->data['User']['id'],USER_RESUME_ORIGINAL),$file_name);
					$this->request->data['UserDetail']['resume_doc']= $filename;
									
				}	
			 
				if($this->User->saveAll($this->request->data))
				{   
					 
				
						 
					if ($this->Session->read("back")){
						 $b =  $this->Session->read("back"); 
						 $this->Session->delete("back"); 
						 $this->redirect($b) ;  
					 	}else{
							$this->Session->setFlash(__('Information saved successfully'),'default',array("class"=>"success"));
					$this->redirect(array('controller'=>'users','action'=>'user_personal_detail')); 
					}
				  }
			}
			else
			{
				
				$categories=$this->Category->get_combination_project_job_parent_category_lists();
				$this->set(compact('categories'));
					
			}
		}else{
		
			$this->request->data = $user_data;
			//pr($this->request->data);die;
		}
			
		
	   $portfolio=$this->UserPortfolio->find('all',array('conditions'=>array('UserPortfolio.user_id'=>$this->Auth->User('id'))));  
		$user_data["UserPortfolio"] = $portfolio ; 
		 
		
		 $categories=$this->Category->get_combination_project_job_parent_category_lists2();
		//$categories=$this->Category->get_project_job_parent_category_lists(Configure::read('App.Category.Project'));
		$this->set(compact('categories','user_data'));
		
		 
		
		
	}
	
	public function user_portfolio_upload(){ 
		$this->loadModel('UserPortfolio');
		
		if(isset($this->request->data["UserPortfolio"]['image']['tmp_name']))
		{
			
			if(!empty($this->request->data["UserPortfolio"]['image']['tmp_name']))
			{				
				$file_array	=	$this->request->data["UserPortfolio"]['image'];
				$this->request->data["UserPortfolio"]['image']= $this->request->data["UserPortfolio"]['image']['name'];
			}
			
			if(!empty($file_array))
			{	
				
				$file_name	=	time();
				/*this is being used to upload user big size profile image*/
				parent::__uploadFile($file_array,str_replace('{user_id}',$this->Auth->User('id'),USER_PORTFOLIO_IMAGE_THUMB),$file_name,USER_PORTFOLIO_WIDTH_THUMB,USER_PORTFOLIO_HEIGHT_THUMB);
				
				$filename	=	parent::__upload($file_array,str_replace('{user_id}',$this->Auth->User('id'),USER_PORTFOLIO_IMAGE_ORIGINAL),$file_name,USER_PORTFOLIO_WIDTH_THUMB,USER_PORTFOLIO_HEIGHT_THUMB);				
			}else{
				$file_name	=	time();
				$file_array	=	$this->request->data["UserPortfolio"]['image'];
				/*this is being used to upload user big size profile image*/
				parent::__uploadFile($file_array,str_replace('{user_id}',$this->Auth->User('id'),USER_PORTFOLIO_IMAGE_THUMB),$file_name,USER_PORTFOLIO_WIDTH_THUMB,USER_PORTFOLIO_HEIGHT_THUMB);
				
				$filename	=	parent::__upload($file_array,str_replace('{user_id}',$this->Auth->User('id'),USER_PORTFOLIO_IMAGE_ORIGINAL),$file_name,USER_PORTFOLIO_WIDTH_THUMB,USER_PORTFOLIO_HEIGHT_THUMB);	
				
			}
			
			
		}
		$this->request->data['UserPortfolio']['user_id']= $this->Auth->User('id');

		$this->request->data['UserPortfolio']['image']=$filename;
		if(!empty($this->request->data)){
			$avataruploaded = 	$this->UserPortfolio->saveAll($this->request->data);
			$user_id		=	$this->Auth->User('id');
			$lastInsert_id	=	$this->UserPortfolio->id;
			if($avataruploaded){
				$newfile=explode(".",$filename);
				$image='doc.png';
				if($newfile[1]=='jpg' || $newfile[1]=='jpeg' || $newfile[1]=='JPG' || $newfile[1]=='JPEG' || $newfile[1]=='png' || $newfile[1]=='PNG' || $newfile[1]=='gif' || $newfile[1]=='GIF'){
					echo "success|".$filename."|".$lastInsert_id."|".$user_id;
				}else{
					$filename=USER_SHOW.$image;
					echo "success|".$filename."|".$lastInsert_id."|".$user_id;
				}
				
			}else{
				echo "failed";
			} 
		}	
		die;
	}
	
	public function add_portfolio()
	{
		if($this->RequestHandler->isAjax()){
			$this->loadModel('UserPortfolio');			
			if(!empty($this->request->data)){
				
				$this->UserPortfolio->set($this->request->data);
				$this->UserPortfolio->setValidation('overview_detail');
				//die;
				if($this->UserPortfolio->validates())
				{	
					$this->UserPortfolio->id = $this->request->data['UserPortfolio']['id'];
					$this->UserPortfolio->saveAll($this->request->data);
				 
					echo '<script> window.location = SiteUrl+"/users/user_personal_detail"; </script>';
					
				 
				}else{
				 				
					$this->render('/Elements/Front/ele_add_item_portfolio_popup');
				}	
				
			}
		}
	}
	
	public function add_about_us()
	{
		if($this->RequestHandler->isAjax()){
			$this->loadModel('UserDetail');			
			if(!empty($this->request->data)){
				
				$this->UserDetail->set($this->request->data);
				$this->UserDetail->setValidation('add_about_us');
			//die;
				if($this->UserDetail->validates())
				{	
					$this->UserDetail->id = parent::getuserDetailid();					
					$this->UserDetail->save($this->request->data);
					echo '<script> window.location = SiteUrl+"/users/user_personal_detail"; </script>';
					//$this->redirect(array('controller'=>'users','action'=>'user_personal_detail'));
				}else{
					//pr($this->UserPortfolio->validationErrors);					
					$this->render('/Elements/Front/ele_about_us');
				}	
				
			}
		}
	}
	public function add_resume_text()
	{
		if($this->RequestHandler->isAjax()){
			$this->loadModel('UserDetail');			
			if(!empty($this->request->data)){
				
				$this->UserDetail->set($this->request->data);
				$this->UserDetail->setValidation('add_about_us');
		 
				if($this->UserDetail->validates())
				{	
					$this->UserDetail->id = parent::getuserDetailid();					
					$this->UserDetail->save($this->request->data);
					echo '<script> window.location = SiteUrl+"/users/user_personal_detail"; </script>';
				 
				}else{
					 					
					$this->render('/Elements/Front/ele_cv');
				}	
				
			}
		}
	
	
	} 
 
	 
	
	
		/*
		 * _____________________________________
		 * Paypal  Function 
		 * 2014@pashkovdenis@gmail.com   
		 * _____________________________________
		 */
	
	
		public function paypalResponse(){
			$this->autoRender =  false ; 
		 }
		 
 
		
		public function paypalAuth(){
			$this->layout = 'lay_my_profile';
			$this->set("user_id" , $this->Auth->user("id")); 
			$this->set("type",  2); 
		 }
	
		 
		
	 	private  function ispayedAuth(){ 
	 		 $this->loadModel("User") ; 
			 $user_model  =  new User() ; 
		 	 $p = $user_model->query("SELECT COUNT(*) as c FROM auth_payed WHERE user_id='{$this->Auth->user("id")}' "); 
		      //  if ($p[0][0]["c"] ==0 ) 
		   	 //	 $this->redirect("/users/paypalAuth") ;
		 }
	
	 
		 
		 
		 
		 
	
	/* 
	 *  Authonticate  My Self.   
	 *  2014 
	 *  pashkovdenis@gmail.com edition 
	 *  
	 */
	public function userinfo_authenticate()
	{ 
		$this->ispayedAuth() ;
		$this->loadModel("User");  
		$user_model  =  new User() ;  
		 
		$this->layout = 'lay_my_profile';
		$this->set('title_for_layout','User Info Authenticate');
		$user_id = parent::__get_session_user_id();
		$this->User->bindModel(array('hasOne'=>array('UserDetail')),false);		
		if(!empty($this->request->data))
		{

			
			
		  /*
		   * Update Passsword    
		   * 2014    
		   * pashkovdenis@gmail.com   
		   * 
		   */
			
				 
			if  (isset($_POST["old_password"]) &&  $_POST["old_password"] != "" ){ 
			 	if   (strlen(  $_POST["new_password"]  )<6){
			 		$this->Session->setFlash(__('Password length must be more then 6 character length.'),'default',array("class"=>"error")); 
			 		return ;  
			 	}
			   
			 	if ( $_POST["new_password"]  !=   $_POST["new2_password"] ){
			 		$this->Session->setFlash(__('Password Doesnt match.'),'default',array("class"=>"error"));
			 		return ; 
			 	 } 
			 
			 	 $c  =  $user_model->query("SELECT COUNT(*) as c  FROM users WHERE id='{$this->Auth->user("id")}' AND password='". Security::hash($_POST["old_password"],null,true) ."'   ");    
   
				//  update Users Infoi    
				if ($c[0][0]["c"]>0){
			    	$user_model->query("UPDATE users SET password='".( Security::hash($_POST["new_password"],null,true))."'  WHERE id='{$this->Auth->user("id")}' "); 
					$this->Session->setFlash(__('Password Updated'),'default',array("class"=>"success")); 
			 	 	return  ;  
			 	 }else{
			 	 	 $this->Session->setFlash(__('Wrong old Password'),'default',array("class"=>"error"));
			 	 	 return ; 
			 	   }
			 	 
				
			}
			
			/*
			 * Change Account type  :  
			 *  
			 */
		 
			if (isset($_POST["register"])  &&   $_POST["register"]!= ""  ){
              
			     $user_model->query("UPDATE users SET role_id ='{$_POST["register"]}' WHERE id='{$this->Auth->user("id")}'  ") ;  
			     $this->Session->setFlash(__(' Account type changed. Please Login.  '),'default',array("class"=>"success"));     
			     $this->Auth->logout() ;  
			     $this->redirect("/users/login")  ; 
			     
			     return  ;   
				
			}
			
			
			
			
			 $birth=  explode("-", $this->request->data['UserDetail']["birth_date"]); 
			 if (count($birth)){
			 	$this->request->data['UserDetail']["birth_year"] =  $birth[0];
			 	$this->request->data['UserDetail']["birth_month"] =  $birth[1];
			 	$this->request->data['UserDetail']["birth_day"] =  $birth[2];
			  }
			 
			$this->User->set($this->request->data);
			$this->User->setValidation('user_info_authenticate');

			
			if($this->User->validates())
			{
				$this->request->data['UserDetail']['id'] = parent::__get_user_detail_id();
				if($this->User->saveAll($this->request->data))
				{
					$this->Session->setFlash(__('Your information updated successfully.'),'default',array("class"=>"success"));
					$this->redirect(array('controller'=>'users','action'=>'social_media_authenticate'));
				}
			} 
			
			
			
		}
		else
		{
			$fields = array('User.first_name','User.last_name','User.id','UserDetail.id','UserDetail.user_id','UserDetail.birth_day','UserDetail.birth_month','UserDetail.birth_year','UserDetail.gender', 'UserDetail.birth_date');
			$this->request->data = $this->User->read($fields,$user_id);
			  
			
		}
	}
	
	
	
	
	
	/* 
	 * 
	 * 
	 * Social LinkId  Authonticate Stuff   
	 * 2013@gmail.com  
	 * pashkovdenis@gmail.com   
	 * 
	 * 
	 */
	 
	public function social_media_authenticate()
	{ 
		$this->ispayedAuth() ; 
		
	 	$this->loadModel("User") ; 
		$model =  new User();  
		$this->layout = 'lay_my_profile';
		$this->set('title_for_layout','User Social Media Authenticate');
		$this->set("user",  $this->Auth->user("id"))  ; 
	    $user =  $model->find("first",  array("conditions"=>array("id"=>$this->Auth->user("id")))); 
		$status =   0 ; 
		
	    
	   	 	if ($user["User"]["linkid"]!="" && $user["User"]["linkid_confirmed"] ==1  ) 
	  			$status =  2 ;   
	   	 	if ($user["User"]["linkid"]!="" && $user["User"]["linkid_confirmed"] ==0  )
	   	 		$status =  1 ;
	    	
	   	 	
	   	 	if ($status>0)  
	   	 		$this->set("linked" , 1 );
	    	// status  
	    	 $this->set("status",  $status);
	}
	
	
	
	
	/*
	 * Final Step Authoticate My self :    
	 * pashkovdenis@2013  
	 * 2013 
	 * 
	 */  
	
	public function phone_authenticate()
	{ 
		$this->ispayedAuth() ;
		
		$this->loadModel("User") ;   
	 	$this->layout = 'lay_my_profile';
		$this->set('title_for_layout','Phone Authenticate');
	 	$this->layout = 'lay_my_profile';
		$this->set('title_for_layout','Passport Authenticate');
		$this->User->bindModel(array('hasOne'=>array('UserDetail')),false);
		$user_id = parent::__get_session_user_id();
 		$user =  $this->User->find("first",  array("conditions"=>array("User.id"=>$user_id)));  
		
 		 if ($user["User"]["phone_confirmed"]==0){
		 	$this->set("confirmed" , false);
		 }else{
		 	$this->set("confirmed" , true); 
		 	$this->set("phone" , $user["User"]["phone_number"]) ;  
		 	
		 }
		   
		 if (!empty($_POST["number"])   &&  $user["User"]["phone_confirmed"] ==0  ){
		 	
		 		$phone =   $_POST["number"];  
		 		$code =   $_POST["code"];  
		 		$this->Session->write("phone", $phone); 
		 	 
		 		
		 		if ($code!=""){
		 			if ($code== $this->Session->read("phone_code")){
		 			    	$this->User->query("UPDATE users SET phone_confirmed=1 , phone_number ='{$phone}'  WHERE id='{$user_id}' ");
		 			 		$this->Session->setFlash(__(" Confirmed."),'default',array("class"=>"success"));  
		 			 		$this->redirect(array('controller'=>'users','action'=>'phone_authenticate'));  
					 		return ;  
						 } 
		 		}
		 		 
		 		//972525833900
		 		
		 	if ($phone!=""){	
		 		
					  	 if (	preg_match( "#^[-+0-9()\s]+$#", $phone )){
			 				 	$rand =  rand(9344,99999);  
			 				    $this->Session->write("phone_code",  $rand);  
			 				    $client = new Services_Twilio(TWILIO_ID, TWILIO_TOKEN);  
			 				    $people = array($phone, "Team4Dream"); 
			 				    $sms = $client->account->messages->sendMessage(    TWILIO_NUMBER ,  	$phone,   "Phone verification  CODE: ". $rand   );
			 				    $this->Session->setFlash(__(" Code has been sent to " . $phone),'default',array("class"=>"success"));
					 	   
					 	 }else{
					   			$this->Session->setFlash(__(" Phone in wrong format."),'default',array("class"=>"error"));
					   }
					    
		   
		 	}
		   
		   
		 	 		
		  } 
	 	
  		if ($this->Session->read("back")!=""){
			$v =   $this->Session->read("back") ;   
			$this->Session->delete("back");
			$this->redirect($v) ; 
		 	} 
	 
	}
	
	
	public function address_authenticate()
	{
		$this->layout = 'lay_my_profile';
		$this->set('title_for_layout','Address Authenticate');
		$this->User->bindModel(array('hasOne'=>array('UserDetail')),false);
		$user_id = parent::__get_session_user_id();
		$user_data =$this->User->find('first',array('conditions'=>array('User.id'=>$user_id),'fields'=>array('User.id','UserDetail.user_id','UserDetail.address_verification_code')));
		
		if(!empty($this->request->data))
		{
			
			$this->User->UserDetail->set($this->request->data);
			$this->User->UserDetail->setValidation('address_authenticate');
			if($this->User->saveAll($this->request->data,array('validate'=>'only')))
			{
				
				if(empty($user_data['UserDetail']['address_verification_code']))
				{
					$this->request->data['UserDetail']['address_verification_code'] = parent::__random_number();
				}
				if(!empty($this->request->data['UserDetail']['check_address_verification_code']))
				{
					if($this->request->data['UserDetail']['check_address_verification_code'] == $user_data['UserDetail']['address_verification_code'])
					{
						$this->request->data['UserDetail']['address_validation'] = 1;
					}
				}
				$this->request->data['UserDetail']['id'] = parent::__get_user_detail_id();
				if($this->User->saveAll($this->request->data))
				{
					$this->Session->setFlash(__("Your address is sucessfully updated."),'default',array("class"=>"success"));
					$this->redirect(array('controller'=>'users','action'=>'passport_authenticate'));
				}
			}
		}
		else
		{
			$this->request->data = $user_data;
		}
		$region = $this->Region->getResionListForUserRegistraion();
		
		if(isset($this->request->data['User']['region_id']) && $this->request->data['User']['region_id'])
		{
			
			$countries = $this->Country->getCountryListByRegionId($this->request->data['User']['region_id']);
		}
		if(isset($this->request->data['User']['country_id']) && $this->request->data['User']['country_id'])
		{
			$states = $this->State->getStateList($this->request->data['User']['country_id']);
		}
		
		$this->set(compact('region','countries','states'));
	}
	
	
	public function passport_authenticate()
	{
		$this->layout = 'lay_my_profile';
		$this->set('title_for_layout','Passport Authenticate');
		$this->User->bindModel(array('hasOne'=>array('UserDetail')),false);
		$user_id = parent::__get_session_user_id();
		if(!empty($this->request->data))
		{
			
			$this->User->UserDetail->set($this->request->data);
			$this->User->UserDetail->setValidation('passport_authenticate');
			if($this->User->saveAll($this->request->data,array('validate'=>'only')))
			{
				$this->request->data['UserDetail']['id'] = parent::__get_user_detail_id();
				if($this->User->saveAll($this->request->data))
				{
					$this->Session->setFlash(__("Your passport information is sucessfully updated."),'default',array("class"=>"success"));
					parent::user_redirect();
				}
			}
		}
		else
		{
			$this->request->data = $this->User->read(array('User.id','UserDetail.user_id','UserDetail.passport_key_one','UserDetail.passport_key_two'),$user_id);
		}
	}
	
	 
	
	
	/*
	 * Search Experts Acton   
	 * pashkovdenis@gmail.com   
	 * 2014   
	 *  
	 */
	
	public function search_expert()
	
	{

		$this->layout = 'lay_search_expert';
		$this->set('title_for_layout','Search Expert');
		$this->loadModel('Category');
		$this->loadModel('Availability');
		$this->loadModel('WorkingStatus');
		$this->loadModel("User"); 
		$this->loadModel('Category');
		$this->loadModel('SkillsUser');
		$this->loadModel('UsersWorkingStatus');
		$orderby = array('User.id'=>'DESC');
		$conditions = array();
		$account_type = array();
		$newUserId1 = array();
		$newUserId2 = array();
		$filtersA =array();
		$paging = '';
		// Detect Current User Type :   
		//$current_user  = (new User())->find("first",  array("conditions"=>array("id"=>$this->Auth->user("id")))) ;  
		$current_user  = new User();
		$current_user1=$current_user->find("first",  array("conditions"=>array("id"=>$this->Auth->user("id"))));
		 if ($current_user1["User"]["role_id"]!=4){
		 	$this->set("expert"   , 1);
		 }
		   
		
		

		 $this->loadModel("Category") ;
		 $cat_model  = new Category() ;
		 
		 
		 
		 //  Status Goes Here :
		 $cats =  $cat_model->find("all" , array("conditions"=>array("Category.type_for"=>2 , "Category.status"=>1,  "Category.parent_id"=>0)));
		 
		 $res_cat  =  array() ;
		 
		 
		 //  Select Categories
		 foreach($cats as $category){
		 	$res_cat[$category["Category"]["id"]]  = $category["Category"]["name"] ;
		 }
		 
		 $this->set("job_categories"  ,  $res_cat );
		
		
		if(isset($this->request->data['Paging']['page_no']) &&  !empty($this->request->data['Paging']['page_no']))
		{
			$paging = $this->request->data['Paging']['page_no'];
		}
		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('FrontSearch');
			$this->Session->delete('Url');
		}
		$this->User->bindModel(array(
			'hasOne'=>array(
				'UserDetail'
			),
			'hasAndBelongsToMany'=>array(
				'Skill'
			)	
		)

		,false);
		
		$this->User->UserDetail->bindModel(array(
			'belongsTo'=>array(
				'Country',
				'ExpertiseCategory'=>array(
					'className'=>'Category',
					'foreignKey'=>'expertise_category_id'
				),
				'LeaderCategory'=>array(
						'className'=>'Category',
						'foreignKey'=>'leadership_category_id'
					) 
			)	
		)
		,false);
		
		$this->User->Behaviors->attach('Containable');
		if(!empty($this->request->data)){	
			
			
			$this->Session->delete('FrontSearch');
			$this->Session->delete('Url');
			
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data['User']['keyword'])){
				$search_keyword = Sanitize::escape($this->request->data['User']['keyword']);
				$this->Session->write('FrontSearch.search_keyword', $search_keyword);
			}
			if(!empty($this->request->data['User']['name'])){
				$name = Sanitize::escape($this->request->data['User']['name']);
				$this->Session->write('FrontSearch.name', $name);
			}
			if(!empty($this->request->data['Category']['Category'])){
				$expertise_category_id = $this->request->data['Category']['Category'];
				$this->Session->write('FrontSearch.expertise_category_id', $expertise_category_id);
			}
			if(!empty($this->request->data['Skill']['Skill'])){
				$userid=$this->SkillsUser->find('all',array('fields'=>array('SkillsUser.user_id'),'conditions'=>array('SkillsUser.skill_id'=>$this->request->data['Skill']['Skill'])));
				
				foreach($userid as $key=>$user_id)
				{
					$newUserId1[]=$user_id['SkillsUser']['user_id'];
				
				}
				
				$newUserId1 = array_values(array_unique($newUserId1));
				
				//$this->Session->write('FrontSearch.id', $newUserId);
					
			}
			if(!empty($this->request->data['WorkingStatus']['WorkingStatus'])){
				$userid=$this->UsersWorkingStatus->find('all',array('fields'=>array('UsersWorkingStatus.user_id'),'conditions'=>array('UsersWorkingStatus.working_status_id'=>$this->request->data['WorkingStatus']['WorkingStatus'])));
				
				foreach($userid as $key=>$user_id)
				{
					$newUserId2[]=$user_id['UsersWorkingStatus']['user_id'];
				
				}
			
				$newUserId2 = array_values(array_unique($newUserId2));
				
				//$this->Session->write('FrontSearch.id', $newUserId);
					
			}
			$filtersA = array_merge($newUserId2,array_diff($newUserId1,$newUserId2));
			if(!empty($filtersA))
			{
				$this->Session->write('FrontSearch.id', $filtersA);
			}
		
			if(!empty($this->request->data['UserDetail']['region_id'])){
				$region_id = Sanitize::escape($this->request->data['UserDetail']['region_id']);
				$this->Session->write('FrontSearch.region_id', $region_id);
			}
			if(!empty($this->request->data['UserDetail']['country_id'])){
				$country_id = Sanitize::escape($this->request->data['UserDetail']['country_id']);
				$this->Session->write('FrontSearch.country_id', $country_id);
			}
			if(!empty($this->request->data['UserDetail']['state_id'])){
				$state_id = Sanitize::escape($this->request->data['UserDetail']['state_id']);
				$this->Session->write('FrontSearch.state_id', $state_id);
			}
			if(!empty($this->request->data['UserDetail']['account_type_individual']) && !empty($this->request->data['UserDetail']['account_type_company'])){
				$account_type = array($this->request->data['UserDetail']['account_type_individual'],$this->request->data['UserDetail']['account_type_company']);
				
				$this->Session->write('FrontSearch.account_type', $account_type);
			}
			if(!empty($this->request->data['UserDetail']['account_type_individual']) && empty($this->request->data['UserDetail']['account_type_company'])){
				$account_type = array($this->request->data['UserDetail']['account_type_individual']);
				$this->Session->write('FrontSearch.account_type', $account_type);
			}
			if(!empty($this->request->data['UserDetail']['account_type_company']) && empty($this->request->data['UserDetail']['account_type_individual'])){
				$account_type = array($this->request->data['UserDetail']['account_type_company']);
				$this->Session->write('FrontSearch.account_type', $account_type);
				
				
			}
			if(!empty($this->request->data['User']['sortby']) && $this->request->data['User']['sortby'] =='first_name'){
				
				$orderby = array('User.first_name'=>'ASC');
			}
			if(!empty($this->request->data['User']['sortby']) && $this->request->data['User']['sortby'] =='last_name'){
				$orderby = array('User.last_name'=>'ASC');
			}
			if(!empty($this->request->data['User']['sortby']) && $this->request->data['User']['sortby'] =='created'){
				$orderby = array('User.created'=>'DESC');
			}
			
			
		}
			
		if($this->Session->check('FrontSearch')){
			
			$keywords  = $this->Session->read('FrontSearch');
			//pr($keywords);
		
		
			foreach($keywords as $key=>$values){
			
				if($key == 'search_keyword'){
					$conditions[] = array('OR'=>array('User.first_name LIKE'=>"%".$values."%",'User.last_name LIKE'=>"%".$values."%",'CONCAT("first_name","last_name")LIKE'=>"%".$values."%"));
				}
				else if($key == 'name'){
					$conditions[] = array('OR'=>array('User.first_name LIKE'=>"%".$values."%",'User.last_name LIKE'=>"%".$values."%"));
				}
				else if($key == 'account_type'){
					$conditions[] = array('OR'=>array('UserDetail.'.$key =>$values));
				}
				else if($key == 'region_id'){
					$conditions[] = array('UserDetail.'.$key =>$values);
				}else if($key == 'country_id'){
					$conditions[] = array('UserDetail.'.$key =>$values);
				}else if($key == 'state_id'){
					$conditions[] = array('UserDetail.'.$key =>$values);
				}
				else if($key == 'expertise_category_id'){
					$conditions[] = array('UserDetail.'.$key =>$values);
				}
				
				else
				{
					$conditions[] = array('User.'.$key =>$values);
				}
				
			}
			
			
		}
		
		
		$conditions[] =array('OR'=>array('User.role_id'=>array(Configure::read('App.Role.Provider'),Configure::read('App.Role.Both'))));
		
		//die;
		
		$this->paginate = 	array(
								'conditions'=>$conditions,
								'limit'=>Configure::read('App.PageLimit'),
								'order'=>$orderby,
								'page'=>$paging,
								'contain'=>array(
									
									'UserDetail'=>array(
										'fields'=>array('UserDetail.about_us','UserDetail.user_id','UserDetail.max_reference_rate','UserDetail.min_reference_rate','UserDetail.gender'),
										'Country'=>array(
											'conditions'=>array('Country.status'=>Configure::read('App.Status.active')),
											'fields'=>array('Country.name','Country.country_flag')
										),
										'ExpertiseCategory'=>array(
											'conditions'=>array('ExpertiseCategory.status'=>Configure::read('App.Status.active')),
											'fields'=>array('ExpertiseCategory.name')
										),
										'LeaderCategory'=>array(
							'conditions'=>array('LeaderCategory.status'=>Configure::read('App.Status.active')),
							'fields'=>array('LeaderCategory.name')
						)
									),
									'Skill'=>array('conditions'=>array('Skill.status'=>Configure::read('App.Status.active'))),
									
								),
								'fields'=>array('User.id','User.role_id','UserDetail.image','User.first_name','User.last_name')
							);
		 $data = $this->paginate('User');
		 
		
		$work_catgories=$this->Category->get_parent_categories_front(Configure::read('App.Category.Job'));
		//pr($work_catgories);
		//die;
		$regions = $this->Region->getResionListForUserRegistraion();
		
		$expert_availability=$this->Availability->get_availability_front();
		$expert_work_status = $this->WorkingStatus->getAllWorkingStatus();
		$skills = $this->Category->get_job_skills();
		
		if(!empty($this->request->data['Project']['region_id']))
		{
			$countries = $this->Country->getCountryListByRegionId($this->request->data['Project']['region_id']);
		}
		if(!empty($this->request->data['Project']['country_id']))
		{
			$states = $this->State->getStateList($this->request->data['Project']['country_id']);
		} 
		
		
		$this->loadModel("Availability")  ;
		 
		
		
		// Load Additona Data For   each users :   
		foreach($data as $k=>$o){  
			
			$status_id = $this->User->query("SELECT working_status_id FROM users_working_statuses WHERE user_id = '{$o["User"]["id"]}' "); 
		 	 
			
			if (isset($status_id[0]["users_working_statuses"])){
			$status =   $this->User->query("SELECT name FROM working_statuses WHERE id  = '{$status_id[0]["users_working_statuses"]["working_status_id"]}' ") ; 
		 	$data[$k]["status"] =  $status[0]["working_statuses"]["name"]  ; }else
		 	{
		 		$data[$k]["status"] = ""; 
		 	}
		 	 
			 
			$avail  = $this->User->query("SELECT availability_id FROM  user_details WHERE user_id='{$o["User"]["id"]}' ");
			//$model =  (new Availability())->find("first" , array("conditions"=>array("id"=>$avail[0]["user_details"]["availability_id"])));
			$model =  new Availability();
			$model1=$model->find("first" , array("conditions"=>array("id"=>$avail[0]["user_details"]["availability_id"])));
			if (!empty($model1["Availability"]["name"]))
				$data[$k]["avail"] =   $model1["Availability"]["name"];
			else
				$data[$k]["avail"] = "N/A" ; 
			
			
			
			
		} 
		  
		
		//  Num of Project   
		foreach($data as $k=>$o){   
			
			$data[$k]["projects_num"]  =  User::getNumberExpertProject($o["User"]["id"]) ;   
		}
		
		
		
		//pr($data);
		$this->set(compact('data','work_catgories','regions','countries','states','expert_availability','expert_work_status','skills'));	
		if($this->request->is('ajax')){
			$this->layout = false;
			$this->render('/Elements/Front/ele_search_expert_right_sidebar');
		}
	}
	
	
	
	/*
	 * Search Leader Step   
	 * pashkovdenis@gmail.com  
	 * 2014  ; 
	 * 
	 */
	
	
	public function search_leader()
	{
		$this->layout = 'lay_search_leader';
		$paging = '';
		$this->set('title_for_layout','Search Leader');
		$conditions = array();
		$orderby = array('User.id'=>'DESC');
		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('FrontSearch');
			$this->Session->delete('Url');
		}
		$this->loadModel('Availability');
		$this->loadModel('WorkingStatus');
		$this->loadModel('Project');
		$this->User->bindModel(array(
					'hasOne'=>array(
						'UserDetail'=>array(
							'className' => 'UserDetail',
							'foreignKey' => 'user_id'
						),
						'Project'=>array(
							'className' => 'Project',
							'foreignKey' => 'user_id'
						)
					),
					'hasAndBelongsToMany'=>array(
						'Skill'
					)
				)
		,false);
		
		$this->User->UserDetail->bindModel(array(
					'belongsTo'=>array(
					  
							'ExpertiseCategory'=>array(
									'className'=>'Category',
									'foreignKey'=>'expertise_category_id'
							),
							'LeaderCategory'=>array(
									'className'=>'Category',
									'foreignKey'=>'leadership_category_id'
							), 
							
							
						'Region'=>array('foreignKey'=>'region_id'),
						'Country',
						'State'
					)	
				)
		,false);
		
		
	if(isset($this->request->data['Paging']['page_no']) &&  !empty($this->request->data['Paging']['page_no']))
		{
			$paging = $this->request->data['Paging']['page_no'];
		}
		
		if(!empty($this->request->data))
		{
			//pr($this->request->data);
			$this->Session->delete('FrontSearch');
			$this->Session->delete('Url');
			App::uses('Sanitize', 'Utility');
			
			if(!empty($this->request->data['User']['keyword'])){
				$search_keyword = Sanitize::escape($this->request->data['User']['keyword']);
				$this->Session->write('FrontSearch.search_keyword', $search_keyword);
			}
			if(!empty($this->request->data['User']['name'])){
				$name = Sanitize::escape($this->request->data['User']['name']);
				$this->Session->write('FrontSearch.name', $name);
			}
			if(!empty($this->request->data['UserDetail']['region_id'])){
				$region_id = Sanitize::escape($this->request->data['UserDetail']['region_id']);
				$this->Session->write('FrontSearch.region_id', $region_id);
			}
			if(!empty($this->request->data['UserDetail']['country_id'])){
				$country_id = Sanitize::escape($this->request->data['UserDetail']['country_id']);
				$this->Session->write('FrontSearch.country_id', $country_id);
			}
			if(!empty($this->request->data['UserDetail']['state_id'])){
				$state_id = Sanitize::escape($this->request->data['UserDetail']['state_id']);
				$this->Session->write('FrontSearch.state_id', $state_id);
			}
			if(!empty($this->request->data['UserDetail']['leadership_category_id'])){
				$leadership_category_id = $this->request->data['UserDetail']['leadership_category_id'];
				$this->Session->write('FrontSearch.leadership_category_id', $leadership_category_id);
			}			
			if(!empty($this->request->data['User']['sortby']) && $this->request->data['User']['sortby'] =='first_name'){
				
				$orderby = array('User.first_name'=>'ASC');
			}
			if(!empty($this->request->data['User']['sortby']) && $this->request->data['User']['sortby'] =='last_name'){
				$orderby = array('User.last_name'=>'ASC');
			}
			if(!empty($this->request->data['User']['sortby']) && $this->request->data['User']['sortby'] =='registration_date'){
				$orderby = array('User.created'=>'ASC');
			}
			
			
			if($this->Session->check('FrontSearch')){
			
				$keywords  = $this->Session->read('FrontSearch');
				//pr($keywords);
				foreach($keywords as $key=>$values){
					if($key == 'name'){
						
						$conditions[] = array('OR'=>array('User.first_name LIKE'=>"%".$values."%",'User.last_name LIKE'=>"%".$values."%"));
					}
					else if($key == 'search_keyword'){
						
						$conditions[] = array('OR'=>array('User.first_name LIKE'=>"%".$values."%" ,'User.last_name LIKE'=>"%".$values."%" ));
					}
					else if($key == 'region_id'){
						
						$conditions[] = array('UserDetail.'.$key =>$values);
					}
					else if($key == 'state_id'){
						
						$conditions[] = array('UserDetail.'.$key =>$values);
					}else if($key == 'country_id'){
						
						$conditions[] = array('UserDetail.'.$key =>$values);
					}else if($key == 'leadership_category_id'){
						
						$conditions[] = array('UserDetail.'.$key =>$values);
					}
					else{
						$conditions[] = array('User.'.$key =>$values);
						
					}
				}
			}	
			
		}
		
		
		$conditions[] = array('OR'=>array('User.role_id'=>array(Configure::read('App.Role.Buyer'),Configure::read('App.Role.Both'))));
		$this->User->Behaviors->attach('Containable');
		
		$this->paginate = 	array(								
								'limit'=>Configure::read('App.PageLimit'),
								'order'=>$orderby,
								'page'=>$paging,	
								'recursive'=>0,
								'conditions'=>$conditions,
								'contain'=>array(
										'UserDetail'=>array(
											'fields'=>array(
												'UserDetail.user_id','UserDetail.region_id','UserDetail.country_id','UserDetail.state_id','UserDetail.leadership_category_id','UserDetail.city','UserDetail.min_reference_rate','UserDetail.max_reference_rate','UserDetail.about_us','UserDetail.gender'
	,'UserDetail.image'								
											),
												
												'Country'=>array(
														'conditions'=>array('Country.status'=>Configure::read('App.Status.active')),
														'fields'=>array('Country.name','Country.country_flag')
												),
												'ExpertiseCategory'=>array(
														'conditions'=>array('ExpertiseCategory.status'=>Configure::read('App.Status.active')),
														'fields'=>array('ExpertiseCategory.name')
												),
												'LeaderCategory'=>array(
														'conditions'=>array('LeaderCategory.status'=>Configure::read('App.Status.active')),
														'fields'=>array('LeaderCategory.name')
												)
												,
											'Region'=>array(
												'conditions'=>array('Region.status'=>Configure::read('App.Status.active')),
												'fields'=>array('Region.name')
											),
											'Country'=>array(
												'conditions'=>array('Country.status'=>Configure::read('App.Status.active')),
												'fields'=>array('Country.name','Country.country_flag')
											),
											'State'=>array(
												'conditions'=>array('State.status'=>Configure::read('App.Status.active')),
												'fields'=>array('State.name')
											),
											'LeaderCategory'=>array(
												'conditions'=>array('LeaderCategory.status'=>Configure::read('App.Status.active')),
												'fields'=>array('LeaderCategory.name')
											)
										
										),
										'Skill',
										'Project'
									),
									'fields'=>array('User.*','count(Project.id) as totalProject'),
									'group'=>array('User.id')
							);
							
							
		$leaderData = $this->paginate('User');
		//pr($leaderData );
		//pr(parent::DisplayQuery('User'));
		
		
		foreach($leaderData as $k=>$o){
			$c = $this->Project->find("count",  array("conditions"=>array("user_id"=> $o["User"]["id"] , "status"=>1, "visibility"=>1 )));
			$leaderData[$k]["projects_num"] = $c; 
		} 
		
		
		$this->loadModel("Availability")  ;  
		
		foreach($leaderData as $k=>$o){ 
			
			$avail  = $this->User->query("SELECT availability_id FROM  user_details WHERE user_id='{$o["User"]["id"]}' "); 
			//$model =  (new Availability())->find("first" , array("conditions"=>array("id"=>$avail[0]["user_details"]["availability_id"])));  
			$model1 =  new Availability();
			$model = $model1->find("first" , array("conditions"=>array("id"=>$avail[0]["user_details"]["availability_id"]))); 
			
			if (!empty($model["Availability"]["name"]))
			$leaderData[$k]["avail"] =   $model["Availability"]["name"];
			else 
				$leaderData[$k]["avail"] = "N/A" ; 
			
			$status_id = $this->User->query("SELECT working_status_id FROM users_working_statuses WHERE user_id = '{$o["User"]["id"]}' ");
			 
				
			if (isset($status_id[0]["users_working_statuses"])){
				$status =   $this->User->query("SELECT name FROM working_statuses WHERE id  = '{$status_id[0]["users_working_statuses"]["working_status_id"]}' ") ;
				$leaderData[$k]["status"] =  $status[0]["working_statuses"]["name"]  ; }else
				{
					$leaderData[$k]["status"] = "";
				}
				 
				// Select Expertise Category :   
				$cat  =  $this->Category->find("first", array("conditions"=>array("Category.id"=> $o["UserDetail"]["expertise_category_id"]  )) ); 
				$leaderData[$k]["expert_Cat"] =$cat["Category"]["name"]; 
				
				
		}
		
		 
		
		
		
		$leader_availability=$this->Availability->get_availability_front();
		
		$region = $this->Region->getResionListForUserRegistraion();
		
		if(!empty($this->request->data['UserDetail']['region_id']))
		{
			$countries = $this->Country->getCountryListByRegionId($this->request->data['UserDetail']['region_id']);
		}
		if(!empty($this->request->data['UserDetail']['country_id']))
		{
			$states = $this->State->getStateList($this->request->data['UserDetail']['country_id']);
		}
		
		$project_catgories=$this->Category->get_categories_front(Configure::read('App.Category.Project'));
		
		$project_skill = $this->Category->get_project_skills();
		
		$leader_work_status = $this->WorkingStatus->getAllWorkingStatus();
		
		$this->set(compact('leaderData','project_catgories','region','countries','states','leader_availability','project_skill','leader_work_status'));	
			
		if($this->request->is('ajax'))
		{
			$this->layout = false;
			$this->render('/Elements/Front/ele_search_leader_right_sidebar');
		}	
		
	}

	
	public function skill_delete($id,$div_id)
	{
		$this->loadModel('SkillsUser');
		if($this->RequestHandler->isAjax())
		{
			$this->autoRender = true;
			$this->layout = false;
			if (isset($id) && $id!='')
			{
				$this->SkillsUser->delete($id);
				
				$this->set(compact('row_id','id'));
			}
		}
	}
	
	
	function facebook_validation()
	{	$number_of_friends=50;
		$this->layout = false;
		$this->autoRender	= true ;
		$this->loadModel('Template');
		$this->User->bindModel(array('hasOne'=>array('UserDetail')),false);
		if(!empty($_POST))
		{
			
			$this->User->UserDetail->id = parent::__get_user_detail_id();
			
			if($number_of_friends < Configure::read('App.Facebook.Verification.Friends'))
			{
				$msg = "You are not verified with facebook authentication.";
				$this->User->UserDetail->saveField('facebook_social_media_validation',0);
				
				$this->Session->setFlash(__($msg),'default',array("class"=>"success"));
			}
			elseif($number_of_friends >= Configure::read('App.Facebook.Verification.Friends') )
			{
				$msg = "You are successfully verified with facebook authentication.";
				$this->User->UserDetail->saveField('facebook_social_media_validation',1);
				$this->Session->setFlash(__($msg),'default',array("class"=>"success"));
			}
			//parent::social_media_authentication_mail($user_id,$msg);
			
		}
	}

		
	function linkdin_validation()
	{
		$this->layout = false;
		$this->autoRender	= true ;
		$this->loadModel('Template');
		$user_data =array();
		$user_id =parent::__get_session_user_id();
		$this->User->bindModel(array('hasOne'=>array('UserDetail')),false);
		
		if(!empty($_POST))
		{
			
			$this->User->UserDetail->id = parent::__get_user_detail_id();
			$this->User->UserDetail->saveField('linkedin_verification_profile_url',$_POST['values'][0]['publicProfileUrl']);
			$user_data = $this->User->find('first',array('conditions'=>array('User.id'=>$user_id),'fields'=>array('User.id','UserDetai.user_id','USerDetail.linkdin_url')));
			if(empty($user_data['UserDetail']['linkdin_url']))
			{
				$this->User->UserDetail->id =  parent::__get_user_detail_id();;
				$this->User->UserDetail->saveField('linkdin_social_media_validation',0);
				$msg = 'You are not verified with linkdin.';
				$this->Session->setFlash(__($msg),'default',array("class"=>"success"));
			}
			else
			{
				if($user_data['UserDetail']['linkdin_url'] == $_POST['values'][0]['publicProfileUrl'])
				{
					$this->User->UserDetail->id =  parent::__get_user_detail_id();
					$this->User->UserDetail->saveField('linkdin_social_media_validation',1);
					$msg ='You are successfully verified with linkdin.';
					$this->Session->setFlash(__($msg),'default',array("class"=>"success"));
				}
				else
				{
					$this->User->UserDetail->id =  parent::__get_user_detail_id();;
					$this->User->saveField('linkdin_social_media_validation',0);
					$msg ='You are not verified with linkdin.';
					$this->Session->setFlash(__($msg),'default',array("class"=>"success"));
				}
			}
			//parent::social_media_authentication_mail($user_id,$msg);
		}
	}


	function checkip_validation($ipaddress=null)
	{
		
		$ip_validation_status = true;
		$buf = '';
		$license_key = "7aWly84C8Bsy";
		$query = "http://geoip.maxmind.com/f?l=" . $license_key . "&i=" . $ipaddress;
		$url = parse_url($query);
		$host = $url["host"];
		$path = $url["path"] . "?" . $url["query"];
		$timeout = 1;
		$fp = fsockopen ($host, 80, $errno, $errstr, $timeout)
				or die('Can not open connection to server.');
		if ($fp) {
		  fputs ($fp, "GET $path HTTP/1.0\nHost: " . $host . "\n\n");
		  while (!feof($fp)) {
			$buf .= fgets($fp, 128);
		  }
		  $lines = explode("\n", $buf);
		  $data = $lines[count($lines)-1];
		  fclose($fp);
		} else {
		  
		}
		$location_array = explode(",",$data);
		$country_data = $this->Country->findById($this->request->data['UserDetail']['country_id']);
		
		if(isset($location_array[0]))
		{
			if(strtolower($country_data['Country']['iso2']) != strtolower($location_array[0]))	
			{
				$ip_validation_status = false;
			}
		}
		else
		{
			$ip_validation_status = false;
		}
		if(isset($location_array[2]))
		{
			if(strtolower($this->request->data['UserDetail']['city']) != strtolower($location_array[2]))	
			{
				$ip_validation_status = false;
			}
		}
		else
		{
			$ip_validation_status = false;
		}
		
		return $ip_validation_status;
		//echo $data;
	}
	
	
	
	
	
	/*
	 * 2014   
	 * User Public View Controller Action   
	 * pashkovdenis@gmail.com  
	 *  
	 */


	public function user_public_view($id=null)
	{
		 $this->layout = 'lay_user_detail';
		 $this->set('title_for_layout','User Public View');
		 $this->loadModel('Project');
		 $this->loadModel('Category');
		 $this->loadModel("User")  ;  
		 $this->loadModel("Job") ;  
		 $this->loadModel("JobBid")  ;
		 $jobBid  = new JobBid() ;  
		 App::import("model" ,  "FeedbackFactory")  ;
		// $current_user  = (new User())->find("first",  array("conditions"=>array("id"=>$this->Auth->user("id")))) ;
		 $current_user1  = new User();
		 
		$current_user = $current_user1  ->find("first",  array("conditions"=>array("id"=>$this->Auth->user("id")))) ;
		 if ($current_user["User"]["role_id"]!=4){
		 	$this->set("expert"   , 1);
		 }
		   
		 // Send FeedBAck Here : 
		  if (isset($_POST["feedback"])) {
		 	FeedbackFactory::checkFeedback($this->Auth->user("id")) ; 
		 	$this->Session->setFlash(__(' Feedback has been sent'),'default',array("class"=>"success")); 
		 	$this->redirect("/users/user_public_view/{$id}") ;  
		  }
		  
		 
		 
		 // Check For Feedback
		  if (isset($_GET["feedback"]) &&   FeedbackFactory::needPopup($id)   )
		  {
		  	 	$this->set("feed_list" ,  FeedbackFactory::getList($id));
		  		$this->set("feedback" ,  1 );
		   
		  }
		 
		 
		 $this->Project->bindModel(array(
		 		'belongsTo'=>array(
		 				'User',
		 				'IdeaMaturity',
		 				'ProjectStatus',
		 				'ProjectType',
		 				'Availability',
		 				'BusinessPlanLevel',
		 				'Region',
		 				'Country',
		 				'State',
						"Category"
		 		)
		 ),false); 
		 
	 
		 
		$this->User->Behaviors->attach('Containable');
		$this->User->bindModel(array(
			'hasOne'=>array(
				'UserDetail'
			),
			'hasAndBelongsToMany'=>array(
				'Skill',
				'WorkingStatus'
			),
			'hasMany'=>array(
				'Project',
				'UserPortfolio'
			)
			
		)
		,false);  
		

		 
		
		$this->User->UserDetail->bindModel(array(
				'belongsTo'=>array(
					'Country',
					'ExpertiseCategory'=>array(
						'className'=>'Category',
						'foreignKey'=>'expertise_category_id'
					),
					'LeaderCategory'=>array(
						'className'=>'Category',
						'foreignKey'=>'leadership_category_id'
					)/* ,
					'Availability' */
					
					
				)
			)
		,false);
		
	 
	 	 $this->Project->Behaviors->attach('Containable');  
		
	 	 
	 	 //$apply =  [1];    //  Applied ids of the projects     :    
	 	  $apply =  array(1);
	 	 $bids  = $jobBid->find("all" , array("conditions"=>array("user_id"=>$id)));  
	 	 
	 	 foreach ($bids as $b){
	 	 		$job =  $this->Job->find("first", array("conditions"=>array("Job.id"=>$b["JobBid"]["job_id"])));  
 				if (!empty($job["Job"]["project_id"]))
	 	 		$apply[] = $job["Job"]["project_id"];  
	 	 	  
	 	 	
	 	 	
	 	 }     
 
	 	
	 	 
	 	 $this->paginate = 	array(
	 	 		'conditions'=> "user_id='{$id}' OR Project.id IN (".join(",",$apply).") ",
	 	 		'page'=>1,
	 	 		'limit'=>Configure::read('App.PageLimit'),
	 	 		'order'=> " Project.id " ,
	 	 		'contain'=>array(
	 	 				'User'=>array(
	 	 						'UserDetail'=>array(
	 	 								'fields'=>array('UserDetail.name_visibility', 'UserDetail.country_id','UserDetail.gender'),
	 	 								'Country'=>array(
	 	 										'conditions'=>array('Country.status'=>Configure::read('App.Status.active')),
	 	 										'fields'=>array('Country.name','Country.country_flag')
	 	 								)
	 	 						),
	 	 						'fields'=>array('User.first_name','User.last_name'),
	 	 				),
	 	 				'IdeaMaturity'=>array(
	 	 						'conditions'=>array('IdeaMaturity.status'=>Configure::read('App.Status.active')),
	 	 						'fields'=>array('IdeaMaturity.name')
	 	 				),
	 	 				'ProjectStatus'=>array(
	 	 						'conditions'=>array('ProjectStatus.status'=>Configure::read('App.Status.active')),
	 	 						'fields'=>array('ProjectStatus.name')
	 	 				),
	 	 				'ProjectType'=>array(
	 	 						'conditions'=>array('ProjectType.status'=>Configure::read('App.Status.active')),
	 	 						'fields'=>array('ProjectType.name')
	 	 				),
						"Category"=>array( 
									 			"conditions"=>("Category.id =Project.category_id"),
												"fields"=>array("Category.name","Category.id")
									), 
	 	 				'Availability'=>array(
	 	 						'conditions'=>array('Availability.status'=>Configure::read('App.Status.active'))
	 	 						,'fields'=>array('Availability.name')
	 	 				),
	 	 				'BusinessPlanLevel'=>array(
	 	 						'conditions'=>array('BusinessPlanLevel.status'=>Configure::read('App.Status.active')),
	 	 						'fields'=>array('BusinessPlanLevel.name')
	 	 				),
	 	 				'Region'=>array(
	 	 						'conditions'=>array('Region.status'=>Configure::read('App.Status.active')),
	 	 						'fields'=>array('Region.name')
	 	 				),
	 	 				'Country'=>array(
	 	 						'conditions'=>array('Country.status'=>Configure::read('App.Status.active')),
	 	 						'fields'=>array('Country.name')
	 	 				),
	 	 				'State'=>array(
	 	 						'conditions'=>array('State.status'=>Configure::read('App.Status.active')),
	 	 						'fields'=>array('State.name')
	 	 				)
	 	 		),
	 	 		'fields'=>array('Project.id','Project.title','Project.project_image','Project.description','Project.project_description_visibility','Project.self_investment_option','Project.self_invest_money','Project.external_fund_option','Project.external_fund_money','Project.user_id')
	 	 
	 	 );
	 	 $data = $this->paginate('Project'); 
	 	 $this->set("data",  $data);  
	 	 
	 	 // End Projects :  
	 	 
	 	 
		$userPublicView=$this->User->find(
			'first',
			array(
			
				'group' => array('User.id'),
				'conditions'=>array('User.id'=>$id),
				'contain'=>array(
					'UserDetail'=>array(
						'Country'=>array(
							'conditions'=>array('Country.status'=>Configure::read('App.Status.active')),
							'fields'=>array('Country.name','Country.country_flag')
						),
						'ExpertiseCategory'=>array(
							'conditions'=>array('ExpertiseCategory.status'=>Configure::read('App.Status.active')),
							'fields'=>array('ExpertiseCategory.name')
						),
						'LeaderCategory'=>array(
							'conditions'=>array('LeaderCategory.status'=>Configure::read('App.Status.active')),
							'fields'=>array('LeaderCategory.name')
						)/* ,
						'Availability'=>array(
							'conditions'=>array('Availability.status'=>Configure::read('App.Status.active')),
							'fields'=>array('Availability.name')
						) */,
						 
					),
					'Skill'=>array('fields'=>array('Skill.id','Skill.name')),
					'Project'=>array(
						 
					),
					'WorkingStatus'=>array(
						'conditions'=>array('WorkingStatus.status'=>Configure::read('App.Status.active')),
						'fields'=>array('WorkingStatus.name')
					),
					'UserPortfolio'=>array(
						'fields'=>array('UserPortfolio.image','UserPortfolio.title','UserPortfolio.url','UserPortfolio.category_id','UserPortfolio.description' )
					)
				)
			)
		);
		
		$status_id = $this->User->query("SELECT working_status_id FROM users_working_statuses WHERE user_id = '{$userPublicView["User"]["id"]}' "); 
		 	 
			
			if (isset($status_id[0]["users_working_statuses"])){
			$status =   $this->User->query("SELECT name FROM working_statuses WHERE id  = '{$status_id[0]["users_working_statuses"]["working_status_id"]}' ") ; 
		 	 $userPublicView["status"] =  $status[0]["working_statuses"]["name"]  ; }else
		 	{
		 		$userPublicView["status"] = ""; 
		 	}
			 
			//echo count($apply);
		$no_pro_exp=  $this->Project->find("all", array(
	 	 		'conditions'=>array("user_id='{$userPublicView["User"]["id"]}' OR Project.id IN (".join(",",$apply).") "),
					'fields'=>array('Project.id')
					)
					);
		//print_r($no_pro_exp);
		
	  	$no_of_pro =  $this->User->query("SELECT count(*) as count FROM  projects WHERE user_id='{$userPublicView["User"]["id"]}'  "); 
		$userPublicView['no_of_pro']=$no_of_pro ;
		// Select Users  Skills :    
		 $skills =  $this->User->query("SELECT skill_id FROM skills_users WHERE user_id='{$userPublicView["User"]["id"]}'  "); 
			 $this->loadModel("Skill") ; 
			 $Skill_model  = new Skill() ; 
		 
			 $skills_loaded = array(); 
			 
			//  foreach Skills   
			foreach($skills as $s){
				$sk = $Skill_model->find("first" , array("conditions"=> array("id"=>$s["skills_users"]["skill_id"])) ) ; 
				$skills_loaded[]=  $sk["Skill"]["name"] ; 	
				
		 	}
		
		 	$userPublicView["skills"] =  $skills_loaded ; 
		 	$avail =    $this->User->query("SELECT name FROM availabilities WHERE id='{$userPublicView["UserDetail"]["availability_id"]}' ") ;  
		 	if (isset($avail[0]))
		 	$userPublicView["avail"] = $avail[0]["availabilities"]["name"] ;
			else
				$userPublicView["avail"] = "" ;
			
			
			//add category into  portfolio   
			
			foreach($userPublicView["UserPortfolio"] as $k=>$item){ 
			 
				$c =  $this->Category->find("first",array("conditions"=>array(  "Category.id"=>$item["category_id"]))); 
			 
				$userPublicView["UserPortfolio"][$k]["cat"]  = " Category: " .  	$c["Category"]["name"]; 
				
			}
			
			
		
		$this->set(compact('userPublicView')); 
	 }	
	
	
	
	
	// Method   to chekc  if there was     
	public function userExists($id=null){
		$this->autoRender = false ;  
 	 	$this->loadModel("Users") ;  
 	 	$user = $this->Users->find("first", array("conditions"=>array("username"=>$id))); 
	  if ($user){
	  	echo $user["Users"]["first_name"]. " ".$user["Users"]["last_name"]  ; 
	  }else{
	  	echo "false"  ;
	  }
 	 	 
 	 	
	 
		 
	}
	
	
	
	
	
	
	/*
	 * invite Job Action  
	 * 
	 */
	public function getProjectList(){ 
		$data = array();  
		$this->autoRender = false ;  
		$this->loadModel("User") ;   
		$this->loadModel("Project")   ; 
		$project =  new Project() ; 
		$list =  $project->find("all" , array("conditions"=>array("user_id"=>$this->Auth->user("id"),  "status"=>1  ))) ;  
		foreach($list as $r){
			$data[$r["Project"]["id"]] =  $r["Project"]["title"];
		}
		
		echo json_encode($data) ; 
	} 
	 
	
	// Get job List By  Projects   
	public function getGetJobList($project_id ){
		$this->autoRender = false;  
		$this->loadModel("Job") ; 
		$job_model  =  new Job() ;  
		$data =  array();  
		$list =  $job_model->find("all" , array("conditions"=>array("project_id"=>$project_id, "status"=>1))); 
		foreach($list as $j)  
			if (!Job::isApply($j["Job"]["id"]))
			$data[$j["Job"]["id"]] =   $j["Job"]["title"]; 
		 echo json_encode($data) ; 
	}
	
	
	
	
	/*
	 * Invite  Other  users To  job Team Up  
	 * pashkovdenis@gmail.com  
	 * 2014  
	 * 
	 */
	
	public function invitejob(){  
		
		$this->loadModel("jobInvite"); 
		$this->loadModel("Users") ; 
		$model  = new jobInvite() ; 
		$this->autoRender = false ; 
		$text= INVITE_TO_USER_JOB  ; 
		//$me  = (new User())->find("first" , array("conditions"=>array("id"=>$this->Auth->user("id")))); 
		$me  = new User();
		$me= $this->Users->find("first" , array("conditions"=>array("id"=>$this->Auth->user("id")))); 
	   // $other =  (new User())->find("first" , array("conditions"=>array("id"=> $_POST["user_id"]))); 
		 $other =  new User();
		 $other = $this->Users->find("first" , array("conditions"=>array("id"=> $_POST["user_id"]))); 
		 
		$result  =  $model->sendInvite($me, $other, $_POST["Job"],  $_POST["project"] ) ;
		
		if ($result)
		$this->Session->setFlash(__('Invate has been Sent, email has been sent'),'default',array("class"=>"success"));
		else
		 $this->Session->setFlash(__('Error Select Project/Job'),'default',array("class"=>"error")); 
			
		$this->redirect("/users/search_expert") ; 
		
	}
	
	
	
	// Decline invitation  
	public function decline($job_id){ 
		$this->autoRender= false ;  
		$this->loadModel("jobInvite");
		$model  = new jobInvite() ; 
		$model->decline($this->Auth->user("id"), $job_id) ;  
		$this->Session->setFlash(__('Declined'),'default',array("class"=>"success")); 
		$this->redirect('/jobs/my_job') ; 
	 }
	
	
	
	/*
	 * Import Countries
	 */


    public function importstates(){
        $this->autoRender = false ;
        $this->loadModel("Country");
        $model = new Country() ;
        echo  "<p> Import States  563  </p>";
        $file = file_get_contents(ROOT."/app/tmp/states_amirc.csv");
        $lines  = explode("\n", $file) ;
        foreach($lines as $state)
            $model->query("INSERT INTO states SET name='{$state}' ,  country_id ='563' ,  status=1");

        echo  "<p> Import States  481  </p>";
        $file = file_get_contents(ROOT."/app/tmp/states_india.csv");
        $lines  = explode("\n", $file) ;
        foreach($lines as $state)
            $model->query("INSERT INTO states SET name='{$state}' ,  country_id ='481' ,  status=1");
 }




    public function importcountries(){
        $this->autoRender = false ;
        $this->loadModel("Country");
        $model = new Country() ;
        echo "<p> Begin Import From Cssv </p>" ;
        $file = file_get_contents(ROOT."/app/tmp/CCSV.csv");
        $lines  = explode("\n", $file) ;
        if (count($lines)){
            echo "<p> Begin import </p>" ;
            $model->query("DELETE FROM countries");

            foreach($lines as $country){
                   $data = explode(",",$country) ;
                   $region_name  = trim($data[3]);
                   $model = new Country() ;

                   $region_id  =  $model->query("SELECT * FROM regions WHERE regions.name LIKE '{$region_name}' ");
                   $name  = trim($data[2]);
                   $code  = trim($data[1]);
                    $icon  = trim($data[0]);
                $model->query("INSERT INTO countries SET region_id='{$region_id[0]["regions"]["id"]}'  , iso2='{$code}', name='{$name}', flag='{$icon}',  iso3='{$code}', country_flag='{$icon}' ,status=1 ");



            }


        }



    }
	
}