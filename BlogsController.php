<?php
/**
 * Blogs Controller
 * PHP version 5.4
 */
class BlogsController extends AppController {
	/**
	 * Controller name
	 * @var string
	 * @access public
	 */
	var	$name	=	'Blogs';
	public $helpers = array('General','Html');
	var $model='Blog';
	var $controller='blogs';
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('display','listing','detail','comment','blog_feed');
		$this->set('controller',$this->controller);
		$this->set('model',$this->model);
		$this->__mostPopularPost();

	}
	
	function __mostPopularPost(){
		$this->loadModel('Comment');
		$this->loadModel('Blog');
		$this->Comment->bindModel(array('belongsTo'=>array('Blog'=>array('fields'=>array('Blog.id','Blog.title','Blog.post_img')))),false);
		$popular_post=$this->Comment->find('all',array(
											'conditions'=>array('Comment.status'=>1),
											'order'=>array('Comment.blog_id'=>'DESC'),
											//'fields'=>array('Comment.id','Comment.blog_id','Comment.user_id'),
											'group' => array('Comment.blog_id'),
											'limit'=>4
											));
		
		
		//pr($popular_post1);
		$this->set(compact('popular_post'));
		//pr($popular_post);
	
	}
	/*
	 * List all Blogs in admin panel
	 */
	public function admin_index($defaultTab='All') {
		if(!isset($this->request->params['named']['Blog'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}

		$filters_without_status = $filters = array();
		if($defaultTab!='All'){

			$filters[] = array('Blog.status'=>array_search($defaultTab, Configure::read('Status')));
		}
		if(!empty($this->request->data)){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data['Blog']['title'])){
				$title = Sanitize::escape($this->request->data['Blog']['title']);
				$this->Session->write('AdminSearch.title', $title);
			}
				
			if(isset($this->request->data['Blog']['status']) && $this->request->data['Blog']['status']!=''){
				$status = Sanitize::escape($this->request->data['Blog']['status']);
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
					$filters[] = array('Blog.'.$key =>$values);
						
				}
				else{
				 $filters[] = array('Blog.'.$key.' LIKE'=>"%".$values."%");
				 $filters_without_status = array('Blog.'.$key.' LIKE'=>"%".$values."%");
				}
			}
				
			$search_flag=1;
		}

		$this->set(compact('search_flag','defaultTab'));

		$this->paginate = array(
			'Blog'=>array(	
				'limit'=>Configure::read('App.AdminPageLimit'), 
				'order'=>array('Blog.title'=>'ASC'),
				'conditions'=>$filters,
				'recursive'=>1
		)
		);

		$data = $this->paginate('Blog');


		$this->set(compact('data'));
		$this->set('title_for_layout',  __('Blogs', true));

		if(isset($this->request->params['named']['Blog']))
		$this->Session->write('Url.Blog', $this->request->params['named']['Blog']);
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
				$temp[] = array('Blog.status'=>Configure::read('App.Status.active'));
				$active = $this->Blog->find('count',array('conditions'=>$temp));
			}
				
			if($search_status=='' || $search_status==Configure::read('App.Status.inactive')){
				$temp=$filters_without_status;
				$temp[] = array('Blog.status'=>Configure::read('App.Status.inactive'));
				$inactive = $this->Blog->find('count',array('conditions'=>$temp));
			}

			$tabs = array('All'=>$active+$inactive, 'Active'=>$active,'Inactive'=>$inactive);
			$this->set(compact('tabs'));
		}
	}
	public function admin_add() {
		if ($this->request->is('post') || $this->request->is('put')) {
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				//validate Blog data
				$this->Blog->set($this->request->data);
				$this->Blog->setValidation('admin');
				if ($this->Blog->validates()) {
					if(!empty($this->request->data['Blog']['post_img']['tmp_name'])){
						$post_image = $this->request->data['Blog']['post_img'];
						unset($this->request->data['Blog']['post_img']);
					}else{
						$post_image = '';
					}
					if(!empty($post_image)){									
							$file_name	=	time();
							parent::__uploadFile($post_image,BLOG_IMAGE_BIG_PATH,"big_".$file_name,BLOG_IMAGE_WIDTH_BIG,BLOG_IMAGE_HEIGHT_BIG);
							parent::__uploadFile($post_image,BLOG_IMAGE_THUMB_PATH,"thumb_".$file_name,BLOG_IMAGE_WIDTH_THUMB,BLOG_IMAGE_HEIGHT_THUMB);
							parent::__uploadFile($post_image,BLOG_IMAGE_SMALL_PATH,"small_".$file_name,BLOG_IMAGE_WIDTH_SMALL,BLOG_IMAGE_HEIGHT_SMALL);
							$filename = parent::__upload($post_image,BLOG_IMAGE_ORIGINAL_PATH,$file_name);
					}
					$this->request->data['Blog']['post_img']=$filename;
					if ($this->Blog->save($this->request->data)){
						$this->Session->setFlash(__('The Blog has been saved successfully',true), 'admin_flash_success');
						$this->redirect(array('action' => 'index'));
					}else{
						$this->Session->setFlash(__('The Blog could not be saved. Please, try again.',true), 'admin_flash_error');
					}
				}else{
					$this->Session->setFlash(__('The Blog could not be saved. Please, correct errors.', true), 'admin_flash_error');
				}
			}
		}
		$this->loadModel('Category');
		$categories=$this->Category->find('list',array('conditions'=>array(
														'Category.status'=>Configure::read('App.Status.active'),
														'Category.parent_id'=>0
														
														),'fields'=>array('id','name')));
		$this->set(compact('categories'));
	}
	/**
	 * edit existing admin
	 */
	public function admin_edit($id = null) {
		$this->Blog->id = $id;
		if (!$this->Blog->exists()) {
			throw new NotFoundException(__('Invalid Blog'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				$this->Blog->set($this->request->data);
				$this->Blog->setValidation('admin');
				if(!empty($this->request->data["Blog"]['post_img']['tmp_name'])){
					
					if(file_exists(BLOG_IMAGE_SMALL_PATH.'small_'.$this->request->data['Blog']['blog_fileold'])){ 
						unlink(BLOG_IMAGE_SMALL_PATH.'small_'.$this->request->data['Blog']['blog_fileold']);
					}
					if(file_exists(BLOG_IMAGE_BIG_PATH.'big_'.$this->request->data['Blog']['blog_fileold'])){ 
						unlink(BLOG_IMAGE_BIG_PATH.'big_'.$this->request->data['Blog']['blog_fileold']);
					}
					if(file_exists(BLOG_IMAGE_THUMB_PATH.'thumb_'.$this->request->data['Blog']['blog_fileold'])){ 
						unlink(BLOG_IMAGE_THUMB_PATH.'thumb_'.$this->request->data['Blog']['blog_fileold']);
					}
					if(file_exists(BLOG_IMAGE_ORIGINAL_PATH.$this->request->data['Blog']['blog_fileold'])){ 
						unlink(BLOG_IMAGE_ORIGINAL_PATH.$this->request->data['Blog']['blog_fileold']);
					}
					$post_image=$this->request->data['Blog']['post_img'];
					$file_name	=	time();
					parent::__uploadFile($post_image,BLOG_IMAGE_BIG_PATH,"big_".$file_name,BLOG_IMAGE_WIDTH_BIG,BLOG_IMAGE_HEIGHT_BIG);
					parent::__uploadFile($post_image,BLOG_IMAGE_THUMB_PATH,"thumb_".$file_name,BLOG_IMAGE_WIDTH_THUMB,BLOG_IMAGE_HEIGHT_THUMB);
					parent::__uploadFile($post_image,BLOG_IMAGE_SMALL_PATH,"small_".$file_name,BLOG_IMAGE_WIDTH_SMALL,BLOG_IMAGE_HEIGHT_SMALL);
					$image_name = parent::__upload($post_image,BLOG_IMAGE_ORIGINAL_PATH,$file_name);
					$this->request->data['Blog']['post_img'] =$image_name;	
				}else{
				
					$this->request->data["Blog"]['post_img'] =$this->request->data['Blog']['blog_fileold'];
				} 
				//pr($this->request->data);
				//die;
				
				if ($this->Blog->validates()) {
					if ($this->Blog->save($this->request->data)){
						$this->Session->setFlash(__('The Blog information has been updated successfully',true), 'admin_flash_success');
						$this->redirect(array('action' => 'index'));
					}else{
						$this->Session->setFlash(__('The Blog could not be saved. Please, try again.',true), 'admin_flash_error');
					}
				}else{
					$this->Session->setFlash(__('The Blog could not be saved. Please, correct errors.', true), 'admin_flash_error');
				}
			}
		}else{
			$this->request->data = $this->Blog->read(null, $id);
			$this->loadModel('Category');
			$categories=$this->Category->find('list',array('conditions'=>array(
														'Category.status'=>Configure::read('App.Status.active'),
														'Category.parent_id'=>0
														
														),'fields'=>array('id','name')));
			$this->set(compact('categories'));
		}
	}
	function referer($default = NULL, $local = false){
		$defaultTab = $this->Session->read('Url.defaultTab');
		$Blog = $this->Session->read('Url.Blog');
		$sort = $this->Session->read('Url.sort');
		$direction = $this->Session->read('Url.direction');

		return Router::url(array('action'=>'index', $defaultTab,'Blog'=>$Blog,'sort'=>$sort,'direction'=>$direction),true);
	}
	public function admin_delete($id = null) {
		$model=$this->model;
		$controller=$this->controller;
		$this->loadModel('Comment');
		$this->loadModel('Blog');
		
		$this->Blog->bindModel(array('hasMany'=>array('Comment'=>array('dependent'=>true))),false);
		$this->$model->id = $id;
		if (!$this->$model->exists()) {
			throw new NotFoundException(__('Invalid '.$model.''));
		}
		if (!isset($this->request->params['named']['token']) || ($this->request->params['named']['token'] != $this->request->params['_Token']['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback();
		}
		$image_name = $this->Blog->find('first',array('conditions'=>array('Blog.id'=>$id),'fields'=>array('Blog.post_img')));
		
		if ($this->$model->delete()) {
			$this->Session->setFlash(__(''.$model.' deleted successfully'), 'admin_flash_success');
			if(!empty($image_name)){
				$image_name_unlink = $image_name['Blog']['post_img'];
				if(file_exists(BLOG_IMAGE_SMALL_PATH.'small_'.$image_name_unlink)){ 
						unlink(BLOG_IMAGE_SMALL_PATH.'small_'.$image_name_unlink);
					}
					if(file_exists(BLOG_IMAGE_BIG_PATH.'big_'.$image_name_unlink)){ 
						unlink(BLOG_IMAGE_BIG_PATH.'big_'.$image_name_unlink);
					}
					if(file_exists(BLOG_IMAGE_THUMB_PATH.'thumb_'.$image_name_unlink)){ 
						unlink(BLOG_IMAGE_THUMB_PATH.'thumb_'.$image_name_unlink);
					}
					if(file_exists(BLOG_IMAGE_ORIGINAL_PATH.$image_name_unlink)){ 
						unlink(BLOG_IMAGE_ORIGINAL_PATH.$image_name_unlink);
					}
			}
			
			$this->redirect($this->referer());
		}
		$this->Session->setFlash(__(''.$model.' was not deleted', 'admin_flash_error'));
		$this->redirect($this->referer());
	}
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
	public function admin_view($blog_id=null,$defaultTab='All') {
		//$this->layout = "admin_blog_view";
		$this->loadModel('Comment');
		$this->loadModel('Blog');
		$model=$this->model;
		
		
		$controller=$this->controller;
		$modelc = 'Comment';
		$blog = $this->$model->read(null,$blog_id);
		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}
		$filters = $filters_without_status =  array();
		//echo $this->params->params['pass'][0]."ram"; 
		/* if(isset($this->params->params['pass'][0])){
			$blog_id = $this->params->params['pass'][0];
		} */
		if(isset($blog_id)){
			$filters_without_status = $filters = array($modelc.'.blog_id'=>$blog_id); 
		}
		if($defaultTab!='All'){
				$filters[] = array($modelc.'.status'=>array_search($defaultTab, Configure::read('Status')));
		}
		 
		//pr()
		if(!empty($this->request->data)){ 
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data[''.$modelc.'']['comment'])){
				$comment = Sanitize::escape($this->request->data[''.$modelc.'']['comment']);
				$this->Session->write('AdminSearch.comment', $comment);
			}
			if(isset($this->request->data[''.$modelc.'']['status']) && $this->request->data[''.$modelc.'']['status']!=''){
				$status = Sanitize::escape($this->request->data[''.$modelc.'']['status']);
				$this->Session->write('AdminSearch.status', $status);
				$defaultTab = Configure::read('Status.'.$status);
			}
					
		}
		$search_flag=0;	$search_status='';
		if($this->Session->check('AdminSearch')){
			$keywords  = $this->Session->read('AdminSearch');
			//pr($keywords);die('222222');
			foreach($keywords as $key=>$values){
				if($key == 'status'){
					$search_status=$values;
					$filters[] = array(''.$modelc.'.'.$key =>$values);
				}
				else{
					$filters[] = array(''.$modelc.'.'.$key.' LIKE'=>"%".$values."%");
					$filters_without_status[] = array(''.$modelc.'.'.$key.' LIKE'=>"%".$values."%");
				}
			}
			$search_flag=1;
		}
		//pr($filters);
		$this->set(compact('search_flag','defaultTab'));
		$this->$modelc->bindModel(
			array('belongsTo'=>
				array(
					'User'
				)
			),false
		);
		//pr($filters);
		$this->paginate = array(
			''.$modelc.''=>array(
				 'limit'=>Configure::read('App.AdminPageLimit'), 
				 'order'=>array(''.$modelc.'.id'=>'desc'),
				 'conditions'=>$filters,
				 'recursive'=>1
			)
		);
		$data = $this->paginate(''.$modelc.'');
		//pr($data);
		$this->set(compact('data','blog_id','blog','modelc'));
		$this->set('title_for_layout',  __(''.$model.'s', true));
		if(isset($this->request->params['named']['page']))
			$this->Session->write('Url.page', $this->request->params['named']['page']);
		if(isset($this->request->params['named']['sort']))
			$this->Session->write('Url.sort', $this->request->params['named']['sort']);
		if(isset($this->request->params['named']['direction']))
			$this->Session->write('Url.direction', $this->request->params['named']['direction']);
		
		$this->Session->write('Url.defaultTab', $defaultTab);
		if($this->request->is('ajax')){ 
			$this->render('comment_ajax/admin_index');
		}
		else{
			$active=0;$inactive=0;
			if($search_status=='' || $search_status==Configure::read('App.Status.active')){
				$temp=$filters_without_status;
				$temp[] = array(''.$modelc.'.status'=>Configure::read('App.Status.active'));
				$active = $this->$modelc->find('count',array('conditions'=>$temp));
			}
			if($search_status=='' || $search_status==Configure::read('App.Status.inactive')){
				$temp=$filters_without_status;
				$temp[] = array(''.$modelc.'.status'=>Configure::read('App.Status.inactive'));
				$inactive = $this->$modelc->find('count',array('conditions'=>$temp));
			}
			$tabs = array('All'=>$active+$inactive, 'Active'=>$active,'Inactive'=>$inactive);
			$this->set(compact('tabs','modelc'));
		}
	}
	
	public function admin_comment_delete($id = null, $blog_id = null){
		$this->loadModel('Comment');
		$model='Comment';
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
			$this->redirect(array('controller'=>'blogs','action'=>'view',$blog_id));
		}
		$this->Session->setFlash(__(''.$model.' was not deleted', 'admin_flash_error'));
		$this->redirect(array('controller'=>'blogs','action'=>'view',$blog_id));
	}
	
	public function admin_comment_status($id = null, $blog_id = null) {
		$this->loadModel('Comment');
		$model='Comment';
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
			$this->redirect(array('controller'=>'blogs','action'=>'view',$blog_id));
		}
		$this->Session->setFlash(__(''.$model.'\'s status was not changed', 'admin_flash_error'));
		$this->redirect(array('controller'=>'blogs','action'=>'view',$blog_id));
	}
	
	public function admin_comment_process(){
		$this->loadModel('Comment');
		$model='Comment';
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
			$blog_id = $this->request->data['Blog']['id'];;
			if (count($this->request->data) == 0 || $this->request->data[''.$model.''] == null) {
				$this->Session->setFlash('No items selected.', 'admin_flash_error');
				$this->redirect(array('controller'=>'blogs','action'=>'view',$blog_id));
			}
				
			if($action == "delete"){
				$this->$model->deleteAll(array(''.$model.'.id'=>$ids));
				$this->Session->setFlash(''.$model.' have been deleted successfully', 'admin_flash_success');
				$this->redirect(array('controller'=>'blogs','action'=>'view',$blog_id));
			}
			if($action == "activate"){
				$this->$model->updateAll(array(''.$model.'.status'=>Configure::read('App.Status.active')),array(''.$model.'.id'=>$ids));

				$this->Session->setFlash(''.$model.' have been activated successfully', 'admin_flash_success');
				$this->redirect(array('controller'=>'blogs','action'=>'view',$blog_id));
			}
				
			if($action == "deactivate"){
				$this->$model->updateAll(array(''.$model.'.status'=>Configure::read('App.Status.inactive')),array(''.$model.'.id'=>$ids));

				$this->Session->setFlash(''.$model.' have been deactivated successfully', 'admin_flash_success');
				$this->redirect(array('controller'=>'blogs','action'=>'view',$blog_id));
			}
		}
		else{
			$this->redirect(array('controller'=>'blogs','action'=>'view',$blog_id));
		}
	}
	public function blog_list(){
		$this->loadModel('Blog');
		$this->paginate = array(
			'Blog'=>array(	
				'limit'=>Configure::read('App.AdminPageLimit'), 
				'order'=>array('Blog.created'=>'ASC'),
				'conditions'=>array('Blog.status'=>Configure::read('App.Status.active'),
		)
		)
		);
		$blogLists= $this->paginate('Blog');
		$this->set(compact('blogLists'));

	}
	public function comment_post(){
		$this->loadModel('Comment');
		if ($this->request->is('post') || $this->request->is('put')) {
				
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				//validate Comment data
				$this->Comment->set($this->request->data);
				$this->Comment->setValidation('postcomment');
				if ($this->Comment->save($this->request->data, array('validate'=>'only'))){

					$this->request->data['Comment']['user_id']= $this->Session->read('Auth.User.id');
					if ($this->Comment->save($this->request->data)) {
						$this->Session->setFlash(__('The Comment has been posted successfully.',true), 'admin_flash_success');
						//$this->redirect(array('action' => 'index'));
					} else {
						$this->Session->setFlash(__('The Comment could not be saved. Please, try again.',true), 'admin_flash_error');
					}

				}
				else {
					$this->Session->setFlash(__('The Comment could not be saved. Please, correct errors.', true), 'admin_flash_error');
				}
			}
		}
	}
	public function blog_comment_list($id=null){
		$this->loadModel('Blog');
		$this->loadModel('Comment');
		$this->paginate = array(
			'Comment'=>array(	
				'limit'=>Configure::read('App.AdminPageLimit'), 
				'order'=>array('Comment.created'=>'ASC'),
				'conditions'=>array('Comment.status'=>Configure::read('App.Status.active'),'Comment.blog_id'=>$id)
		)
		);
		$commentLists= $this->paginate('Comment');
		//pr($commentLists);die;
		$this->set(compact('commentLists'));

	}
	/* 
		@blog post listing display 
	*/
	public function listing(){
		$this->layout = "lay_blog_post";
		$this->set('title_for_layout','Blog Listing');
		$paging = '';
		$filters = array(); 
		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('FrontSearch');
			$this->Session->delete('Url');
		}
		if(isset($this->request->data['Paging']['page_no']) &&  !empty($this->request->data['Paging']['page_no']))
		{
			$paging = $this->request->data['Paging']['page_no'];
		}
		
		if(!empty($this->request->data)){
		
			$this->Session->delete('FrontSearch');
			$this->Session->delete('Url');
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data['Blog']['category_id'])){
				$category_id = Sanitize::escape($this->request->data['Blog']['category_id']);
				$this->Session->write('FrontSearch.category_id', $category_id);
			}	
			
		}
		
		if($this->Session->check('FrontSearch')){
			
			$keywords  = $this->Session->read('FrontSearch');
			//pr($keywords);
		
		
			foreach($keywords as $key=>$values){
			
				if($key == 'category_id'){
					$filters[] = array('Blog.category_id'=>$values);
				}
			}
		}
		
		$filters[] = array('Blog.status'=>Configure::read('App.Status.active'));
		
		$this->Blog->bindModel(array(
								'belongsTo'=>array('Category'),
								'hasOne'=>array('Comment'=>array(
														'className'=>'Comment',
														'foreginKey'=>false,
														'conditions'=>array('Comment.blog_id = Blog.id'),
														'fields'=>array('count(Comment.id) as Total_Comment'),
													))
								),false);
		$this->paginate = array(
			'Blog'=>array(	
				'limit'=>Configure::read('App.PageLimit'),			
				'order'=>array('Blog.created'=>'ASC'),
				'conditions'=>$filters,
				'page'=>$paging,			
				'group'=>array('Blog.id')
			)
		);
		//pr($filters);
		$blogLists= $this->paginate('Blog');
		$this->set(compact('blogLists'));
		//pr($blogLists);
		
		if($this->request->is('ajax')){
			$this->layout =false;
			$this->render('/Elements/blog/ele_listing');
			return; 
		} 
		 
		$this->loadModel('Category');
		$categories=$this->Category->find('list',array(
											'conditions'=>array(
													'Category.status'=>Configure::read('App.Status.active'),
													'Category.parent_id'=>0
													),'fields'=>array('id','name')));
		$this->set(compact('categories'));
		
	}
	/* 
		@ Blog post detail infromation display 
		date : 18-april-13
	*/
	public function detail($id = null){
		$this->layout ="lay_blog_post";
		$this->set('title_for_layout','Blog Detail');
		$model=$this->model;
		$controller=$this->controller;
		$this->loadModel('Category');
		$this->loadModel("Comment");
		$this->loadModel("User");
		$this->loadModel("UserDetail");
		
		
		$this->User->bindModel(array('hasOne'=>array('UserDetail'=>array('fields'=>array('image')))),false);
		$this->$model->id = $id;
		if (!$this->$model->exists()) {
			throw new NotFoundException(__('Invalid '.$model.''));
		}
		
		/* if($this->request->is('ajax')){
			$this->layout =false;
			$this->render('/Elements/blog/ele_listing');
			return; 
		}
		 */
		if(is_numeric($id)){
			$this->Blog->bindModel(array('belongsTo'=>array('Category')),false);
			$blog_post=$this->$model->find('first',array(
											'conditions'=>array($model.'.id'=>$id),
											'fields'=>array('Blog.id','Blog.category_id','Blog.title','Blog.article','Blog.post_img','Blog.created','Blog.status','Category.id','Category.name')
											)
									);
			
			$this->set(compact('blog_post'));
		}else{
			$this->Session->setFlash(__('Blog id invalid',true), 'admin_flash_success');
			$this->redirect(array('controller'=>'blogs','action' => 'list'));
		}
		
		//category drop-down box 
		
		$categories=$this->Category->find('list',array('conditions'=>array(
													'Category.status'=>Configure::read('App.Status.active'),
													'Category.parent_id'=>0
													
													),'fields'=>array('id','name')));
		
		//Blog Comment listing  
		
		$this->Comment->bindModel(array('belongsTo'=>array('User'=>array('fields'=>array('id','username','first_name')))),false);
		
		$post_comment=$this->Comment->find('threaded',array('conditions'=>array('Comment.blog_id'=>$id,'Comment.status'=>Configure::read('App.Status.active')),'recursive'=>2));
		
		/* $comment_reply=$this->Comment->get_blog_parent_child();
		pr($comment_reply); */
		
		$this->set(compact('post_comment','categories','id'));
		
	
	}
	
	public function comment(){	
			
			if($this->request->is('ajax')){
			
				$this->layout =false;
				$this->loadModel('Comment');
				$this->loadModel("User");
				$this->loadModel("UserDetail");				
				$this->User->bindModel(array('hasOne'=>array('UserDetail'=>array('fields'=>array('image')))),false);
				App::uses('Sanitize', 'Utility');
				
				$this->request->data['Comment']['user_id']= $this->Session->read('Auth.User.id');
				$this->request->data['Comment']['comment']=Sanitize::html($this->request->data['Comment']['meta_description']);
				
				if ($this->Comment->save($this->request->data)) {				
				
					$this->Comment->bindModel(array('belongsTo'=>array('User'=>array('fields'=>array('id','username','first_name')))),false);
					$post_comment=$this->Comment->find('threaded',array('conditions'=>array('Comment.blog_id'=>$this->request->data['Comment']['blog_id']),'recursive'=>2));
					
					
					$this->set(compact('post_comment'));
					$this->render('/Elements/blog/post_comment');
					return; 
				}
			}
			
	}
	
	public function comment_reply(){
	
			
			if($this->request->is('ajax')){
				
				$this->layout =false;
				$this->loadModel('Comment');
				$this->User->bindModel(array('hasOne'=>array('UserDetail'=>array('fields'=>array('image')))),false);
				App::uses('Sanitize', 'Utility');
				$this->request->data['Comment']['user_id']= $this->Session->read('Auth.User.id');				
				$this->request->data['Comment']['comment']= Sanitize::html($this->request->data['Comment']['meta_description_reply']);
				
				
				
				if ($this->Comment->save($this->request->data)) {
				/* pr($this->request->data); */
					$this->Comment->bindModel(array('belongsTo'=>array('User'=>array('fields'=>array('id','username','first_name')))),false);
					
					$Reply_Comment=$this->Comment->find('all',array('conditions'=>array('Comment.parent_id'=>$this->request->data['Comment']['parent_id'],'Comment.status'=>Configure::read('App.Status.active')),'recursive'=>2));
					/*  pr($Reply_Comment);
					die;  */
					$this->set(compact('Reply_Comment'));
					$this->render('/Elements/blog/ele_reply');
					return;

				}
			}
			
	}
	
	public function blog_archive(){	
		//pr($this->request->data);
		$this->layout ="lay_blog_post";
		$this->set('title_for_layout','Blog Archive');
		$this->loadModel('Category');
		$paging = '';
		$filters = array(); 
		
		$this->Blog->bindModel(array('belongsTo'=>array('Category')),false);
		
		if(!isset($this->request->params['named']['page'])){
			$this->Session->delete('FrontSearch');
			$this->Session->delete('Url');
		}
		
		if(isset($this->request->data['Paging']['page_no']) &&  !empty($this->request->data['Paging']['page_no']))
		{
			$paging = $this->request->data['Paging']['page_no'];
		}
		
		$categories=$this->Category->find('list',array(
											'conditions'=>array(
													'Category.status'=>Configure::read('App.Status.active'),
													'Category.parent_id'=>0
													),'fields'=>array('id','name')));
		$this->set(compact('categories'));
		
		
		if(!empty($this->request->data)){
		
			$this->Session->delete('FrontSearch');
			$this->Session->delete('Url');
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data['Blog']['category_id'])){
				$category_id = Sanitize::escape($this->request->data['Blog']['category_id']);
				$this->Session->write('FrontSearch.category_id', $category_id);
			}	
			
		}
		
		if($this->Session->check('FrontSearch')){
			
			$keywords  = $this->Session->read('FrontSearch');
			
			foreach($keywords as $key=>$values){
			
				if($key == 'category_id'){
					$filters[] = array('Blog.category_id'=>$values);
				}
			}
		}
			
		$filters[] = array('Blog.status'=>Configure::read('App.Status.active'),'DATEDIFF(NOW(),Blog.created) >'=>2);
					//pr($filters);			
		$this->paginate = array(
						'Blog'=>array(	
							'limit'=>5,			
							'order'=>array('Blog.created'=>'ASC'),
							'conditions'=>$filters,
							'page'=>$paging,			
							'group'=>array('Blog.id')
						)
		);
		
		$blogLists= $this->paginate('Blog');
		//pr($blogLists);
		$this->set(compact('blogLists'));
		if($this->request->is('ajax')){
			$this->layout =false;
			$this->render('/Elements/blog/ele_blog_archive');
			return; 
		} 
	
	}
	
	public function blog_feed($id=null){
		$this->layout = false;		
		$this->set('title_for_layout','Blog Feed');
		$this->loadModel('Blog');
		$blog_feed_data=$this->Blog->find('all',array('conditions'=>array('Blog.id'=>$id)));			
		$this->set(compact('blog_feed_data'));	
	
	}
}