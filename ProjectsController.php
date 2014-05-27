<?php  

 /*
  * 
  * Project Controller    
    pashkovdenis@gmail.com   
  	2014:   
  	
  *
  */

	class ProjectsController extends AppController {
   	
	
			/*
			 * _______________________________
			 * 
			 * Attributes  
			 * 2014   @ 136558885
			 * pashkovdenis@gmail.com   	   
			 * _______________________________ 
			 * 
			 */ 

			var	$name			=	        'Projects';
			var	$uses			=	         array('Project','Region','Country','State');
			var $helpers		=            array('Html','General', 'Feedback');
			var $components 	= 	         array('General','Upload','RequestHandler');
			var $model			=	        'Project';
			var $controller	    =	        'projects';  

			
			/* 
			 * 
			 * ____________________________ 
			 *  
			 * 	BeforeFilter  
			 *  starts :  236665544     
			 *  2014@gmail.com   
			 *  ____________________________
			 * 
			 * 
			 */
		
					
			  public function beforeFilter() { 
		
			  			parent::beforeFilter();		
						$this->set('controller',$this->controller);
						$this->set('model',$this->model); 
					 	
			  }

		
			/*
			 * ___________________________
			 * 
			 * 	Index Admin     
			 * 	Pashkovdenis@gmail.com  
			 *  2014@69985544456665544
			 * 	2014@gmail.com   
			 * ____________________________
		 	
		 	 */	
		
		
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
		
    
    
	    
    /* 
     * _______________________
     * 
     * Referer  
     * pashkovdenis@gmail.com   
     * 2014
     * ______________________ 
     * 
     *    
     */
    
    
	public function referer($default = NULL, $local = false)
	{
		$defaultTab = $this->Session->read('Url.defaultTab');
		$Page = $this->Session->read('Url.Page');
		$sort = $this->Session->read('Url.sort');
		$direction = $this->Session->read('Url.direction');
		
		return Router::url(array('action'=>'index', $defaultTab,'Page'=>$Page,'sort'=>$sort,'direction'=>$direction),true);
	}
	
	
  
	
	
	/*
	 * ___________________
	 * 
	 * Admin  Delete  :   
	 * pashkovdenis@gmail.com  
	 * 2014
	 * ___________________ 
	 *   
	 */
	
	
	public function admin_delete($id = null) {
		$this->loadModel('Project');
		$this->loadModel('ProjectMilestone');
		$this->loadModel('ProjectFile');
		$this->loadModel('ProjectEstimation');
		$this->loadModel('ProjectBusinessplanFile');
		
		
		$model=$this->model;
		$controller=$this->controller; 
		$this->$model->bindModel(array('hasMany'=>array('ProjectMilestone'=>array('dependent'=>true),'ProjectFile'=>array('dependent'=>true),'ProjectEstimation'=>array('dependent'=>true),'ProjectBusinessplanFile'=>array('dependent'=>true))),false);
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
	 
    
    
    
    /*
     * 
     * ________________________
     * Admin  Process   
     * Some Actions   
     * pashkovdenis@gmail.com   
     * _________________________ 
     * 
     * 
     */ 
    
    
	 
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
		$this->$model->bindModel(array('belongsTo'=>array(
															'User'=>array(
																			'fields'=>array(
																				'User.id','User.username'
																			)
						
						
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
														),'hasOne'=>array('ProjectBusinessplanFile'=>array(
																'fields'=>array('ProjectBusinessplanFile.file_name')
																										)
															)
									),false
							);
		$this->$model->recursive=3;
		$data=$this->$model->read(null, $id); 
		$this->set(compact('data'));
		 
	}

 
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
						 
							
							parent::__uploadFile($project_image,str_replace('{project_id}',$id,PROJECT_IMAGE_BIG_PATH),"big_".$file_name,PROJECT_IMAGE_WIDTH_BIG,PROJECT_IMAGE_HEIGHT_BIG);
							
							parent::__uploadFile($project_image,str_replace('{project_id}',$id,PROJECT_IMAGE_THUMB_PATH),"thumb_".$file_name,PROJECT_IMAGE_WIDTH_THUMB,PROJECT_IMAGE_HEIGHT_THUMB);
							
							parent::__uploadFile($project_image,str_replace('{project_id}',$id,PROJECT_IMAGE_SMALL_PATH),"small_".$file_name,PROJECT_IMAGE_WIDTH_SMALL,PROJECT_IMAGE_HEIGHT_SMALL);
							
							$filename = parent::__upload($project_image,str_replace('{project_id}',$id,PROJECT_IMAGE_ORIGINAL_PATH),$file_name);
							
							
							$this->Project->saveField('project_image',$filename);
						 
							
						}
						
						if(!empty($file_array))
						{									
							$file_name	=	time();
						 
							$filename	=	parent::__upload($file_array,str_replace('{project_id}',$id,PROJECT_PLAN_PATH),$file_name);
							
							$this->Project->saveField('business_plan_doc',$filename);
							 
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
	
		$this->layout = false;
        $this->loadModel('ProjectBusinessplanFile');            
        $data_file=$this->ProjectBusinessplanFile->find('first',array('conditions'=>array('ProjectBusinessplanFile.project_id'=>$project_id)));
		$fullPath = str_replace('{project_id}',$project_id,PROJECT_BUSINESS_PLAN_PATH_FOLDER).$data_file['ProjectBusinessplanFile']['file_name'];
		
		if ($fd = fopen ($fullPath, "r")) 
		{
			$fsize = filesize($fullPath);
			$path_parts = pathinfo($fullPath);
			$ext = strtolower($path_parts["extension"]);
			//$fl =  $fullPath;
            header('Content-Description: File Transfer');			
			header("Content-type: application/force-download");			
            header('Content-Disposition: attachment; filename='.basename($fullPath));			
			header('Content-Transfer-Encoding: Binary');
			header('Content-Type: application/octet-stream');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' .$fsize);
            //ob_clean();
            flush();
            readfile($fullPath);
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
                unlink(PROJECT_TEMP_THUMB_DIR_232_232.'thumb_'.$oldpic["ImageTemp"]['project_image']);
				unlink(PROJECT_TEMP_BIG_DIR.'big_'.$oldpic["ImageTemp"]['project_image']);
				unlink(PROJECT_TEMP_SMAll_DIR.'small_'.$oldpic["ImageTemp"]['project_image']);
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
	
	 
    
    /*
     * ____________________________
     * Upload Project file Here   
     * pashkovdenis@gmail.com  
     * 2014  
     * ______________________________  
      
     
     * 
     */
	
	
	public function project_fileupload(){ 
		$this->loadModel('FileTemp');
		
        if(isset($this->request->data["FileTemp"]['project_file']['tmp_name'])){
            
			if(!empty($this->request->data["FileTemp"]['project_file']['tmp_name']))
			{				
 
				$file_array	=	$this->request->data["FileTemp"]['project_file'];
				$this->request->data["FileTemp"]['project_file']= $this->request->data["FileTemp"]['project_file']['name'];
 			}
			
 			
 			// Test Double   
 			$test =  substr_count( $this->request->data["FileTemp"]['project_file'], ".") ; 
 			 if ($test>1){
 			 	// Try to rename   it  : 
 			 	$fine_name  =  explode(".", $this->request->data["FileTemp"]['project_file']);  
 			 	$this->request->data["FileTemp"]['project_file'] = $fine_name[0].".".$fine_name[1] ; 
 			  	
 			 }
 			
 			
			if(!empty($file_array))
			{	
				$file_name	=	 $this->request->data["FileTemp"]['project_file'] ;
				/* this is being used to upload user big size profile image*/
			
				$filename	=	parent::__upload($file_array,PROJECT_TEMP_THUMB_DIR_FILE,$file_name);
			}	
           
        }
        
    $name = ( $this->request->data["FileTemp"]["project_file"]);
        // test PPTX:  
		$this->request->data['FileTemp']['user_id']= $this->Auth->User('id');
		$this->request->data['FileTemp']['project_file']=$filename;
		$this->request->data["FileTemp"]['file_name']=  $name ; 
		
		
		
		
		if(!empty($this->request->data)){
			$avataruploaded = $this->FileTemp->saveAll($this->request->data);
			$lastInsert_id=$this->FileTemp->id;
			$created = date('F j, Y, g:i a',strtotime($this->FileTemp->field('created')));
			if($avataruploaded){
				echo "success|".$name."|".$lastInsert_id."|".$created;
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
	
	
	/*
	 * Download O
	 */
	public function download_project_file($id)
	{
	
		$this->layout = false;
        $this->loadModel('FileTemp');    
        $this->autoRender = false;  
                
        $data_file=$this->FileTemp->find('first',array('conditions'=>array('FileTemp.id'=>$id)));
		$fullPath = 'img/'.PROJECT_TEMP_THUMB_DIR_FILE_VIEW.$data_file['FileTemp']['project_file'];
		$filenae =   str_replace(" ", "_", $data_file['FileTemp']['file_name']); 
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
			header("Content-type: application/force-download");			
            header('Content-Disposition: attachment; filename='.basename($fullPath));			
			header('Content-Transfer-Encoding: Binary');
			header('Content-Type: application/octet-stream');
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
	 
	
	
	/*
	 * New Project Create Her:  
	 * pashkovdenis@gmail.com  
	 * 2014  :   
	 *  
	 *  
	 */
	
	public function project_general($id=null)
	{	
	    $this->loadModel("User")  ;   
	    $user_model  =  new User()  ;    
	    
	    if ($id != null){
	    	$col_edit   =false  ; 	
	    }else{
	    	$col_edit  = true ; 
	    } 
	    
	    $this->set("col_edit" ,$col_edit) ;
	    
	    
	    $user_local =  $user_model->find("first", array("conditions"=>array("id"=>$this->Auth->user("id")))); 
	   
		 if ( $user_local["User"]["role_id" ] == 4){
		 	$this->Session->setFlash(__('Please register as "Both" to be able to post a project as Leader'),'default',array("class"=>"error"));
		 	$this->redirect("/") ;
		 	
		 }
		
		  
		 
		
		$this->set('title_for_layout','Project General');
		$this->layout = 'lay_my_project_job';
		$this->loadModel('Category');
		$this->loadModel('ProjectType');
		$this->loadModel('ProjectVisibility');
		$this->loadModel('ImageTemp');
		$this->loadModel('FileTemp');
		$this->loadModel('DreamOwner');
		$this->loadModel('ProjectFile');
		$this->loadModel("Workroom");
		$this->Project->bindModel(array('hasMany'=>array('ProjectFile')),false);	
		$Totdata=0;	 
		
	   
		$this->Set("project_id", $id);
		
		 
		// Flush Project  
		if  (!empty($_GET["new"])){
			$this->Session->delete("project_business_stuff");
			$this->Session->delete("project_status_timeline");
			$this->Session->delete("created_project"); 
			$this->Project->query("DELETE FROM image_temps "); 
		}
		
		if  ($id != null){
			 
			$this->Session->write("project_business_stuff" ,'allow');
			$this->Session->write("project_status_timeline", 'allow'); 
		}
		
		 
		 if ($this->Session->read("created_project")!=""  && $id==null) 
		 $id  = $this->Session->read("created_project") ;  
		 
		 
			if(!empty($this->request->data))
			{
				if(isset($_POST["freelance"])){
					$_SESSION["freelace"] = $_POST["freelance"] ;
				}
				$this->Project->set($this->request->data);
				$this->Project->setValidation('project_general');
				
				
				// Start Validations  :    
				
				if($this->Project->validates())
				{
					
					$this->request->data['Project']['user_id']=$this->Auth->User('id');
				 
					
					// Only  For   new  Project :  
					if  (!empty($_GET["status"])){
					$this->request->data['Project']['visibility']  		=		0 ; 
					$this->request->data['Project']['status'] 		    =		0 ;  
					}
					
					
					
					if($this->Project->saveAll($this->request->data))
					{	
						
						$id = $this->Project->id; 
						
						
					 
						 $this->Session->write("created_project", $id);

			
						
						$activationKey = mt_rand(1000000,10000000).$id;
						$this->Project->updateAll(array('Project.project_identification_no'=>$activationKey),array('Project.id'=>$id));	
										
						@parent::__copy_directory(PROJECT_PLAN_PATH_DEFAULT,PROJECT_PLAN_PATH_FOLDER.$id);
						
						if(empty($this->request->data['ProjectFile']['project_file'])){	
							 
							@parent::__copy_directory(PROJECT_TEMP_THUMB_DIR_FILE,str_replace('{project_id}',$id,PROJECT_PLAN_PATH));	
							
							
							 
							$oldfiles= $this->FileTemp->find("all",array("conditions"=>array("FileTemp.user_id"=>$this->Auth->User('id')),'fields'=>'FileTemp.project_file'));
							if(isset($oldfiles) && !empty($oldfiles))
							{
								foreach($oldfiles as $oldfile)
								{										
									unlink(PROJECT_TEMP_THUMB_DIR_FILE.$oldfile['FileTemp']['project_file']);
								}
							}
							
							$this->FileTemp->deleteAll(array('FileTemp.user_id'=>$this->Session->read('Auth.User.id')));
							
						}
						
						if(empty($this->request->data['Project']['id']) && !empty($this->request->data['ImageTemp']['project_image'])){	
							
				 
							@parent::__copy_directory(PROJECT_TEMP_ORIGINAL_DIR,PROJECT_PLAN_PATH_FOLDER.$id.'/image/Original/');
							@parent::__copy_directory(PROJECT_TEMP_BIG_DIR,PROJECT_PLAN_PATH_FOLDER.$id.'/image/Big/');
							@parent::__copy_directory(PROJECT_TEMP_THUMB_DIR_232_232,PROJECT_PLAN_PATH_FOLDER.$id.'/image/Thumb/');
							@parent::__copy_directory(PROJECT_TEMP_SMAll_DIR,PROJECT_PLAN_PATH_FOLDER.$id.'/image/Small/');
							
							
							$oldpic= $this->ImageTemp->find("first",array("conditions"=>array("ImageTemp.user_id"=>$this->Auth->User('id')),'fields'=>'ImageTemp.project_image'));
							if(isset($oldpic) && !empty($oldpic['ImageTemp']['project_image']))
							{					
								unlink(PROJECT_TEMP_ORIGINAL_DIR.$oldpic["ImageTemp"]['project_image']);
								unlink(PROJECT_TEMP_BIG_DIR."big_".$oldpic["ImageTemp"]['project_image']);
								unlink(PROJECT_TEMP_THUMB_DIR_232_232."thumb_".$oldpic["ImageTemp"]['project_image']);
								unlink(PROJECT_TEMP_SMAll_DIR."small_".$oldpic["ImageTemp"]['project_image']);
							}
						 
							$this->ImageTemp->deleteAll(array('ImageTemp.user_id'=>$this->Session->read('Auth.User.id')));
						
						}
						
						
						if(!empty($this->request->data['Project']['id']) && !empty($this->request->data['ImageTemp']['project_image']))
						{
							$id=$this->request->data['Project']['id'];
							//copy file from project image to project image folder
							@parent::__copy_directory(PROJECT_TEMP_ORIGINAL_DIR,PROJECT_PLAN_PATH_FOLDER.$id.'/image/Original/');
							@parent::__copy_directory(PROJECT_TEMP_BIG_DIR,PROJECT_PLAN_PATH_FOLDER.$id.'/image/Big/');
							@parent::__copy_directory(PROJECT_TEMP_THUMB_DIR_232_232,PROJECT_PLAN_PATH_FOLDER.$id.'/image/Thumb/');
							@parent::__copy_directory(PROJECT_TEMP_SMAll_DIR,PROJECT_PLAN_PATH_FOLDER.$id.'/image/Small/');
							
							$oldpic= $this->ImageTemp->find("first",array("conditions"=>array("ImageTemp.user_id"=>$this->Auth->User('id')),'fields'=>'ImageTemp.project_image'));
							if(isset($oldpic) && !empty($oldpic['ImageTemp']['project_image']))
							{					
								unlink(PROJECT_TEMP_ORIGINAL_DIR.$oldpic["ImageTemp"]['project_image']);
								unlink(PROJECT_TEMP_BIG_DIR."big_".$oldpic["ImageTemp"]['project_image']);
								unlink(PROJECT_TEMP_THUMB_DIR_232_232."thumb_".$oldpic["ImageTemp"]['project_image']);
								unlink(PROJECT_TEMP_SMAll_DIR."small_".$oldpic["ImageTemp"]['project_image']);
							}
							$this->ImageTemp->deleteAll(array('ImageTemp.user_id'=>$this->Session->read('Auth.User.id')));
							
							$project_pic= $this->Project->find("first",array("conditions"=>array("Project.id"=>$this->request->data['Project']['id']),'fields'=>'Project.project_image'));
							if(isset($project_pic) && !empty($project_pic['Project']['project_image']))
							{					
								unlink(PROJECT_PLAN_PATH_FOLDER.$id.'/image/Original/'.$project_pic["Project"]['project_image']);
								unlink(PROJECT_PLAN_PATH_FOLDER.$id.'/image/Big/'."big_".$project_pic["Project"]['project_image']);
								unlink(PROJECT_PLAN_PATH_FOLDER.$id.'/image/Thumb/'."thumb_".$project_pic["Project"]['project_image']);
								unlink(PROJECT_PLAN_PATH_FOLDER.$id.'/image/Small/'."small_".$project_pic["Project"]['project_image']);
							}
							
							
						}
						if(!empty($this->request->data['ImageTemp']['project_image'])){
							
							$this->Project->id = $id;
							$this->Project->savefield('project_image',$this->request->data['ImageTemp']['project_image']);
						}
						
						$Totdata=$this->DreamOwner->find('count',array('conditions'=>array('DreamOwner.project_id'=>$id)));
						
						
						/*
						 * Update User  Dream Owner  Here  
						 * 2014  
						 */
					 
						if($Totdata<=0){
						
							$this->request->data['DreamOwner']['project_id']=$id;
							$this->request->data['DreamOwner']['name']=$this->Auth->User('username'); 
							$this->request->data["DreamOwner"]["full_name"] =    $this->Auth->User("first_name"). " ".  $this->Auth->User("last_name") ;
							$this->request->data['DreamOwner']['ownership_percentage']='100';
							$this->request->data['DreamOwner']['job_direction_id']='1';
							$this->request->data['DreamOwner']['dilution_id']='1';
							$this->request->data['DreamOwner']['status']='1';
							$this->request->data['DreamOwner']['role']= 0; 
							
							$this->DreamOwner->saveAll($this->request->data['DreamOwner']);
						}
					  
						 	$workroom = new Workroom() ;  
						
						 
							if ($workroom->createIfnotFromProject($id , $this->Auth->User('id') ,$this->request->data['Project']['title']))  
								$res =  "Workroom has been created "; 
							else 
								$res =  "Workroom Exists"; 
						  
							if (!$this->Session->read("created_project")!="")
							{
								$this->Session->setFlash(__('Project general has been posted successfully.' .$res),'default',array("class"=>"success"));
							} 
							  
							
							// Workroom  :  2014@pashkovdenis.com 
							if ($col_edit)
							$workroom->query(" INSERT INTO clb_projects SET  project='{$id}' ,  clb='{$_POST["collaboration"]}'  ,  freelance='{$_POST["freelance"]}'  ")  ;  
							else{ 
								// else  Just Update  
								$workroom->query("UPDATE clb_projects SET freelance='{$_POST["freelance"]}'  WHERE  project='{$id}'  ");
								
							} 
							
							
							unset($_SESSION["freelace"] );
							
							$this->Session->write("project_status_timeline", "allow"); // allow   Next  Step  ; 
							$this->redirect(array('controller'=>'projects','action'=>'project_status_timeline',$id));
					}
					else
					{
						
						$this->Session->setFlash(__('Project general is not posted successfully.'),'default',array("class"=>"error"));
					}
				}
				else
				{	
						
			 
					$project_img =$this->Project->find('first',array('fields'=>array('project_image','id'),'conditions'=>array('Project.id'=>$id)));
					$projectfile=$this->ProjectFile->find('all',array('fields'=>array('project_file','id','project_id'),'conditions'=>array('ProjectFile.project_id'=>$id)));
					
					$this->set(compact('project_img','projectfile'));
					$this->Session->setFlash(__('Please correct the error listed.'),'default',array("class"=>"error"));
				}
			}
			else{
	
 
				if(!empty($id))
				{
			 
			 $project_img =$this->Project->find('first',array('fields'=>array('project_image','id'),'conditions'=>array('Project.id'=>$id)));
					
					$projectfile=$this->ProjectFile->find('all',array('fields'=>array('project_file','id','project_id','created'),'conditions'=>array('ProjectFile.project_id'=>$id)));
					
					$this->set(compact('project_img','projectfile'));					
					$this->request->data = $this->Project->read(null,$id);
					
					
				}
				
		 
				 
				
			}
		 
			
			
			/*
			 * Load Project Informations :   
			 * pashkovdenis@gmail.com  
			 * 2014   
			 * 
			 * 
			 */  
			
			
			 
		if ($id)	
	   $project_image=$this->ImageTemp->find('first',array('fields'=>array('project_image','id'),'conditions'=>array('ImageTemp.user_id'=>$this->Auth->User('id'))));
	 
	   	$project_file=$this->FileTemp->find('all',array('fields'=>array('project_file','id','created', 'file_name'),'conditions'=>array('FileTemp.user_id'=>$this->Auth->User('id'))));
		
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
	








	public function update_dream_owner($id)
	{
	
		$this->loadModel('DreamOwner');
		$this->loadModel('JobDirection');
		$this->DreamOwner->bindModel(array('belongsTo'=>array('JobDirection')),false);
		$dream_owner=$this->DreamOwner->find('all',array('conditions'=>array('DreamOwner.status'=>Configure::read('App.Status.active'),'DreamOwner.project_id'=>$id)));
	
		$jobdirection=$this->JobDirection->find('list',array('conditions'=>array('JobDirection.status'=>Configure::read('App.Status.active'))));
		$this->set('id',$id);
		$this->set('dream_owner',$dream_owner);
		$this->render('/Elements/Front/ele_dream_owner');
	
	}
	
	
	public function add_dream_owner()
	{
	
		if($this->RequestHandler->isAjax()){
			$this->loadModel('DreamOwner');
			$this->loadModel('JobDirection');
			$test_flag = '';
				
			if(!empty($this->request->data)){
				$id=$this->request->data['DreamOwner']['project_id'];
				$this->DreamOwner->set($this->request->data);
				$this->DreamOwner->setValidation('add_dream_owner_valid');
				$jobdirection=$this->JobDirection->find('list',array('conditions'=>array('JobDirection.status'=>Configure::read('App.Status.active'))));
				if($this->DreamOwner->validates())
				{
					$this->request->data['DreamOwner']['status']=Configure::read('App.Status.active');
					$id=$this->request->data['DreamOwner']['project_id'];
					if($this->DreamOwner->saveAll($this->request->data)){
	
						$test_flag = 'success';
	
					}
				}else{
						
					$test_flag = 'validation';
						
						
				}
				$this->set(compact('id','jobdirection','test_flag'));
				$this->render('/Elements/Front/ele_add_dream_owner_statement');
	
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
			die;
		}
	}
	
	
	
	
	
	
	
	 /**
	  * Project Leader Page THat need To be removed from  Stack   
	  * @param string $id 
	  * 2013  
	  */
	
	
	
	
	// Some Data At The   Project Leader Stack   :    
	
	
	public function project_leader($id = NULL)
	{	
		$this->layout = 'lay_my_project_job';
		$this->set('title_for_layout','Project Leader');
		$this->loadModel('Availability');
		$this->loadModel('UserDetail');		
		$this->loadModel('DreamOwner');
		$this->loadModel('JobDirection');
		
		$test_flag = '';
		$validation = false;
		
		$this->DreamOwner->bindModel(array('belongsTo'=>array('JobDirection')),false);	
		$this->Project->bindModel(array('hasMany'=>array('DreamOwner')),false);
		
		$dream_owner=$this->DreamOwner->find('all',array('conditions'=>array('DreamOwner.status'=>Configure::read('App.Status.active'),'DreamOwner.project_id'=>$id)));
		
		if(!empty($this->request->data))
		{
		 
			$this->Project->set($this->request->data);
			$this->Project->setValidation('project_leader');
			$this->Project->DreamOwner->setValidation('add_dream_owner_valid');
			
			if($this->Project->saveAll($this->request->data,array('validate'=>'only')))
			{
				if(!empty($this->request->data['Project']['id']) && $this->request->data['Project']['id']!=NULL)
				{
					if($this->Project->saveAll($this->request->data))
					{	 
						$this->Session->setFlash(__('Project leader has been posted successfully.'),'default',array("class"=>"success"));
						$this->redirect(array('controller'=>'projects','action'=>'project_status_timeline',$this->request->data['Project']['id']));
					}
					else
					{	$validation = true;
						$this->Session->setFlash(__('The Project could not be saved.Please correct errors.'),'default',array("class"=>"error"));
						
					}
				}
				else
				{	$validation = true;
				$this->redirect("/my-project") ;
					$this->Session->setFlash(__('UnAuthorized Process.'),'default',array("class"=>"error"));
					
				}
			}
			else
			{	
			  	$validation = true;
				$this->Session->setFlash(__('Please correct errors listed below.'),'default',array("class"=>"error"));
			}
		}
		else
		{	$validation = false;
			$this->request->data = $this->Project->read(null,$id);
			
		}
		
		$userdetail=$this->UserDetail->find('first',array('fields'=>array('linkdin_url','facebook_url','image'),'conditions'=>array('UserDetail.user_id'=>$this->Auth->User('id'))));
		$jobdirection=$this->JobDirection->find('list',array('conditions'=>array('JobDirection.status'=>Configure::read('App.Status.active'))));
		//pr($jobdirection);
		$project_manager_availabilities=$this->Availability->find('list',array('conditions'=>array('Availability.status'=>Configure::read('App.Status.active'))));
		
		$this->set(compact('project_manager_availabilities','dream_owner','id','jobdirection','userdetail','test_flag','validation'));		
	}
	
	
	
	
	
	
	
	
	
	/*
	 * Project status time line
	 * pashkovdenis@gmail.com
	 * 2014
	 * 
	 */
	
	public function project_status_timeline($id = NULL)
	{
		$this->set('title_for_layout','Project Status & Timeline');
		$this->layout = 'lay_my_project_job';
		
		$this->loadModel('Availability'); 
		$this->loadModel('ProjectStatus');
		$this->loadModel('IdeaMaturity');
		$this->loadModel('ProjectMilestone');
		$this->loadModel('ProjectEstimation');
		$this->loadModel('UserDetail');
		$this->loadModel('DreamOwner'); 
		
		
		$validation = false;
		
		if ($this->Session->read("created_project")!=""  && $id==null)
			$id  = $this->Session->read("created_project") ;
		
		
		$this->Project->bindModel(array('hasMany'=>array('ProjectMilestone')),false);
		$projectmilestones=$this->ProjectMilestone->find('all',array('conditions'=>array('ProjectMilestone.project_id'=>$id))); 
		
		
	
		
		if(!empty($this->request->data))
		{
			 
			$this->Project->set($this->request->data);
			$this->Project->setValidation('project_general');
			$this->Project->ProjectMilestone->setValidation('add_project_milestone');
			if($this->Project->saveAll($this->request->data,array('validate'=>'only')))
			{
				if(!empty($this->request->data['Project']['id']) && $this->request->data['Project']['id']!=NULL)
				{
					if($this->Project->saveAll($this->request->data))
					{	
						
						//check in database on edit time that this is already exist or not
						$Totdata=$this->ProjectEstimation->find('count',array('conditions'=>array('ProjectEstimation.project_id'=>$id)));
						
						/*
						if($Totdata<=0){
							$this->request->data['ProjectEstimation'][0]['project_id']=$id;
							$this->request->data['ProjectEstimation'][0]['timeline']=0;
							$this->request->data['ProjectEstimation'][0]['estimate_net_value']='';
							$this->request->data['ProjectEstimation'][0]['description']='';
							$this->request->data['ProjectEstimation'][1]['project_id']=$id;
							$this->request->data['ProjectEstimation'][1]['timeline']=5;
							$this->request->data['ProjectEstimation'][1]['estimate_net_value']='';
							$this->request->data['ProjectEstimation'][1]['description']='';
														
							$this->ProjectEstimation->saveAll($this->request->data['ProjectEstimation']);
						}*/ 
						
						
						
						$this->Session->setFlash(__('Project status and timeline has been posted successfully.'),'default',array("class"=>"success"));
					 	$this->Session->write("project_business_stuff", "allow") ;  // allow Next Steping   
						$this->redirect(array('controller'=>'projects','action'=>'project_business_stuff',$this->request->data['Project']['id']));
					}
					else
					{
						
						
						$validation = true;
						//$this->Session->setFlash(__('The Project could not be saved.  Please, correct errors.'),'default',array("class"=>"error"));
					}
				}
				else
				{
					$validation = true;
					$this->redirect("/my-project") ;
					$this->Session->setFlash(__('UnAuthorized Process.'),'default',array("class"=>"error"));
				}
			}
			else
			{
				
				
				/* pr($this->request->data);
				die; */  
				
				$validation = true;
				$this->Session->setFlash(__('The Project could not be saved.  Please, correct errors.'),'default',array("class"=>"error"));
				
			}
		}else
		{
			$this->request->data = $this->Project->read(null,$id);
			$validation = false;
			
			
		}

		$milestone_counter = count($projectmilestones); 
		$project_manager_availabilities=$this->Availability->find('list',array('conditions'=>array('Availability.status'=>Configure::read('App.Status.active'))));
		$this->set("project_manager_availabilities", $project_manager_availabilities); //  Set Avail
	 
		$this->set("project_id" , $id ) ; 
		  
		
		$projectStatus=$this->ProjectStatus->find('list',array('fields'=>array('id','name'),'conditions'=>array('ProjectStatus.status'=>Configure::read('App.Status.active'))));
		$ideaMaturity=$this->IdeaMaturity->find('list',array('fields'=>array('id','name'),'conditions'=>array('IdeaMaturity.status'=>Configure::read('App.Status.active'))));
		$this->set(compact('projectStatus','ideaMaturity','projectmilestones','proj_id','id','validation'));
	}
	
	public function add_project_milestone()
	{
		if($this->RequestHandler->isAjax()){
			$this->loadModel('ProjectMilestone');	
			$test_flag = '';
			if(!empty($this->request->data)){
				
				$this->ProjectMilestone->set($this->request->data);
				$this->ProjectMilestone->setValidation('add_project_milestone');
				$id=$this->request->data['ProjectMilestone']['project_id'];
				//die;
				if($this->ProjectMilestone->validates())
				{								
					if($this->ProjectMilestone->saveAll($this->request->data))
					{			
						$test_flag = 'success';
						
					}	
				}else{
						$test_flag = 'validation';			
					
				}
				$this->set(compact('test_flag','id'));
				$this->render('/Elements/Front/ele_add_project_milestone');	
				
				
			}else{
				$this->request->data = $this->ProjectMilestone->read(null,$this->params['pass'][0]);				
                $sendArray['content'] = $this->request->data;
                echo json_encode($sendArray);
			}
		}
		
	}
	
	public function update_project_milestone($id){
		
		if($this->RequestHandler->isAjax()){
			$this->loadModel('ProjectMilestone');
			$projectmilestones=$this->ProjectMilestone->find('all',array('conditions'=>array('ProjectMilestone.project_id'=>$id)));
			$this->set(compact('projectmilestones'));
			$this->render('/Elements/Front/ele_project_milestone_table');
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
				$this->Session->setFlash(__('Project milestone has been posted successfully.'),'default',array("class"=>"success"));
				die();
			}
	}

	
	
	
	
	
	
	
	
	
	
	
	/*
	 * Project Buisness   Tab  Woth Popup    
	 * Team4Dream Project   
	 * 2014   
	 * 
	 */
	
	
	public function project_business_stuff($id = NULL)
	{
		 
		$this->set('title_for_layout','Business Stuff');
		$this->layout = 'lay_my_project_job';
		$this->loadModel('BusinessPlanLevel');
		$this->loadModel('FileTemp');
		$this->loadModel('ProjectEstimation');
		$validation = false;
		if ($this->Session->read("created_project")!=""  && $id==null)
			$id  = $this->Session->read("created_project") ; 
		
		
		$this->Project->bindModel(array('hasMany'=>array('ProjectEstimation')),false);
		$this->Project->bindModel(array('hasMany'=>array('ProjectBusinessplanFile')),false);
		
		 $this->set("project_id",  $id);
		
		$this->loadModel('Availability');
		$this->loadModel('UserDetail');
		$this->loadModel('DreamOwner');
		$this->loadModel('JobDirection');
		$this->loadModel("Category") ;  
		
		
		
		$test_flag = '';
		$validation = false;
		
		$this->DreamOwner->bindModel(array('belongsTo'=>array('JobDirection')),false);
		 $this->Project->bindModel(array('hasMany'=>array('DreamOwner')),false);
		
		$this->Project->bindModel(array('belongsTo'=>array('Category')),false);
		
		
		
		$dream_owner=$this->DreamOwner->find('all',array('conditions'=>array('project_id'=>$id)));
		
		  
		$categories   =$this->Category->find("list",array("conditions"=>array("status"=>1,"type_for"=>2))) ; 
	 
		 
		$categories[0] =  "Leader";
 		$categories = array_reverse($categories) ; 
		$this->set("categories" , $categories); 
		
		
		
		
		$project_estimation=$this->ProjectEstimation->find('all',array('conditions'=>array('ProjectEstimation.project_id'=>$id)));
		if(!empty($this->request->data))
		{
			 
			$this->Project->set($this->request->data);
			$this->Project->setValidation('project_general');
			$this->Project->ProjectEstimation->setValidation('add_project_estimation');
		
			if($this->Project->saveAll($this->request->data,array('validate'=>'only')))
			{
					if($this->Project->saveAll($this->request->data))
					{	
						$id = $this->Project->id;
						if(empty($this->request->data['ProjectBusinessplanFile']['file_name'])){	
						 
							@parent::__copy_directory(PROJECT_TEMP_THUMB_DIR_FILE,str_replace('{project_id}',$id,PROJECT_BUSINESS_PLAN_PATH_FOLDER));						
							
							 
							$oldfiles= $this->FileTemp->find("all",array("conditions"=>array("FileTemp.user_id"=>$this->Auth->User('id')),'fields'=>'FileTemp.project_file'));		
							foreach($oldfiles as $oldfile)
							{										
								unlink(PROJECT_TEMP_THUMB_DIR_FILE.$oldfile['FileTemp']['project_file']);
							}
							
						 
							$this->FileTemp->deleteAll(array('FileTemp.user_id'=>$this->Session->read('Auth.User.id')));
							
						}

					 		$this->Session->delete("project_business_stuff"); 
							$this->Session->delete("project_status_timeline");
							$this->Session->delete("created_project");

							$project_model  = new Project() ;  
							$project_model->query("UPDATE projects SET  status=1  WHERE id='{$id}' ");



						 	$this->Session->setFlash(__('The Project business stuff has been posted successfully.'),'default',array("class"=>"success"));						
							$this->redirect(array('controller'=>'projects','action'=>'my_project'));
					}	
			}
			else
			{	
				$validation = true;
				$this->Session->setFlash(__('The Project could not be saved.  Please, correct errors.'),'default',array("class"=>"error"));			
			}
		}else
		{	
			$validation = false;
			$this->request->data = $this->Project->read(null,$id);
			//pr($this->request->data);
		}
		
		$this->set("dream_owner" ,$dream_owner);
		$jobdirection=$this->JobDirection->find('list',array('conditions'=>array('JobDirection.status'=>Configure::read('App.Status.active'))));
		  
		$project_manager_availabilities=$this->Availability->find('list',array('conditions'=>array('Availability.status'=>Configure::read('App.Status.active'))));
		$this->set("jobdirection", $jobdirection);  
		$this->set("project_manager_availabilities",  $project_manager_availabilities); 
		 
		
		
		$project_file=$this->FileTemp->find('all',array('fields'=>array('project_file','id','created', 'file_name'),'conditions'=>array('FileTemp.user_id'=>$this->Auth->User('id'))));
		$businessplanlevelData=$this->BusinessPlanLevel->find('list',array('fields'=>array('id','name'),'conditions'=>array('BusinessPlanLevel.status'=>Configure::read('App.Status.active'))));
		$this->set(compact('businessplanlevelData','project_file','project_estimation','id','validation'));
	}
	
	
	
	
	/*
	 * Some  Project Estimation  
	 * 2014 :  
	 */
	
	
	
	public function add_project_estimation()
	{
		if($this->RequestHandler->isAjax()){
			$this->loadModel('ProjectEstimation');	
			$test_flag = '';
			if(!empty($this->request->data)){
				
				$this->ProjectEstimation->set($this->request->data);
				$this->ProjectEstimation->setValidation('add_project_estimation');
				$id=$this->request->data['ProjectEstimation']['project_id'];
				//die;
				if($this->ProjectEstimation->validates())
				{										
					if($this->ProjectEstimation->saveAll($this->request->data)){
						
						$test_flag = 'success';
					}
				}else{
					$test_flag = 'validation';			
					
				}	
				$this->set(compact('test_flag','id'));
				$this->render('/Elements/Front/ele_add_project_estimationnet_value');
			}else{
				$this->request->data = $this->ProjectMilestone->read(null,$this->params['pass'][0]);				
                $sendArray['content'] = $this->request->data;
                echo json_encode($sendArray);
			}
		}
	
	}
	
	public function update_estimation_project_value($id){
		
		if($this->RequestHandler->isAjax()){
			$this->loadModel('ProjectEstimation');
			$project_estimation=$this->ProjectEstimation->find('all',array('conditions'=>array('ProjectEstimation.project_id'=>$id)));
			$this->set(compact('project_estimation'));
			$this->render('/Elements/Front/ele_project_estimation_net_value_table');
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
				$this->Session->setFlash(__('Project Estimation deleted successfully.'),'default',array("class"=>"error"));		
				die();
			}
	}
	
	
	
	/*
	  My Project Action   
	  pashkovdenis@gmail.com  
  	  Display List of  all  My Created Projects
  	  2014
  	  
  	  */
	 
	public function my_project()
	{	
		//echo session_id();
		$this->layout = 'lay_my_account';
		$this->set('title_for_layout',"My Projects");
		$this->loadModel('Job');
		$this->loadModel('JobBid'); 
		$this->loadModel("Workroom") ;  
		$workroom= new Workroom() ;  
		$paging = '';
		$number_of_record = Configure::read('App.PageLimit');
		$user_id = parent::__get_session_user_id();
		$this->Project->Behaviors->attach('Containable'); 
		
		if(isset($this->request->data['Paging']['page_no']) &&  !empty($this->request->data['Paging']['page_no']))
		  $paging = $this->request->data['Paging']['page_no'];

		$this->JobBid->bindModel(array('belongsTo'=>array('User')),false);	
		$this->Job->bindModel(array('hasMany'=>array('JobMilestone'=>array('fields'=>array('JobMilestone.date','JobMilestone.job_id','JobMilestone.id'),'order'=>array('JobMilestone.date'=>'ASC'))),'hasOne'=>array('JobBid'=>array('conditions'=>array(),'fields'=>array('JobBid.user_id','JobBid.id','JobBid.job_id')))),false);		
		$this->Project->bindModel(array('belongsTo'=>array('User'=>array('fields'=>array('User.id','User.first_name','User.last_name','User.username'))),'hasMany'=>array('Job'=>array('conditions'=>array('Job.status'=>array(Configure::read('App.Job.Active'),Configure::read('App.Job.Awarded'),Configure::read('App.Job.Completed'))),'fields'=>array('Job.id','Job.status','Job.title','Job.description')),'ProjectMilestone'=>array('fields'=>array('ProjectMilestone.project_id','ProjectMilestone.date'),'order'=>array('ProjectMilestone.date'=>'ASC')))),false);
		$filters = array(); 
		$rooms = array(); 
	  	$filters[] = array('Project.user_id'=>$user_id);
	  	//$filters[] = array('Project.status'=> 1); 
		$this->paginate = array(
									'limit'=>$number_of_record, 
									'order'=>array('Project.id'=>'Desc'),
									'conditions'=>$filters,
									'contain'=>array('User','Job'=>array('JobMilestone','JobBid'=>array('User')),'ProjectMilestone'),
									'page'=>$paging,
									);
	
		$data = $this->paginate('Project'); 
	 	$this->set("current_user", $this->Auth->user("id")) ; 
		$this->set('data',$data);
		
		if($this->request->is('ajax'))
		{
			$this->layout = false;
			$this->render('/Elements/Front/ele_myproject_right');
		}
	}
	
	
	
	/*
	 * Search Project   
	 * pashkovdenis@gmail.com   
	 * 2014    
	 * 
	 * 
	 */ 
	
	
	public function search_project()  
	{	
		 
		if(!$this->request->is('ajax')){
				$this->Session->delete("FrontSearchSkill")  ;
				$this->Session->delete("FrontSearchJobs")  ;
		
		
		}
	
	
		$this->layout = 'lay_search_project';
		$paging = '';
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
		$job_skills=$this->Category->get_job_skills();   
		$this->set("job_skills"  ,  $job_skills); 
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
					"Category" , 
					'State'
				)
		),false);
		$this->UserDetail->bindModel(array(
			'belongsTo'=>array('Country')
		),false);
		
		$this->User->bindModel(array('hasOne'=>array('UserDetail')
		),false);
		
		$this->Project->Behaviors->attach('Containable');
		
		if(isset($this->request->data['Paging']['page_no']) &&  !empty($this->request->data['Paging']['page_no']))
		{
			$paging = $this->request->data['Paging']['page_no'];
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
			
			// Add Filter By  Skill  
			if(!empty($this->request->data['Skill']['Skill'])){ 
				$this->Session->write  ( "FrontSearchSkill" , $this->request->data['Skill']['Skill']); 
				    				
			}
			  
			
			//  Added Filter  By   job   Category  
			if(!empty($this->request->data['Job']['Job'])){
				$this->Session->write  ( "FrontSearchJobs" , $this->request->data['Job']['Job']);
			
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
		 
		
		$total_ids  =   array() ;    
		
		
		//  Skills   :   
		if ($this->Session->read("FrontSearchSkill")){
			$skills = $this->Session->read("FrontSearchSkill");  
			$stack  =array_values($skills) ; 
			$ids =  Project::getSkills($stack) ;   
  			$total_ids = array_merge($total_ids ,  $ids)  ;  
  			
			if (count($total_ids)==0)  
				 $total_ids[] = 1 ;
			 	 
	 	} 
	 	 
	 
	 	
	 	
	 	 //  Front  SearchJobs  :   
	 	if ($this->Session->read("FrontSearchJobs")){   
			$skills = $this->Session->read("FrontSearchJobs");
	 		$stack  =array_values($skills) ;
	 	 	$ids = Project::getJobProject($stack) ;   
	 	 	$total_ids = array_merge($total_ids ,  $ids)  ;
	 	 	if (count($total_ids)==0)
	 	 		$total_ids[] = 1 ;
	 		
	 		
	 		
	 		
	 	}
		
	 	if(count($total_ids))  
	 	$conditions[] = array('Project.id'=> $total_ids);  
	  	$conditions[] = array('Project.status'			=>Configure::read('App.Status.active')); 
		$conditions[] = array('Project.visibility'		=> 1); 
		$conditions[] = array('Project.status'			=> 1);  
		 
		// Paginate :     
		$this->paginate = 	array(
								'conditions'=>$conditions,
								'page'=>$paging,
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
										'fields'=>array('User.first_name','User.last_name', 'User.username'),										
									),									
									'IdeaMaturity'=>array(
										'conditions'=>array('IdeaMaturity.status'=>Configure::read('App.Status.active')),
										'fields'=>array('IdeaMaturity.name')
									),
									 "Category"=>array( 
									 			"conditions"=>("Category.id =Project.category_id"),
												"fields"=>array("Category.name","Category.id")
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
										'fields' => array ('Country.name','Country.country_flag' ) 
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
		}else{
			$this->Session->delete("FrontSearchSkill")  ;
			
		}
	}
	
	 
	/*
	 * Public View Project 
	 * pashkovdenis@gmail.com  
	 * 2014 
	 * 
	 * 
	 * 
	 */ 
	
	public function public_view($id) {  
		
		$this->loadModel('Project');   
		$this->loadModel('Agreement');
		$this->loadModel('Compensation');
		$this->loadModel('Availability');
		$this->loadModel('JobsSkill');
		$this->loadModel('UserDetail');
		$this->loadModel("jobInvite") ;
		$this->loadModel ( 'JobBid' ); 
		$this->loadModel('UsersWorkingStatus');
		
	 	$this->layout = "project" ; 
		$this->loadModel("User");
		$u  =  $this->Project->find("first" , array("conditions"=>array("id"=>$id)));    
		$user_id =    $u["Project"]["user_id"];   
	 
		$files = $this->Project->query("SELECT * FROM  project_files WHERE project_id ='{$id}'  "); 
		
	
		  
		
		
 		if (empty($id) ||  $this->Auth->user("id") == "" ||  empty($user_id))  
	  	 	$this->redirect("/") ; 
 				$this->Project->bindModel(array(
 				'belongsTo'=>array(
 						'User',
 						'IdeaMaturity',
 						'ProjectStatus',
 						'ProjectType',
 						'Availability',
 						'BusinessPlanLevel',
 						'Region',
 						"Category" ,
 						'Country',
 						'State'
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
 	  					) 
 	  						
 	  			)
 	  	)
 	  			,false);
 	  	
 	  	
 	  	$this->Project->Behaviors->attach('Containable');
 	  	$this->paginate = 	array(
 	  			'conditions'=> array("Project.id"=>$id),
 	  			'page'=>1,
 	  			'limit'=>Configure::read('App.PageLimit'),
 	  			'order'=> " Project.id " ,
 	  			'contain'=>array(
 	  					'User'=>array(
 	  							'UserDetail'=>array(
 	  									'fields'=>array('UserDetail.name_visibility', 'UserDetail.country_id'),
 	  									'Country'=>array(
 	  											'conditions'=>array('Country.status'=>Configure::read('App.Status.active')),
 	  											'fields'=>array('Country.name','Country.country_flag')
 	  									),
										'ExpertiseCategory'=>array(
											'conditions'=>array('ExpertiseCategory.status'=>Configure::read('App.Status.active')),
											'fields'=>array('ExpertiseCategory.name')
										),
 	  							),
 	  							'fields'=>array('User.first_name','User.last_name', 'User.id' ,'User.username'),
 	  					),
 	  					'IdeaMaturity'=>array(
 	  							'conditions'=>array('IdeaMaturity.status'=>Configure::read('App.Status.active')),
 	  							'fields'=>array('IdeaMaturity.name')
 	  					),
 	  					"Category"=>array(
 	  							"conditions"=>("Category.id =Project.category_id"),
 	  							"fields"=>array("Category.name","Category.id")
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
 	  							'fields'=>array('Country.name','Country.country_flag')
 	  					),
 	  					'State'=>array(
 	  							'conditions'=>array('State.status'=>Configure::read('App.Status.active')),
 	  							'fields'=>array('State.name')
 	  					)
 	  			),
 	  			'fields'=>array('Project.id','Project.title','Project.project_image','Project.description','Project.project_description_visibility','Project.self_investment_option','Project.self_invest_money','Project.external_fund_option','Project.external_fund_money','Project.user_id')
 	  	
 	  	);
 	  	$data = $this->paginate('Project');
 	  	
 	  	// Select prject Files :
 	  		
 	  	if (!empty($files)){
 	  		$files_list =  array() ;
 	  		foreach($files as $r){
 	  			$files_list[] = $r["project_files"]["project_file"];
 	  		}
 	  	
 	  	 $this->Set("files" , $files_list); 
 	  	
 	  	}
 	  	 



 	  	
 	  	$this->set("data",  $data);
 	  	
 	  	$userPublicView=$this->User->find(
 	  			'first',
 	  			array(
 	  						
 	  					'group' => array('User.id'),
 	  					'conditions'=>array('User.id'=>$user_id),
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
 	  									) 
 	  										
 	  							),
 	  							'Skill',
 	  							'Project'=>array(
 	  										
 	  							),
 	  							'WorkingStatus'=>array(
 	  									'conditions'=>array('WorkingStatus.status'=>Configure::read('App.Status.active')),
 	  									'fields'=>array('WorkingStatus.name')
 	  							),
 	  							'UserPortfolio'=>array(
 	  									'fields'=>array('UserPortfolio.image','UserPortfolio.title','UserPortfolio.url')
 	  							)
 	  					)
 	  			)
 	  	);
		
		 
			 $this->loadModel('WorkingStatus');
			 $status_id = $this->User->query("SELECT working_status_id FROM users_working_statuses WHERE user_id = '{$userPublicView["User"]["id"]}' ");
			 
				
			if (isset($status_id[0]["users_working_statuses"])){
				$status =   $this->User->query("SELECT name FROM working_statuses WHERE id  = '{$status_id[0]["users_working_statuses"]["working_status_id"]}' ") ;
			$leaderData["status"] =  $status[0]["working_statuses"]["name"]  ; }else
				{
					$leaderData["status"] = "";
				}
				 
			
			$userPublicView["status"]=$leaderData["status"]	;
				
 	  	
 	  	// Select Users  Skills :
 	  	$skills =  $this->User->query("SELECT skill_id FROM skills_users WHERE user_id='{$userPublicView["User"]["id"]}'  ");
 	  	$this->loadModel("Skill") ;
 	  	$Skill_model  = new Skill() ;
 	  		
 	  	$skills_loaded = array();
 	 
 	   
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
 	  	

 	   $this->set(compact('userPublicView'));
		$k=0; //amit
        $avail  = $this->User->query("SELECT availability_id FROM  user_details WHERE user_id='{$userPublicView["User"]["id"]}' ");
        $model1 =  new Availability();
		$model =$model1	->find("first" , array("conditions"=>array("id"=>$avail[0]["user_details"]["availability_id"])));
        if (!empty($model["Availability"]["name"]))
            $data[$k]["avail"] =   $model["Availability"]["name"];
        else
            $data[$k]["avail"] = "N/A" ;







        $countries=array();
 	   $conditions = array();
 	   $orderby = array('Job.id'=>'DESC');
 	   $states=array();
 	   if(!isset($this->request->params['named']['page'])){
 	   	$this->Session->delete('FrontSearch');
 	   	$this->Session->delete('Url');
 	   } 
 	 
 	   $this->loadModel('Agreement');
 	   $this->loadModel('Compensation');
 	   $this->loadModel('Availability');
 	   $this->loadModel('JobsSkill');
 	   $this->loadModel('UserDetail'); 
      $this->loadModel("Job");
	  		$this->loadModel('Category');

 	   $this->User->bindModel(array('hasOne'=>array('UserDetail')),false);
 	   $this->UserDetail->bindModel(array('belongsTo'=>array('Country'=>array('conditions'=>array('Country.status'=>Configure::read('App.Status.active'))))),false);
 	   $this->Project->bindModel(array(
 	   		'belongsTo'=>array('User',
 	   				'Category'=>array(
 	   						'className'=>'Category',
 	   						'foreignKey'=>'category_id'
 	   				),
 	   				'ProjectChildCategory'=>array(
 	   						'className'=>'Category',
 	   						'foreignKey'=>'sub_category_id'
 	   				))),false);
 	   
 	   $this->Job->bindModel(array(
 	   		'belongsTo'=>array('Project',
 	   				'Category'=>array(
 	   						'className'=>'Category',
 	   						'foreignKey'=>'category_id'
 	   				),
 	   				'ChildCategory'=>array(
 	   						'className'=>'Category',
 	   						'foreignKey'=>'sub_category_id'
 	   				),
 	   				'Region',
 	   				'Country',
 	   				'State'
 	   		),
 	   		'hasAndBelongsToMany'=>array('Skill'))
 	   		,false);
 	   
 	  // $this->Job->Behaviors->attach('Containable');
 	    
 	   
 	   // Conditions    For Visibility :    
 	   //  End Visibility Conditions  :     
	   $this->Job->Behaviors->attach ( 'Containable' );
		$this->loadModel ( "Category" );
		$cat_model = new Category ();
		
		// Status Goes Here :
		$cats = $cat_model->find ( "all", array (
				"conditions" => array (
						"Category.type_for" => 2,
						"Category.status" => 1,
						"Category.parent_id" => 0 
				) 
		) );
		
		$res_cat = array();
		
		// Select Categories
		foreach ( $cats as $category ) {
			$res_cat [$category ["Category"] ["id"]] = $category ["Category"] ["name"];
		}
		
		$this->set ( "job_categories", $res_cat );
		
		
 	   
 	   $conditions[]=array('Job.status'=>1,  "Job.project_id"=>$id); 
 	   
 	 
 	   $this->paginate = array(
 	   		'limit'=>100,
 	   		'order'=> "Job.id",
 	   		'page'=>1,
 	   		'conditions'=>$conditions,
 	   		'contain'=>array(
 	   				'Project'=>array(
 	   						 'User'=>array(
 	   								'fields'=>array('User.first_name','User.last_name','User.id','User.username'),
 	   								'UserDetail'=>array(
 	   										'fields'=>array('UserDetail.country_id','UserDetail.id'),
 	   										'Country'=>array(
 	   												'conditions'=>array('Country.status'=>Configure::read('App.Status.active')),																		'fields'=>array('Country.name','Country.country_flag'),
													
 	   										)
 	   								)
 	   									
 	   						),
 	   							
 	   						'fields'=>array('Project.title','Project.id'),
 	   
 	   						'Category'=>array(
 	   								'conditions'=>array('Category.status'=>Configure::read('App.Status.active')),
 	   								'fields'=>array('Category.name')
 	   						),
 	   						'ProjectChildCategory'=>array(
 	   								'conditions'=>array('ProjectChildCategory.status'=>Configure::read('App.Status.active')),										'fields'=>array('ProjectChildCategory.name','ProjectChildCategory.id'))
 	   				),
 	   				'Category'=>array(
 	   						'conditions'=>array('Category.status'=>Configure::read('App.Status.active')),
 	   						'fields'=>array('Category.id','Category.parent_id','Category.name')
 	   				),
 	   					
 	   				'ChildCategory'=>array(
 	   						'conditions'=>array('ChildCategory.status'=>Configure::read('App.Status.active')),
 	   						'fields'=>array('ChildCategory.name','ChildCategory.id')
 	   				),
 	   					
 	   				'Skill'=>array('fields'=>array('Skill.id','Skill.name')),
 	   					
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
 	   						'fields'=>array('State.name'))
 	   		)
 	   );
 	   	$job_list = $this->paginate('Job'); 
		
		$this->loadModel ( "Duration" );
		$dur = new Duration ();
		// create Duration :
		$this->loadModel ( "Availability" );
		$av = new Availability ();
		
		// Preapre Lists of Jobs Here :
		
		foreach ( $job_list as $k => $j ) {
			$r = $dur->find ( "first", array (
					"conditions" => array (
							"id" => $job_list [$k] ["Job"] ["duration_id"] 
					) 
			) );
			$c = $this->Category->find ( "first", array( 
					"conditions" => array(
							"Category.id" => $job_list [$k] ["Job"] ["category_id"] 
					) 
			) );
			$job_list [$k] ["Job"] ["duration"] = $r ["Duration"] ["name"];
			$job_list [$k] ["Job"] ["cat_name"] = $c ["Category"] ["name"];
			$avail = $av->find ( "first", array( 
					"conditions" => array( 
							"id" => $job_list [$k] ["Job"] ["expert_availability_id"] 
					) 
			) );
			$job_list [$k] ["Job"] ["avail"] = $avail ["Availability"] ["name"];
		}
 	   	
 	   	
 	   //	$job_catgories = $this->Job->Category->get_categories_front ( Configure::read ( 'App.Category.Job' ) );
	
 	   	$this->loadModel("Duration");
 	   	$dur = new Duration() ;
 	   	// create Duration  :
 	   	foreach($job_list as $k=>$j){
 	   		$r = $dur->find("first", array("conditions"=>array("id"=>$job_list[$k]["Job"]["duration_id"])));
 	   	
 	   		$job_list[$k]["Job"]["duration"]  =  $r["Duration"]["name"];
 	   			
 	   			
 	   	}
 	   	
 	   	
 	   	
 	   	
 	   	$this->loadModel("JobBid") ; 
 	   	
 	   	$this->set("job_list",  $job_list);   
 		$users_ids  = array();  
 	 	$bids   = $this->JobBid->find("all" ,  array("conditions"=>  array("project_id"=>$id )));
		
 	   	foreach($bids as $b)  
 	   		$users_ids[] =   $b["JobBid"]["user_id"];   
  			$out  = array(0); 
  			
 	   	 foreach ($users_ids as $key=>$v)  
 	   	 	if (!in_array($v, $out))  
 	   	 		 $out[]  = $v;  
 	   	 
 	   	   
 	   	 	// Load  USer info  :    
 	   	 	
 	   	 	
 	   	 	
 	   	 	
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
 	   	 					) ,   
 	   	 					'JobBid' ,  
 	   	 					"Job" ,  
 	   	 					
 	   	 			),
 	   	 			'hasAndBelongsToMany'=>array(
 	   	 					'Skill',  
 	   	 				 
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
 	   	 					'State' , 
 	   	 					'JobBid'
 	   	 			)
 	   	 	)
 	   	 			,false);
 	   	 	
 	   	 	 
 	   	 	$this->User->Behaviors->attach('Containable');
 	   	 	
 	   	 
 	   	 	// Paginate  :   
 	   	 	$this->paginate = 	array(
 	   	 			'limit'=>Configure::read('App.PageLimit'),
 	   	 			'order'=> "User.id",
 	   	 			'page'=>1,
 	   	 			'recursive'=>1,
 	   	 			'conditions'=> array( "User.id"=>  $out)  ,
 	   	 			'contain'=>array(
 	   	 					'UserDetail'=>array(
 	   	 							'fields'=>array(
 	   	 									'UserDetail.user_id','UserDetail.region_id','UserDetail.country_id','UserDetail.state_id','UserDetail.leadership_category_id','UserDetail.city','UserDetail.min_reference_rate','UserDetail.max_reference_rate','UserDetail.about_us'
 	   	 									,'UserDetail.image'
 	   	 							),
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
 	   	 							),
									'ExpertiseCategory'=>array(
 	  											'conditions'=>array('ExpertiseCategory.status'=>Configure::read('App.Status.active')),
 	  											'fields'=>array('ExpertiseCategory.name')
 	  						),
 	   	 							
 	   	 	
 	   	 					),   
 	   	 					
 	   	 					 
 	   	 					
 	   	 					'Skill',
 	   	 					'Project'
 	   	 			),
 	   	 			'fields'=>array('User.*','count(Project.id) as totalProject'),
 	   	 			'group'=>array('User.id')
 	   	 	);
 	   	 		
 	   	 		$this->loadModel('WorkingStatus');
			 
				 
			
			//$userPublicView["status"]=$leaderData["status"]	;
 	   	
 	   	 	
 	   	 	$leaderData = $this->paginate('User');
			//print_r($leaderData);
			$leaderData_team=array();
			
			
			
 	   	 	foreach($leaderData as $k=>$v){
 	   	 		$leaderData[$k]["jobid"] = $bids[$k] ;
 	   	 		$jobname  =  new Job();
				$jobname1=$jobname  ->find("first", array("conditions"=>array("Job.id"=>$bids[$k]["JobBid"]["job_id"])));
 	   	 		$leaderData[$k]["job1"] = $jobname1;  
 	   	 		 
				// print_r($leaderData[$k]['User']['id']);
 	   	 		$status_id = $this->User->query("SELECT working_status_id FROM users_working_statuses WHERE user_id = {$leaderData[$k]["User"]["id"]} ");
			 
				
			if (isset($status_id[0]["users_working_statuses"])){
				$status =   $this->User->query("SELECT name FROM working_statuses WHERE id  = '{$status_id[0]["users_working_statuses"]["working_status_id"]}' ") ;
				 $leaderData_team["status"] =  $status[0]["working_statuses"]["name"]  ; }else
				{
					$leaderData_team["status"] = "";
				}
				//$leaderData["status"]=$leaderData["status"];
 	   	 		 
 	   	 	
			
 	   	 	
 	   	 	// $this->set("leaderData" ,   $leaderData_team);  
 	   	 	 
 	   	 	}
			
			
 	   	 	
 	   	 	 $this->set("leaderData" ,   $leaderData);  
 	   	 	 $this->set("leaderData_team" ,   $leaderData_team);  
 	   	 	 
 	 
 	   
		
	}
	
	
	
	
	
	
	/*
	 * Close Project Implementation   
	 * pashkovdenis@gmail.com  
	 * 2014   
	 * 
	 */
	 	
	public function closeproject($id){ 
		
		$this->loadModel("Project") ;  
		$this->loadModel("Job") ; 
		$this->loadModel("Workroom") ;  
		
		$this->autoRender = false;  
		$this->autoLayout = false ;  
		//  Change Project status   as   CLosed  
		//  Change Jobs status as Closed  ;  
		//  Close Workrooms   
		$project = $this->Project->find("first"  ,  array("conditions"=>array("id"=>$id , "user_id"=>$this->Auth->user("id")))); 
		// Project 
		if ($project){
			 $jobs =    $this->Job->find("all", array("conditions"=>array("project_id"=>$id)));  
			 $jobids =  array(1) ;  
			 foreach($jobs as $j){
			 		$this->Job->query("UPDATE jobs SET status =0  WHERE id='{$j["Job"]["id"]}' "); 
			 	    $jobids[] =   $j["Job"]["id"];
			 }   
			  $this->Project->query("UPDATE projects SET status = 0  WHERE id = '{$id}' "); 
			   //  Select Workroors :   
			   
			 $job_rooms =   $this->Workroom->find("all" , array("conditions"=>array("job_id"=>$jobids))); 
			   
			 
			 //  Job rooms :  
			 foreach($job_rooms as $j){
			   $this->Workroom->query("UPDATE workroom SET active=0  WHERE id ='{$j["Workroom"]["id"]}' "); 		 	
			 }
			  
			 
			 $this->Workroom->query("UPDATE workroom SET active=0  WHERE project_id ='{$id}' "); 

			 // End CLose   Project  
			 //  Redirect To back   
			 $this->Session->setFlash(__('The project has been closed.'),'default',array("class"=>"success"));   
			  $this->redirect($_SERVER["HTTP_REFERER"]) ; 
			  
			
			
		}
		  
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}