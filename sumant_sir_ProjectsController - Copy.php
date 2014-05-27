<?php
/**
 * Projects Controller
 *
 * PHP version 5.4
 *
 */
class ProjectsController extends AppController {
	/**
     * ProjectVisibilities name
     *
     * @var string
     * @access public
     */
	var	$name	=	'Projects';
	var	$uses	=	array('Project','Region','Country','State');
	var $helpers = array('Html','General');
	var $components = array('General','Upload','RequestHandler');
	var $model='Project';
	var $controller='projects';
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
		
		$this->loadModel('User');
		$this->loadModel('Category');
		$this->loadModel('ProjectStatus');
		
		
		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}	
		
		$this->$model->bindModel(
			array(
				'belongsTo'=>array(
					'User'=>array(
						'fields'=>array(
							'User.id','User.username'
						),
						
						
					),
					'ProjectStatus'=>array(
						'fields'=>array(
							'ProjectStatus.id','ProjectStatus.name'
						)
						
					)
					,
					'Category'=>array(
						'fields'=>array(
							'Category.id','Category.name'
						),
						'type'=>'inner'
					)
				)
			)
		,false
		);
		
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
		
			$tabs = array('All'=>$active+$inactive);
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
			parent::rrmdir(PROJECT_PLAN_PATH_FOLDER.$id);
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
				
				if(empty($this->request->data['Project']['business_plan_doc']['tmp_name']))
				{
					
					unset($this->request->data['Project']['business_plan_doc']);
				}
				
				if(empty($this->request->data['Project']['project_image']['tmp_name']))
				{
					
					unset($this->request->data['Project']['project_image']);
				}
				
			
				$this->$model->set($this->request->data);
				$this->$model->setValidation('admin');
				
				if ($this->$model->validates()) {

					if(!empty($this->request->data['Project']['business_plan_doc']['tmp_name']))
					{
						$file_array = $this->request->data['Project']['business_plan_doc'];
						unset($this->request->data['Project']['business_plan_doc']);
					}
					else
					{
						$file_array = '';
					}
					
					if(!empty($file_array))
					{									
						$file_name	=	time();
						/* this is being used to upload user big size profile image*/
						unlink(str_replace('{project_id}',$id,PROJECT_PLAN_PATH).$this->request->data['Project']['plan_hidden']);
						$filename	=	parent::__upload($file_array,str_replace('{project_id}',$id,PROJECT_PLAN_PATH),$file_name);
						
						$this->Project->saveField('business_plan_doc',$filename);
						unset($this->request->data["Project"]['business_plan_doc']);
						
					}
					else{
						$this->request->data['Project']['business_plan_doc']=$this->request->data['Project']['plan_hidden']; 
					}
					
					if(!empty($this->request->data['Project']['project_image']['tmp_name']))
					{
						$project_image = $this->request->data['Project']['project_image'];
						unset($this->request->data['Project']['project_image']);
					}
					else
					{
						$project_image = '';
					}
					
					if(!empty($project_image))
					{									
						$file_name	=	time();
						/* this is being used to upload user big size profile image*/
						@unlink(str_replace('{project_id}',$id,PROJECT_IMAGE_PATH)."big_".$this->request->data['Project']['image_hidden']);
						
						@unlink(str_replace('{project_id}',$id,PROJECT_IMAGE_PATH)."thumb_".$this->request->data['Project']['image_hidden']);
						
						@unlink(str_replace('{project_id}',$id,PROJECT_IMAGE_PATH)."small_".$this->request->data['Project']['image_hidden']);
						
						@unlink(str_replace('{project_id}',$id,PROJECT_IMAGE_PATH)."original_".$this->request->data['Project']['image_hidden']);
						
						parent::__uploadFile($project_image,str_replace('{project_id}',$id,PROJECT_IMAGE_BIG_PATH),"big_".$file_name,PROJECT_IMAGE_WIDTH_BIG,PROJECT_IMAGE_HEIGHT_BIG);
							
						parent::__uploadFile($project_image,str_replace('{project_id}',$id,PROJECT_IMAGE_THUMB_PATH),"thumb_".$file_name,PROJECT_IMAGE_WIDTH_THUMB,PROJECT_IMAGE_HEIGHT_THUMB);
						
						parent::__uploadFile($project_image,str_replace('{project_id}',$id,PROJECT_IMAGE_SMALL_PATH),"small_".$file_name,PROJECT_IMAGE_WIDTH_SMALL,PROJECT_IMAGE_HEIGHT_SMALL);
						
						$filename = parent::__upload($project_image,str_replace('{project_id}',$id,PROJECT_IMAGE_ORIGINAL_PATH),$file_name);
						
						
						$this->Project->saveField('project_image',$filename);
						unset($this->request->data["Project"]['project_image']);
						
					}
					else{
						$this->request->data['Project']['project_image']=$this->request->data['Project']['image_hidden']; 
					}
					
					
					if ($this->$model->save($this->request->data,false)) {
						$this->Session->setFlash(__('The '.$model.' information has been updated successfully',true), 'admin_flash_success');
						$this->redirect(array('action' => 'index'));
					} else {
						$this->request->data['Project']['business_plan_doc']=$this->request->data['Project']['plan_hidden'];
						$this->Session->setFlash(__('The '.$model.' could not be saved. Please, try again.',true), 'admin_flash_error');
					}
				}
				else {
					$this->request->data['Project']['business_plan_doc']=$this->request->data['Project']['plan_hidden'];
					$this->Session->setFlash(__('The '.$model.' could not be saved. Please, correct errors.', true), 'admin_flash_error');
				}
			}	
        }
		else {
			$this->request->data = $this->$model->read(null, $id);
		}
		
		
		/*load Model*/
		$this->loadModel('User');
		$this->loadModel('Category');
		$this->loadModel('ProjectType');
		$this->loadModel('Availability');
		$this->loadModel('IdeaMaturity');
		$this->loadModel('ProjectVisibility');
		$this->loadModel('ProjectStatus');
		$this->loadModel('BusinessPlanLevel');
		$users=$this->User->find('list',array('conditions'=>array('User.status'=>Configure::read('App.Status.active'),'User.role_id'=>array(Configure::read('App.Role.Buyer'),Configure::read('App.Role.Both'))), 'fields'=>array('User.id','User.username')));
		$categories=$this->Category->find('list',array('conditions'=>array('Category.status'=>Configure::read('App.Status.active'),'Category.type_for'=>Configure::read('App.Category.Project'),'Category.parent_id'=>0)));
		
		$sub_categories = array();
		
		if(isset($this->request->data['Project']['sub_category_id']) && !empty($this->request->data['Project']['sub_category_id']))
		{
			$sub_categories=$this->Category->find('list',array('conditions'=>array('Category.status'=>Configure::read('App.Status.active'),'Category.type_for'=>Configure::read('App.Category.Project'),'Category.parent_id'=>$this->request->data['Project']['category_id'])));
		}
		
		$project_manager_availabilities=$this->Availability->find('list',array('conditions'=>array('Availability.status'=>Configure::read('App.Status.active'))));
		
		$idea_maturities=$this->IdeaMaturity->find('list',array('conditions'=>array('IdeaMaturity.status'=>Configure::read('App.Status.active'))));
		
		$project_visibilities=$this->ProjectVisibility->find('list',array('conditions'=>array('ProjectVisibility.status'=>Configure::read('App.Status.active')),'order'=>array('ProjectVisibility.order_key'=>'asc')));
		
		$business_plan_levels=$this->BusinessPlanLevel->find('list',array('conditions'=>array('BusinessPlanLevel.status'=>Configure::read('App.Status.active'))));
		
		$project_types = $this->ProjectType->find('list',array('conditions'=>array('ProjectType.status'=>Configure::read('App.Status.active'))));
		
		$project_statuses = $this->ProjectStatus->find('list',array('conditions'=>array('ProjectStatus.status'=>Configure::read('App.Status.active'))));
		
		$this->set(compact('users','categories','project_manager_availabilities','idea_maturities','project_visibilities','business_plan_levels','project_types','project_statuses','sub_categories'));
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
    }

	public function admin_view($id=null){
		$model=$this->model;
		$controller=$this->controller;
		$this->Project->id = $id;
        if (!$this->Project->exists()) {
            throw new NotFoundException(__('Invalid project'));
        }
		$this->loadModel('User');
		$this->loadModel('Category');
		$this->$model->bindModel(
			array(
				'belongsTo'=>array(
					'User'=>array(
						'fields'=>array(
							'User.id','User.username'
						),
						//'type'=>'inner'
						
					),
					'ProjectType'=>array(
						'fields'=>array(
							'ProjectType.id','ProjectType.name'
						)
						
					),
					'ProjectStatus'=>array(
						'fields'=>array(
							'ProjectStatus.id','ProjectStatus.name'
						)
						
					),
					'BusinessPlanLevel'=>array(
						'fields'=>array(
							'BusinessPlanLevel.id','BusinessPlanLevel.name'
						)
						
					),
					'Availability'=>array(
						'fields'=>array(
							'Availability.id','Availability.name'
						)
						
					),
					'ProjectVisibility'=>array(
						'fields'=>array(
							'ProjectVisibility.id','ProjectVisibility.name'
						)
						
					),
					'Category'=>array(
						'fields'=>array(
							'Category.id','Category.name'
						),
						'type'=>'inner'
					)
				)
			)
		,false
		);
		$this->$model->recursive=3;
		$data=$this->$model->read(null, $id); 
		$this->set(compact('data'));
		//pr($data);die;
	}

	/*
	* Add new Agreement
	*/	
    public function admin_add() {
        $model=$this->model;
		$controller=$this->controller;
		$result = $this->Project->find('first', array( 'fields' => array('MAX(id) AS max_id')));
		
		
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
						//pr($this->request->data);die;
					if(!empty($this->request->data['Project']['business_plan_doc']['tmp_name']))
					{
						$file_array = $this->request->data['Project']['business_plan_doc'];
						unset($this->request->data['Project']['business_plan_doc']);
					}
					else
					{
						$file_array = '';
					}
					if(!empty($this->request->data['Project']['project_image']['tmp_name']))
					{
						$project_image = $this->request->data['Project']['project_image'];
						unset($this->request->data['Project']['project_image']);
					}
					else
					{
						$project_image = '';
					}
					if ($this->$model->saveAll($this->request->data)) {
						$id=$this->$model->id;
						$activationKey = mt_rand(1000,1000000).$id;
						$this->Project->updateAll(array('Project.project_identification_no'=>$activationKey),array('Project.id'=>$id));
						
						parent::__copy_directory(PROJECT_PLAN_PATH_DEFAULT,PROJECT_PLAN_PATH_FOLDER.$id);
						
						
						if(!empty($project_image))
						{									
							$file_name	=	time();
							/* this is being used to upload user big size profile image*/
							
							parent::__uploadFile($project_image,str_replace('{project_id}',$id,PROJECT_IMAGE_BIG_PATH),"big_".$file_name,PROJECT_IMAGE_WIDTH_BIG,PROJECT_IMAGE_HEIGHT_BIG);
							
							parent::__uploadFile($project_image,str_replace('{project_id}',$id,PROJECT_IMAGE_THUMB_PATH),"thumb_".$file_name,PROJECT_IMAGE_WIDTH_THUMB,PROJECT_IMAGE_HEIGHT_THUMB);
							
							parent::__uploadFile($project_image,str_replace('{project_id}',$id,PROJECT_IMAGE_SMALL_PATH),"small_".$file_name,PROJECT_IMAGE_WIDTH_SMALL,PROJECT_IMAGE_HEIGHT_SMALL);
							
							$filename = parent::__upload($project_image,str_replace('{project_id}',$id,PROJECT_IMAGE_ORIGINAL_PATH),$file_name);
							
							
							$this->Project->saveField('project_image',$filename);
							//unset($this->request->data["Project"]['business_plan_doc']);
							
						}
						
						if(!empty($file_array))
						{									
							$file_name	=	time();
							/* this is being used to upload user big size profile image*/
							$filename	=	parent::__upload($file_array,str_replace('{project_id}',$id,PROJECT_PLAN_PATH),$file_name);
							
							$this->Project->saveField('business_plan_doc',$filename);
							//unset($this->request->data["Project"]['business_plan_doc']);
							
						}
						
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
		
		/*load Model*/
		$this->loadModel('User');
		$this->loadModel('Category');
		$this->loadModel('ProjectType');
		$this->loadModel('Availability');
		$this->loadModel('IdeaMaturity');
		$this->loadModel('ProjectVisibility');
		$this->loadModel('ProjectStatus');
		$this->loadModel('BusinessPlanLevel');
		$users=$this->User->find('list',array('conditions'=>array('User.status'=>Configure::read('App.Status.active'),'User.role_id'=>array(Configure::read('App.Role.Buyer'),Configure::read('App.Role.Both'))), 'fields'=>array('User.id','User.username')));
		
		$sub_categories = array();
		$categories=$this->Category->find('list',array('conditions'=>array('Category.status'=>Configure::read('App.Status.active'),'Category.type_for'=>Configure::read('App.Category.Project'),'Category.parent_id'=>0)));
		
		if(isset($this->request->data['Project']['category_id']) && !empty($this->request->data['Project']['category_id']))
		{
			$sub_categories=$this->Category->find('list',array('conditions'=>array('Category.status'=>Configure::read('App.Status.active'),'Category.type_for'=>Configure::read('App.Category.Project'),'Category.parent_id'=>$this->request->data['Project']['category_id'])));
		}
		$project_manager_availabilities=$this->Availability->find('list',array('conditions'=>array('Availability.status'=>Configure::read('App.Status.active'))));
		
		$idea_maturities=$this->IdeaMaturity->find('list',array('conditions'=>array('IdeaMaturity.status'=>Configure::read('App.Status.active'))));
		$project_visibilities=$this->ProjectVisibility->find('list',array('conditions'=>array('ProjectVisibility.status'=>Configure::read('App.Status.active')),'order'=>array('ProjectVisibility.order_key'=>'asc')));
		$business_plan_levels=$this->BusinessPlanLevel->find('list',array('conditions'=>array('BusinessPlanLevel.status'=>Configure::read('App.Status.active'))));
		$project_types = $this->ProjectType->find('list',array('conditions'=>array('ProjectType.status'=>Configure::read('App.Status.active'))));
		$project_statuses = $this->ProjectStatus->find('list',array('conditions'=>array('ProjectStatus.status'=>Configure::read('App.Status.active'))));
		// pr($project_types); die;
		$this->set(compact('users','categories','project_manager_availabilities','idea_maturities','project_visibilities','business_plan_levels','project_types','project_statuses','sub_categories'));
		//pr($this->request->data);die;
    }
	
	public function admin_download_file($project_id=null){
		    $model=$this->model;
			$controller=$this->controller;
			$this->$model->id = $project_id;
			if (!$this->$model->exists()) {
            throw new NotFoundException(__('Invalid '.$model.''));
			}
			
			$data=$this->$model->find('first',array('conditions'=>array($model.'.id'=>$project_id),'fields'=>array($model.'.business_plan_doc',$model.'.id')));
			$file_name=$data[$model]['business_plan_doc'];
			$file_path = WWW_ROOT .PROJECT_DIR.'/'.$data[$model]['id'].'/plan/'.$data[$model]['business_plan_doc'];
			
			  if (file_exists($file_path)) {
            ob_start();
            //echo $file_name; die;
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.trim($file_name));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            ob_clean();

            flush();
            readfile($file_path);
            exit;
        }
			
			die;
	
	}
/*****************************Front Code Start Here***************************/

	public function post_project()
	{
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
				$this->$model->setValidation('user');
				
				if ($this->$model->validates()) {					
						//pr($this->request->data);die;
						if(!empty($this->request->data['Project']['business_plan_doc']['tmp_name']))
						{
							$file_array = $this->request->data['Project']['business_plan_doc'];
							unset($this->request->data['Project']['business_plan_doc']);
						}
						else
						{
							$file_array = '';
						}
					$this->request->data['Project']['user_id']=$this->Session->read('Auth.User.id');
					
					if($this->$model->saveAll($this->request->data)){
						$id=$this->$model->id;
						$activationKey = mt_rand(1000,1000000).$id;
						$this->Project->updateAll(array('Project.project_identification_no'=>$activationKey),array('Project.id'=>$id));
						
						parent::__copy_directory(PROJECT_PLAN_PATH_DEFAULT,PROJECT_PLAN_PATH_FOLDER.$id);
						
						
						if(!empty($file_array))
						{									
							$file_name	=	time();
							/* this is being used to upload user big size profile image*/
							$filename	=	parent::__upload($file_array,str_replace('{project_id}',$id,PROJECT_PLAN_PATH),$file_name);
							
							$this->Project->saveField('business_plan_doc',$filename);
							
							
						}
						/***************Mailing Code for Admin******************/
						
						$user_project_post = $this->Template->find('first', array('conditions' => array('Template.slug' => 'user_project_post')));
						
						$email_subject = $user_project_post['Template']['subject'];
						$subject = __('[' . Configure::read('Site.title') . '] ' . 
						$email_subject . '', true);
						
						$url = Router::url(
								array
								(
									'controller' => 'projects',
									'action' => 'view',
									$id
								), 
								true
							);
						$url = "<a href='".$url."'>Click Here</a>";
					
						$mailMessage = str_replace(array('{NAME}','{TITLE}','{DESCRIPTION}','{DATE}','{LINK}','{SITE_LINK}'), array('Admin',$this->request->data['Project']['title'], $this->request->data['Project']['description'],date('F d, Y'),$url,Configure::read('Site.title')), $user_project_post['Template']['content']);
						
						$this->sendMail(Configure::read('App.AdminMail'),$subject,$mailMessage,array(Configure::read('App.AdminMail')=>Configure::read('Site.title')),$user_registration['Template']['id']);
						
						/***************Mailing Code for Admin End**************/
						
						/***************Mailing Code for User*******************/
											
						$user_project_post = $this->Template->find('first', array('conditions' => array('Template.slug' => 'user_project_post')));
						$email_subject = $user_project_post['Template']['subject'];
						$subject = __('[' . Configure::read('Site.title') . '] ' . $email_subject . '', true);
						
						$url = Router::url(
								array
								(
									'controller' => 'projects',
									'action' => 'view_project_detail',
									$id
								), 
								true
							);
						$url = "<a href='".$url."'>Click Here</a>";
						$name= $this->Session->read('Auth.User.first_name');
						$email = $this->Session->read('Auth.User.email');
						
						$mailMessage = str_replace(array('{NAME}','{TITLE}','{DESCRIPTION}','{DATE}','{LINK}','{SITE_LINK}'), array($name,$this->request->data['Project']['title'], $this->request->data['Project']['description'],date('F d, Y'),$url,Configure::read('Site.title')), $user_project_post['Template']['content']);
						
						$this->sendMail($email,$subject,$mailMessage,array(Configure::read('App.AdminMail')=>Configure::read('Site.title')),$user_project_post['Template']['id']);
						
						/***************Mailing Code for User End***************/
						
						$this->Session->setFlash(__(''.$model.' has been Posted successfully'), 'admin_flash_success');
						$this->redirect(array('action'=>'index'));
					} else {
						$this->Session->setFlash(__('The '.$model.' could not be post. Please, try again.'), 'front_flash_bad');
					}
				}
				else {
				$this->Session->setFlash('The '.$model.' could not be saved.  Please, correct errors.', 'front_flash_bad');
				}	
			}
		}
		
		/*load Model*/
		$this->loadModel('Category');
		$this->loadModel('ProjectType');
		$this->loadModel('Availability');
		$this->loadModel('IdeaMaturity');
		$this->loadModel('ProjectVisibility');
		$this->loadModel('ProjectStatus');
		$this->loadModel('BusinessPlanLevel');
		
		$categories=$this->Category->parentsList();
		
		$project_manager_availabilities=$this->Availability->find('list',array('conditions'=>array('Availability.status'=>Configure::read('App.Status.active'))));
		
		$idea_maturities=$this->IdeaMaturity->find('list',array('conditions'=>array('IdeaMaturity.status'=>Configure::read('App.Status.active'))));
		
		$project_visibilities=$this->ProjectVisibility->find('list',array('conditions'=>array('ProjectVisibility.status'=>Configure::read('App.Status.active')),'order'=>array('ProjectVisibility.order_key'=>'asc')));
		
		$business_plan_levels=$this->BusinessPlanLevel->find('list',array('conditions'=>array('BusinessPlanLevel.status'=>Configure::read('App.Status.active'))));
		
		$project_types = $this->ProjectType->find('list',array('conditions'=>array('ProjectType.status'=>Configure::read('App.Status.active'))));
		
		$project_statuses = $this->ProjectStatus->find('list',array('conditions'=>array('ProjectStatus.status'=>Configure::read('App.Status.active'))));
		
		$this->set(compact('categories','project_manager_availabilities','idea_maturities','project_visibilities','business_plan_levels','project_types','project_statuses'));
		 
	}
	
	public function my_post_project()
	{

		$model=$this->model;
		$controller=$this->controller;
		
		/*load model*/
		$this->loadModel('User');
		$this->loadModel('Category');
		$this->loadModel('ProjectStatus');
		
		
		
		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}	
		
		$this->$model->bindModel(
			array(
				'belongsTo'=>array(
					'User'=>array(
						'fields'=>array(
							'User.id','User.username'
						),
						//'type'=>'inner'
						
					),
					'ProjectStatus'=>array(
						'fields'=>array(
							'ProjectStatus.id','ProjectStatus.name'
						)
						
					)
					,
					'Category'=>array(
						'fields'=>array(
							'Category.id','Category.name'
						),
						'type'=>'inner'
					)
				)
			)
		,false
		);
		
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
				'limit'=>Configure::read('App.PageLimit'), 
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
		
			$tabs = array('All'=>$active+$inactive);
			$this->set(compact('tabs'));
		}
		
	}
	
	
	public function project_picupload(){ 
		
        //$this->layout = false;
		$this->loadModel('ImageTemp');
		
        if(isset($this->request->data["ImageTemp"]['project_image']['tmp_name'])){
            
			if(!empty($this->request->data["ImageTemp"]['project_image']['tmp_name']))
			{				
				$file_array	=	$this->request->data["ImageTemp"]['project_image'];
				$this->request->data["ImageTemp"]['project_image']= $this->request->data["ImageTemp"]['project_image']['name'];
			}
			
			if(!empty($file_array))
			{	
				$file_name	=	time();
				/* this is being used to upload user big size profile image*/
				
				parent::__uploadFile($file_array,PROJECT_TEMP_THUMB_DIR_232_232,"thumb_".$file_name,PROJECT_IMAGE_WIDTH_THUMB,PROJECT_IMAGE_HEIGHT_THUMB);			
				
				parent::__uploadFile($file_array,PROJECT_TEMP_BIG_DIR,"big_".$file_name,PROJECT_IMAGE_WIDTH_BIG,PROJECT_IMAGE_HEIGHT_BIG);
				
				parent::__uploadFile($file_array,PROJECT_TEMP_SMAll_DIR,"small_".$file_name,PROJECT_IMAGE_WIDTH_SMALL,PROJECT_IMAGE_HEIGHT_SMALL);
				
				$filename	=	parent::__upload($file_array,PROJECT_TEMP_ORIGINAL_DIR,$file_name);
			}	
           
        }
		$this->request->data['ImageTemp']['user_id']= $this->Auth->User('id');
		$this->request->data['ImageTemp']['project_image']=$filename;
		if(!empty($this->request->data)){
			$oldpic= $this->ImageTemp->find("first",array("conditions"=>array("ImageTemp.user_id"=>$this->Auth->User('id')),'fields'=>'ImageTemp.project_image '));
            if(!empty($oldpic['ImageTemp']['project_image']))
            {
                unlink(PROJECT_TEMP_THUMB_DIR_232_232.$oldpic["ImageTemp"]['project_image']);
				unlink(PROJECT_TEMP_BIG_DIR.$oldpic["ImageTemp"]['project_image']);
				unlink(PROJECT_TEMP_SMAll_DIR.$oldpic["ImageTemp"]['project_image']);
				unlink(PROJECT_TEMP_ORIGINAL_DIR.$oldpic["ImageTemp"]['project_image']);
				$avataruploaded = $this->ImageTemp->updateAll(array('ImageTemp.project_image'=>"'".$filename."'"),array('ImageTemp.user_id' =>$this->Auth->User('id')));                
            }else{
			
				$avataruploaded = $this->ImageTemp->saveAll($this->request->data);
			}
			if($avataruploaded){
				echo "success|".$filename;
			}else{
				echo "failed";
			} 
		}	
        die;
    }
	
	
	public function project_fileupload(){ 
		$this->loadModel('FileTemp');
		
        if(isset($this->request->data["FileTemp"]['project_file']['tmp_name'])){
            
			if(!empty($this->request->data["FileTemp"]['project_file']['tmp_name']))
			{				
				$file_array	=	$this->request->data["FileTemp"]['project_file'];
				$this->request->data["FileTemp"]['project_file']= $this->request->data["FileTemp"]['project_file']['name'];
			}
			
			if(!empty($file_array))
			{	
				$file_name	=	time();
				/* this is being used to upload user big size profile image*/
			
				$filename	=	parent::__upload($file_array,PROJECT_TEMP_THUMB_DIR_FILE,$file_name);
			}	
           
        }
		$this->request->data['FileTemp']['user_id']= $this->Auth->User('id');
		$this->request->data['FileTemp']['project_file']=$filename;
		if(!empty($this->request->data)){
			$avataruploaded = $this->FileTemp->saveAll($this->request->data);
			$lastInsert_id=$this->FileTemp->id;
			if($avataruploaded){
				echo "success|".$filename."|".$lastInsert_id;
			}else{
				echo "failed";
			} 
		}	
        die;
    }
	
	public function delete_project_file($id)
	{	
		$this->layout = false;
		$this->autoRender		=	false;
		$this->loadModel('FileTemp');
        $temp 					=	$this->FileTemp->find('first',array('conditions'=>array('FileTemp.id'=>$id)));
        $file					=	$temp['FileTemp']['project_file'];
        $this->FileTemp->id	=	$id;		
        unlink(PROJECT_TEMP_THUMB_DIR_FILE.$file);
        $this->FileTemp->delete();	
	}
	
	public function download_project_file($id)
	{
	
		$this->layout = false;
        $this->loadModel('FileTemp');            
        $data_file=$this->FileTemp->find('first',array('conditions'=>array('FileTemp.id'=>$id)));
		$fullPath = 'img/'.PROJECT_TEMP_THUMB_DIR_FILE_VIEW.$data_file['FileTemp']['project_file'];
		
		if ($fd = fopen ($fullPath, "r")) 
		{
			$fsize = filesize($fullPath);
			$path_parts = pathinfo($fullPath);
			$ext = strtolower($path_parts["extension"]);
            header('Content-Description: File Transfer');
			 // HTTP/1.1
			/* header('Content-type: application/pdf');			
			header('Content-type: application/js');
			header('Content-Type: application/octet-stream');
			header('Content-type: audio/mpeg');
			header("Cache-Control: no-cache, must-revalidate");
			header("Content-Type: application/force-download"); */
			 // HTTP/1.1			
            header('Content-Disposition: attachment; filename='.basename($fullPath));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fullPath));
            ob_clean();
            flush();
            readfile($fullPath);
            exit;
		}	
			
	}
	
	public function delete_project_file_edit()
	{
		
		$this->layout = false;
		$this->autoRender		=	false;
		$this->loadModel('ProjectFile');
        $temp 					=	$this->ProjectFile->find('first',array('conditions'=>array('ProjectFile.id'=>$this->params['pass'][0])));
        $file					=	$temp['ProjectFile']['project_file'];
        $this->ProjectFile->id	=	$temp['ProjectFile']['id'];	
		
        unlink(PROJECT_PLAN_PATH_FOLDER.$this->params['pass'][1].'/plan/'.$file);
        $this->ProjectFile->delete();	
	}
	
	public function download_project_file_edit()
	{
		
		$this->layout = false;
        $this->loadModel('ProjectFile');            
        $data_file=$this->ProjectFile->find('first',array('conditions'=>array('ProjectFile.id'=>$this->params['pass'][0])));
		$fullPath = PROJECT_PLAN_PATH_FOLDER.$this->params['pass'][1].'/plan/'.$data_file['ProjectFile']['project_file'];
		
		if ($fd = fopen ($fullPath, "r")) 
		{
			$fsize = filesize($fullPath);
			$path_parts = pathinfo($fullPath);
			$ext = strtolower($path_parts["extension"]);
			//$fl =  $fullPath;
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename='.basename($fullPath));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fullPath));
            ob_clean();
            flush();
            readfile($fullPath);
            exit;
		}	
			
	}
	
	public function delete_edit_business_file()
	{	
		
		$this->layout = false;
		$this->autoRender		=	false;
		$this->loadModel('ProjectBusinessplanFile');
        $temp 					=	$this->ProjectBusinessplanFile->find('first',array('conditions'=>array('ProjectBusinessplanFile.id'=>$this->params['pass'][0])));
        $file								=	$temp['ProjectBusinessplanFile']['file_name'];
        $this->ProjectBusinessplanFile->id	=	$temp['ProjectBusinessplanFile']['id'];	
		
        unlink(str_replace('{project_id}',$this->params['pass'][1],PROJECT_BUSINESS_PLAN_PATH_FOLDER).$file);
        $this->ProjectBusinessplanFile->delete();	
	}
	
	public function download_edit_business_file()
	{
		
		$this->layout = false;
        $this->loadModel('ProjectBusinessplanFile');            
        $data_file=$this->ProjectBusinessplanFile->find('first',array('conditions'=>array('ProjectBusinessplanFile.id'=>$this->params['pass'][0])));
		$fullPath = str_replace('{project_id}',$this->params['pass'][1],PROJECT_BUSINESS_PLAN_PATH_FOLDER).$data_file['ProjectBusinessplanFile']['file_name'];
		
		if ($fd = fopen ($fullPath, "r")) 
		{
			$fsize = filesize($fullPath);
			$path_parts = pathinfo($fullPath);
			$ext = strtolower($path_parts["extension"]);
			//$fl =  $fullPath;
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename='.basename($fullPath));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fullPath));
            ob_clean();
            flush();
            readfile($fullPath);
            exit;
		}	
			
	}
	public function project_general($id=null)
	{
		$this->set('title_for_layout','Project General');
		$this->layout = 'lay_my_project_job';
		$this->loadModel('Category');
		$this->loadModel('ProjectType');
		$this->loadModel('ProjectVisibility');
		$this->loadModel('ImageTemp');
		$this->loadModel('FileTemp');
		$this->loadModel('DreamOwner');
		$this->Project->bindModel(array('hasMany'=>array('ProjectFile')),false);	
		$Totdata=0;	
			if(!empty($this->request->data))
			{
				
				$this->Project->set($this->request->data);
				$this->Project->setValidation('project_general');
				if($this->Project->validates())
				{
					
					$this->request->data['Project']['user_id']=$this->Auth->User('id');
					//pr($this->request->data);die;
					
					if($this->Project->saveAll($this->request->data))
					{	
						$id = $this->Project->id;
						
						$activationKey = mt_rand(1000000,10000000).$id;
						$this->Project->updateAll(array('Project.project_identification_no'=>$activationKey),array('Project.id'=>$id));						 
						if(empty($this->request->data['Project']['id']))
						{						
							parent::__copy_directory(PROJECT_PLAN_PATH_DEFAULT,PROJECT_PLAN_PATH_FOLDER.$id);
						
							//copy file from project temp to project folder
							parent::__copy_directory(PROJECT_TEMP_THUMB_DIR_FILE,PROJECT_PLAN_PATH_FOLDER.$id.'/plan');
							//copy file from project image to project image folder
							parent::__copy_directory(PROJECT_TEMP_ORIGINAL_DIR,PROJECT_PLAN_PATH_FOLDER.$id.'/image/Original/');
							parent::__copy_directory(PROJECT_TEMP_BIG_DIR,PROJECT_PLAN_PATH_FOLDER.$id.'/image/Big/');
							parent::__copy_directory(PROJECT_TEMP_THUMB_DIR_232_232,PROJECT_PLAN_PATH_FOLDER.$id.'/image/Thumb/');
							parent::__copy_directory(PROJECT_TEMP_SMAll_DIR,PROJECT_PLAN_PATH_FOLDER.$id.'/image/Small/');
						
						}
						//unlink all temp folder image and file
						$oldfiles= $this->FileTemp->find("all",array("conditions"=>array("FileTemp.user_id"=>$this->Auth->User('id')),'fields'=>'FileTemp.project_file'));
						if(isset($oldfiles) && !empty($oldfiles))
						{
							foreach($oldfiles as $oldfile)
							{										
								unlink(PROJECT_TEMP_THUMB_DIR_FILE.$oldfile['FileTemp']['project_file']);
							}
						}
						$oldpic= $this->ImageTemp->find("first",array("conditions"=>array("ImageTemp.user_id"=>$this->Auth->User('id')),'fields'=>'ImageTemp.project_image'));
						if(isset($oldpic) && !empty($oldpic['ImageTemp']['project_image']))
						{					
							unlink(PROJECT_TEMP_ORIGINAL_DIR.$oldpic["ImageTemp"]['project_image']);
							unlink(PROJECT_TEMP_BIG_DIR."big_".$oldpic["ImageTemp"]['project_image']);
							unlink(PROJECT_TEMP_THUMB_DIR_232_232."thumb_".$oldpic["ImageTemp"]['project_image']);
							unlink(PROJECT_TEMP_SMAll_DIR."small_".$oldpic["ImageTemp"]['project_image']);
						}
						//delete project temp, image temp table data
						$this->FileTemp->deleteAll(array('FileTemp.user_id'=>$this->Session->read('Auth.User.id')));
						$this->ImageTemp->deleteAll(array('ImageTemp.user_id'=>$this->Session->read('Auth.User.id')));
						
						//check in database on edit time that this is already exist or not
						$Totdata=$this->DreamOwner->find('count',array('conditions'=>array('DreamOwner.project_id'=>$id)));
						if($Totdata<1){
							$this->request->data['DreamOwner']['project_id']=$id;
							$this->request->data['DreamOwner']['name']=$this->Auth->User('first_name');
							$this->request->data['DreamOwner']['ownership_percentage']='100%';
							$this->request->data['DreamOwner']['job_direction_id']='1';
							$this->request->data['DreamOwner']['status']='1';
							
							$this->DreamOwner->saveAll($this->request->data['DreamOwner']);
						}
						
						$this->Session->setFlash(__('Project general has been posted successfully.'),'default',array("class"=>"success"));
											
						$this->redirect(array('controller'=>'projects','action'=>'project_leader',$id));
					}
					else
					{	
						$this->Session->setFlash(__('Please correct the error listed.'),'default',array("class"=>"error"));
					}
				}
				else
				{	
					//pr($this->Project->validationErrors);
					$project_image=$this->ImageTemp->find('first',array('fields'=>array('project_image','id'),'conditions'=>array('ImageTemp.user_id'=>$this->Auth->User('id'))));
	
					$project_file=$this->FileTemp->find('all',array('fields'=>array('project_file','id'),'conditions'=>array('FileTemp.user_id'=>$this->Auth->User('id'))));
					
					$this->set(compact('project_image','project_file'));
					$this->Session->setFlash(__('Please correct the error listed.'),'default',array("class"=>"error"));
				}
			}
			else{
				if(!empty($id))
				{
										
					$this->request->data = $this->Project->read(null,$id);
					
					
				}
			}
		
		$project_image=$this->ImageTemp->find('first',array('fields'=>array('project_image','id'),'conditions'=>array('ImageTemp.user_id'=>$this->Auth->User('id'))));
	
		$project_file=$this->FileTemp->find('all',array('fields'=>array('project_file','id'),'conditions'=>array('FileTemp.user_id'=>$this->Auth->User('id'))));
		
		$project_parent_category=$this->Category->parentsList();
		
		$project_types = $this->ProjectType->find('list',array('conditions'=>array('ProjectType.status'=>Configure::read('App.Status.active'))));
		
		$visibility = $this->ProjectVisibility->find('list',array('conditions'=>array('ProjectVisibility.status'=>Configure::read('App.Status.active'))));
		
		$region = $this->Region->getResionListForUserRegistraion();
		
		if(isset($this->request->data['Project']['region_id']) && $this->request->data['Project']['region_id'])
		{
			$countries = $this->Country->getCountryListByRegionId($this->request->data['Project']['region_id']);
		}
		
		if(isset($this->request->data['Project']['country_id']) && $this->request->data['Project']['country_id'])
		{
			$states = $this->State->getStateList($this->request->data['Project']['country_id']);
		}
		
		if(isset($this->request->data['Project']['category_id']) && $this->request->data['Project']['category_id'])
		{
			$sub_categories = $this->Category->get_project_job_child_category_lists($this->request->data['Project']['category_id'],Configure::read('App.Category.Project'));
		}
		
		
		$this->set(compact('project_parent_category','sub_categories','project_types','visibility','project_image','project_file','region','id','countries','states'));
	}
	
	public function project_leader($id = NULL)
	{	
		$this->set('title_for_layout','Project Leader');
		$this->layout = 'lay_my_project_job';
		$this->loadModel('Availability');
		$this->loadModel('UserDetail');		
		$this->loadModel('DreamOwner');
		$this->loadModel('JobDirection');
		$this->DreamOwner->bindModel(array('belongsTo'=>array('JobDirection')),false);	
		
		if(!empty($this->request->data))
		{
			
			$this->Project->set($this->request->data);
			$this->Project->setValidation('admin');
			if($this->Project->validates())
			{
				if(!empty($this->request->data['Project']['id']) && $this->request->data['Project']['id']!=NULL)
				{
					if($this->Project->saveAll($this->request->data))
					{	//echo $this->request->data['Project']['id'];die;
						$this->Session->setFlash(__('Project leader has been posted successfully.'),'default',array("class"=>"success"));
						$this->redirect(array('controller'=>'projects','action'=>'project_status_timeline',$this->request->data['Project']['id']));
					}
					else
					{
						$this->Session->setFlash(__('The Project could not be saved.Please correct errors.'),'default',array("class"=>"error"));
						
					}
				}
				else
				{
					$this->Session->setFlash(__('UnAuthorized Process.'),'default',array("class"=>"error"));
					
				}
			}
			else
			{
				$this->Session->setFlash(__('The Project could not be saved.Please correct errors.'),'default',array("class"=>"error"));
			}
		}
		else
		{
			$this->request->data = $this->Project->read(null,$id);
			
		}
		
		$userdetail=$this->UserDetail->find('first',array('fields'=>array('linkdin_url','image'),'conditions'=>array('UserDetail.user_id'=>$this->Auth->User('id'))));
		$dream_owner=$this->DreamOwner->find('all',array('conditions'=>array('DreamOwner.status'=>Configure::read('App.Status.active'),'DreamOwner.project_id'=>$id)));
		
		$jobdirection=$this->JobDirection->find('list',array('conditions'=>array('JobDirection.status'=>Configure::read('App.Status.active'))));
		//pr($jobdirection);
		$project_manager_availabilities=$this->Availability->find('list',array('conditions'=>array('Availability.status'=>Configure::read('App.Status.active'))));
		
		$this->set(compact('project_manager_availabilities','dream_owner','id','jobdirection','userdetail'));		
	}
	
	public function add_dream_owner()
	{
		if($this->RequestHandler->isAjax()){
			$this->loadModel('DreamOwner');	
			$this->loadModel('JobDirection');
			$this->DreamOwner->bindModel(array('belongsTo'=>array('JobDirection')),false);	
			
			if(!empty($this->request->data)){
				$id = $this->request->data['DreamOwner']['project_id'];
				$this->DreamOwner->set($this->request->data);
				$this->DreamOwner->setValidation('add_dream_owner_valid');
				//die;
				if($this->DreamOwner->validates())
				{	 
					$this->request->data['DreamOwner']['status']=Configure::read('App.Status.active');
					$id=$this->request->data['DreamOwner']['project_id'];
					if($this->DreamOwner->saveAll($this->request->data)){
						echo "success";
						$dream_owner=$this->DreamOwner->find('all',array('conditions'=>array('DreamOwner.status'=>Configure::read('App.Status.active'),'DreamOwner.project_id'=>$id)));
						
						$jobdirection=$this->JobDirection->find('list',array('conditions'=>array('JobDirection.status'=>Configure::read('App.Status.active'))));
						$this->set(compact('dream_owner', 'jobdirection'));		
						$this->render('/Elements/Front/ele_dream_owner');
					}
				}else{
					$jobdirection=$this->JobDirection->find('list',array('conditions'=>array('JobDirection.status'=>Configure::read('App.Status.active'))));
					$this->set(compact('jobdirection','id'));
					
					$this->render('/Elements/Front/ele_add_dream_owner_statement');
				}	
				
			}else{
				$this->request->data = $this->DreamOwner->read(null,$this->params['pass'][0]);
				
                $sendArray['content'] = $this->request->data;
                echo json_encode($sendArray);
			}
		}
	
	}
	
	public function delete_dream_owner_statement($id)
	{
			$this->layout = false;
			$this->autoRender		=	false;
			$this->loadModel('DreamOwner');
			
			if(!empty($id)){
				$this->DreamOwner->id = $id;	
				$this->DreamOwner->delete();				
			}
	}
	
	
	public function project_status_timeline($id = NULL)
	{
		$this->set('title_for_layout','Project Status & Timeline');
		$this->layout = 'lay_my_project_job';
		$this->loadModel('ProjectStatus');
		$this->loadModel('IdeaMaturity');
		$this->loadModel('ProjectMilestone');
		
		if(!empty($this->request->data))
		{
			//pr($this->request->data);die;
			$this->Project->set($this->request->data);
			$this->Project->setValidation('project_general');
			if($this->Project->validates())
			{
				if(!empty($this->request->data['Project']['id']) && $this->request->data['Project']['id']!=NULL)
				{
					if($this->Project->saveAll($this->request->data))
					{	
						$this->Session->setFlash(__('Project status and timeline has been posted successfully.'),'default',array("class"=>"success"));
					
						$this->redirect(array('controller'=>'projects','action'=>'project_business_stuff',$this->request->data['Project']['id']));
					}
					else
					{
						$this->Session->setFlash(__('The Project could not be saved.  Please, correct errors.'),'default',array("class"=>"error"));
					}
				}
				else
				{
					$this->Session->setFlash(__('UnAuthorized Process.'),'default',array("class"=>"error"));
				}
			}
			else
			{
				$this->Session->setFlash(__('The Project could not be saved.  Please, correct errors.'),'default',array("class"=>"error"));
				
			}
		}else
		{
			$this->request->data = $this->Project->read(null,$id);
		}
		
		$projectmilestones=$this->ProjectMilestone->find('all',array('conditions'=>array('ProjectMilestone.project_id'=>$id)));
		$projectStatus=$this->ProjectStatus->find('list',array('fields'=>array('id','name'),'conditions'=>array('ProjectStatus.status'=>Configure::read('App.Status.active'))));
		$ideaMaturity=$this->IdeaMaturity->find('list',array('fields'=>array('id','name'),'conditions'=>array('IdeaMaturity.status'=>Configure::read('App.Status.active'))));
		$this->set(compact('projectStatus','ideaMaturity','projectmilestones','proj_id','id'));
	}
	
	public function add_project_milestone()
	{
		if($this->RequestHandler->isAjax()){
			$this->loadModel('ProjectMilestone');	
			
			if(!empty($this->request->data)){
				
				$this->ProjectMilestone->set($this->request->data);
				$this->ProjectMilestone->setValidation('add_project_milestone');
				//die;
				if($this->ProjectMilestone->validates())
				{	$id=$this->request->data['ProjectMilestone']['project_id'];								
					$this->ProjectMilestone->saveAll($this->request->data);
					echo '<script> window.location = SiteUrl+"/projects/project_status_timeline/'.$id.'"; </script>';
				}else{
								
					$this->render('/Elements/Front/ele_add_project_milestone');
				}	
				
			}else{
				$this->request->data = $this->ProjectMilestone->read(null,$this->params['pass'][0]);				
                $sendArray['content'] = $this->request->data;
                echo json_encode($sendArray);
			}
		}
	
	}
	public function delete_project_milestone($id)
	{
			$this->layout = false;
			$this->autoRender		=	false;
			$this->loadModel('ProjectMilestone');
			
			if(!empty($id)){
				$this->ProjectMilestone->id = $id;	
				$this->ProjectMilestone->delete();
				echo '<script> window.location = SiteUrl+"/projects/project_status_timeline"; </script>';				
			}
	}

	public function project_business_stuff($id = NULL)
	{
		$this->set('title_for_layout','Business Stuff');
		$this->layout = 'lay_my_project_job';
		$this->loadModel('BusinessPlanLevel');
		$this->loadModel('FileTemp');
		$this->loadModel('ProjectEstimation');
		$this->Project->bindModel(array('hasMany'=>array('ProjectBusinessplanFile')),false);
		
		if(!empty($this->request->data))
		{
			//pr($this->request->data);die;
			$this->Project->set($this->request->data);
			$this->Project->setValidation('project_general');
			if($this->Project->validates())
			{
					if($this->Project->saveAll($this->request->data))
					{	
						$id = $this->Project->id;
						if(empty($this->request->data['Project']['id']))
						{
							parent::__copy_directory(PROJECT_TEMP_THUMB_DIR_FILE,str_replace('{project_id}',$id,PROJECT_BUSINESS_PLAN_PATH_FOLDER));
						}	
						//unlink all temp folder image and file
						$oldfiles= $this->FileTemp->find("all",array("conditions"=>array("FileTemp.user_id"=>$this->Auth->User('id')),'fields'=>'FileTemp.project_file'));		
						foreach($oldfiles as $oldfile)
						{										
							unlink(PROJECT_TEMP_THUMB_DIR_FILE.$oldfile['FileTemp']['project_file']);
						}
						//delete project temp, image temp table data
						$this->FileTemp->deleteAll(array('FileTemp.user_id'=>$this->Session->read('Auth.User.id')));
						
						$this->Session->setFlash(__('The Project business stuff has been posted successfully.'),'default',array("class"=>"success"));						
						$this->redirect(array('controller'=>'projects','action'=>'my_project'));
					}	
			}
			else
			{
				$this->Session->setFlash(__('The Project could not be saved.  Please, correct errors.'),'default',array("class"=>"error"));			
			}
		}else
		{
			$this->request->data = $this->Project->read(null,$id);
			//pr($this->request->data);
		}
		
		$project_estimation=$this->ProjectEstimation->find('all',array('conditions'=>array('ProjectEstimation.project_id'=>$id)));
		
		$project_file=$this->FileTemp->find('all',array('fields'=>array('project_file','id'),'conditions'=>array('FileTemp.user_id'=>$this->Auth->User('id'))));
		$businessplanlevelData=$this->BusinessPlanLevel->find('list',array('fields'=>array('id','name'),'conditions'=>array('BusinessPlanLevel.status'=>Configure::read('App.Status.active'))));
		$this->set(compact('businessplanlevelData','project_file','project_estimation','id'));
	}
	
	public function add_project_estimation()
	{
		if($this->RequestHandler->isAjax()){
			$this->loadModel('ProjectEstimation');	
			
			if(!empty($this->request->data)){
				
				$this->ProjectEstimation->set($this->request->data);
				$this->ProjectEstimation->setValidation('add_project_estimation');
				//die;
				if($this->ProjectEstimation->validates())
				{	$id=$this->request->data['ProjectEstimation']['project_id'];									
					$this->ProjectEstimation->saveAll($this->request->data);
					echo '<script> window.location = SiteUrl+"/projects/project_business_stuff/'.$id.'"; </script>';
				}else{
								
					$this->render('/Elements/Front/ele_add_project_estimationnet_value');
				}	
				
			}else{
				$this->request->data = $this->ProjectMilestone->read(null,$this->params['pass'][0]);				
                $sendArray['content'] = $this->request->data;
                echo json_encode($sendArray);
			}
		}
	
	}
	
	
	public function delete_project_estimation($id)
	{
			$this->layout = false;
			$this->autoRender		=	false;
			$this->loadModel('ProjectEstimation');
			
			if(!empty($id)){
				$this->ProjectEstimation->id = $id;	
				$this->ProjectEstimation->delete();
				echo '<script> window.location = SiteUrl+"/projects/project_business_stuff"; </script>';				
			}
	}
	
	public function my_project()
	{	
		
		$this->layout = 'lay_my_account';
		$this->set('title_for_layout',"My Projects");
		$number_of_record = Configure::read('App.AdminPageLimit');
		$user_id = parent::__get_session_user_id();
		$this->Project->Behaviors->attach('Containable');
		
		$this->User->bindModel(array('hasAndBelongsToMany'=>array('Skill'),'hasOne'=>array('UserDetail')),false);		
		$this->Project->bindModel(array('belongsTo'=>array('User')),false);
		
		$filters = array();
		$filters[] = array('Project.user_id'=>$user_id);
		
		
		$this->paginate = array(
									'limit'=>$number_of_record, 
									'order'=>array('Project.id'=>'Desc'),
									'conditions'=>$filters,
									'contain'=>array('User'=>array('UserDetail'))
									);
	
		$data = $this->paginate('Project'); 
		$this->set('data',$data);
		
		
		
	}
	
	public function search_project()
	{
	
		$this->layout = 'lay_search_project';
		$this->set('title_for_layout','Search Project');
		$conditions = array();
		$countries=array();
		$states=array();
		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('FrontSearch');
			$this->Session->delete('Url');
		}
		$orderby = array('Project.id'=>'DESC');
		$this->loadModel('Category');
		$this->loadModel('Agreement');
		$this->loadModel('Compensation');
		$this->loadModel('BusinessPlanLevel');
		$this->loadModel('UserDetail');
		
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
					'State'
				)
		),false);
		$this->UserDetail->bindModel(array(
			'belongsTo'=>array('Country')
		),false);
		
		$this->User->bindModel(array('hasOne'=>array('UserDetail')
		),false);
		
		$this->Project->Behaviors->attach('Containable');
		if(!empty($this->request->data))
		{
			//pr($this->request->data);
			$this->Session->delete('FrontSearch');
			$this->Session->delete('Url');
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data['Project']['region_id'])){
				$region_id = Sanitize::escape($this->request->data['Project']['region_id']);
				$this->Session->write('FrontSearch.region_id', $region_id);
			}
			
			if(!empty($this->request->data['Project']['country_id'])){
				$country_id = Sanitize::escape($this->request->data['Project']['country_id']);
				$this->Session->write('FrontSearch.country_id', $country_id);
			}
			if(!empty($this->request->data['Project']['state_id'])){
				$state_id = Sanitize::escape($this->request->data['Project']['state_id']);
				$this->Session->write('FrontSearch.state_id', $state_id);
			}
			if(!empty($this->request->data['Project']['keyword'])){
				$search_keyword = Sanitize::escape($this->request->data['Project']['keyword']);
				$this->Session->write('FrontSearch.search_keyword', $search_keyword);
			}
			if(!empty($this->request->data['Project']['name'])){
				$title = Sanitize::escape($this->request->data['Project']['name']);
				$this->Session->write('FrontSearch.title', $title);
			}
			if(!empty($this->request->data['Project']['hour_days'])){
				$hour_days = Sanitize::escape($this->request->data['Project']['hour_days']);
				$this->Session->write('FrontSearch.hour_days', $hour_days);
			}
			if(!empty($this->request->data['Project']['category_id'])){
				$category_id = $this->request->data['Project']['category_id'];
				$this->Session->write('FrontSearch.category_id', $category_id);
			}
			if(!empty($this->request->data['Project']['sub_category_id'])){
				$sub_category_id = $this->request->data['Project']['sub_category_id'];
				$this->Session->write('FrontSearch.sub_category_id', $sub_category_id);
			}
			if(!empty($this->request->data['Project']['business_plan_level_id'])){
				$business_plan_level_id = $this->request->data['Project']['business_plan_level_id'];
				$this->Session->write('FrontSearch.business_plan_level_id', $business_plan_level_id);
			}
			if(!empty($this->request->data['Project']['sortby']) && $this->request->data['Project']['sortby'] =='project_name'){
				
				$orderby = array('Project.title'=>'ASC');
			}
			if(!empty($this->request->data['Project']['sortby']) && $this->request->data['Project']['sortby'] =='posting_date'){
				$orderby = array('Project.created'=>'ASC');
			}
			
			
			if($this->Session->check('FrontSearch')){
			
				$keywords  = $this->Session->read('FrontSearch');
			
				foreach($keywords as $key=>$values){
					if($key == 'title'){
						
						$conditions[] = array('Project.title LIKE'=>"%".$values."%");
					}
					else if($key == 'search_keyword'){
						
						$conditions[] = array('OR'=>array('Project.title LIKE'=>"%".$values."%" ,'Project.description LIKE'=>"%".$values."%" ));
					}
					elseif($key == 'hour_days')
					{
						if($values == 24)
						{
							$date1 = parent::get_ago_date(-1);
							$conditions[] = array('DATE(Project.created) <='=>$date1);
						}
						elseif($values == 3)
						{
							$date1 = parent::get_ago_date(-3);
							$conditions[] = array('DATE(Project.created) <='=>$date1);

						}
						elseif($values == 7)
						{
							$date1 = parent::get_ago_date(-7);
							$conditions[] = array('DATE(Project.created) <='=>$date1);
						}
						
					}
					else{
						$conditions[] = array('Project.'.$key =>$values);
						
					}

				}
				
			}
		}
		
		$conditions[] = array('Project.status'=>Configure::read('App.Status.active')); 
	
		
		$this->paginate = 	array(
								'conditions'=>$conditions,
								'limit'=>Configure::read('App.PageLimit'),
								'order'=>$orderby,
								'contain'=>array(
									'User'=>array(
										'UserDetail'=>array(
										'fields'=>array('UserDetail.name_visibility', 'UserDetail.country_id'),
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
		
		//pr($data);die;
		$project_categories = $this->Category->get_categories_front(Configure::read('App.Category.Project'));
		$regions = $this->Region->getResionListForUserRegistraion();
		$project_agreement_types=$this->Agreement->get_agreement_list();
		$compensations = $this->Compensation->get_compensation_front();
		$business_plans = $this->BusinessPlanLevel->get_business_plan_front();
		
		if(!empty($this->request->data['Project']['region_id']))
		{
			$countries = $this->Country->getCountryListByRegionId($this->request->data['Project']['region_id']);
		}
		if(!empty($this->request->data['Project']['country_id']))
		{
			$states = $this->State->getStateList($this->request->data['Project']['country_id']);
		}
		$this->set(compact('data','project_categories','regions','countries','states','project_agreement_types','compensations','business_plans'));
		if($this->request->is('ajax'))
		{
			$this->layout = false;
			$this->render('/Elements/Front/ele_search_project_right_sidebar');
		}
	}
	
	
}