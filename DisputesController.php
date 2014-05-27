<?php
/**
 * Disputes Controller
 *
 * PHP version 5.4
 *
 */
class DisputesController extends AppController {
	/**
	 * Controller name
	 *
	 * @var string
	 * @access public
	 */
	var	$name	=	'Disputes';

	public $helpers = array('General','Html');
	var $model='Dispute';
	var $controller='disputes';
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('display');
		$this->set('controller',$this->controller);
		$this->set('model',$this->model);

	}
	/*
	 * List all Blogs in admin panel
	 */
	public function admin_index($defaultTab='All') {
		$model = $this->model;
		if(!isset($this->request->params['named'][$model])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}
		$this->set(compact('search_flag','defaultTab'));

		$this->$model->bindModel(
		array(
				'belongsTo'=>array(
					'Provider'=>array(
						'className'=>'User',
						'foreignKey'=>'to_user_id'
						),
					'Buyer'=>array(
						'className'=>'User',
						'foreignKey'=>'from_user_id'
						),
					'Project'=>array(
						'className'=>'Project',
						'foreignKey'=>'project_id'
						),
					'Job'=>array(
						'className'=>'Job',
						'foreignKey'=>'job_id'
						)
						)
						),false
						);

						$this->paginate = array(
						$model=>array(
				'limit'=>Configure::read('App.AdminPageLimit'), 
				'order'=>array("$model.id"=>'DESC'),
				'recursive'=>1
						)
						);

						$data = $this->paginate($model);

						//pr($data);die;
						$this->set(compact('data'));
						$this->set('title_for_layout',  __('Disputes', true));

						if(isset($this->request->params['named'][$model]))
						$this->Session->write("Url.$model", $this->request->params['named'][$model]);
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


	public function admin_user_dispute_list($type=null,$id=null) {
		$defaultTab='All';
		$model = $this->model;
		if(!isset($this->request->params['named'][$model])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}
		$this->set(compact('search_flag','defaultTab'));
		$this->$model->bindModel(
		array(
				'belongsTo'=>array(
					'Provider'=>array(
						'className'=>'User',
						'foreignKey'=>'to_user_id'
						),
					'Buyer'=>array(
						'className'=>'User',
						'foreignKey'=>'from_user_id'
						),
					'Project'=>array(
						'className'=>'Project',
						'foreignKey'=>'project_id'
						),
					'Job'=>array(
						'className'=>'Job',
						'foreignKey'=>'job_id'
						)
						)
						),false
						);
						$filter = array('OR' => array(
            "$model.to_user_id" => $id,
            "$model.from_user_id" => $id,
						));

						$this->paginate = array(
						$model=>array(
				'limit'=>Configure::read('App.AdminPageLimit'), 
				'order'=>array("$model.id"=>'DESC'),
				'conditions'=>$filter,
				'recursive'=>1
						)
						);

						$data = $this->paginate($model);

						//pr($data);die;
						$this->set(compact('data','type'));
						$this->set('title_for_layout',  __('Disputes', true));

						if(isset($this->request->params['named'][$model]))
						$this->Session->write("Url.$model", $this->request->params['named'][$model]);
						if(isset($this->request->params['named']['sort']))
						$this->Session->write('Url.sort', $this->request->params['named']['sort']);
						if(isset($this->request->params['named']['direction']))
						$this->Session->write('Url.direction', $this->request->params['named']['direction']);
						$this->Session->write('Url.defaultTab', $defaultTab);

						if($this->request->is('ajax')){
							$this->render('ajax/admin_user_dispute_list');
						}else{
							$tabs = array('All'=>count($data));
							$this->set(compact('tabs'));
						}
	}



	/* public function admin_add() {

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
	if ($this->Blog->save($this->request->data)) {
	$this->Session->setFlash(__('The Blog has been saved successfully',true), 'admin_flash_success');
	$this->redirect(array('action' => 'index'));
	} else {
	$this->Session->setFlash(__('The Blog could not be saved. Please, try again.',true), 'admin_flash_error');
	}
	}
	else {
	$this->Session->setFlash(__('The Blog could not be saved. Please, correct errors.', true), 'admin_flash_error');
	}
	}
	}
	} */

	/**
	 * edit existing admin
	 */
	/* public function admin_edit($id = null) {

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
	//validate Blog data
	$this->Blog->set($this->request->data);
	$this->Blog->setValidation('admin');
	if ($this->Blog->validates()) {
	if ($this->Blog->save($this->request->data)) {
	$this->Session->setFlash(__('The Blog information has been updated successfully',true), 'admin_flash_success');
	$this->redirect(array('action' => 'index'));
	} else {
	$this->Session->setFlash(__('The Blog could not be saved. Please, try again.',true), 'admin_flash_error');
	}
	}
	else {
	$this->Session->setFlash(__('The Blog could not be saved. Please, correct errors.', true), 'admin_flash_error');
	}
	}
	}
	else {
	$this->request->data = $this->Blog->read(null, $id);
	}
	} */

	/* function referer($default = NULL, $local = false)
	 {
		$defaultTab = $this->Session->read('Url.defaultTab');
		$Dispute = $this->Session->read('Url.Dispute');
		$sort = $this->Session->read('Url.sort');
		$direction = $this->Session->read('Url.direction');

		return Router::url(array('action'=>'index', $defaultTab,'Dispute'=>$Dispute,'sort'=>$sort,'direction'=>$direction),true);
		} */



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
			$this->redirect($this->referer());
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

	public function admin_view($id=null) {
		$defaultTab='All';
		$model = $this->model;
		if(!isset($this->request->params['named'][$model])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}
		$this->set(compact('search_flag','defaultTab'));
		$this->$model->bindModel(
		array(
				'belongsTo'=>array(
					'Provider'=>array(
						'className'=>'User',
						'foreignKey'=>'to_user_id'
						),
					'Buyer'=>array(
						'className'=>'User',
						'foreignKey'=>'from_user_id'
						),
					'Project'=>array(
						'className'=>'Project',
						'foreignKey'=>'project_id'
						),
					'Job'=>array(
						'className'=>'Job',
						'foreignKey'=>'job_id'
						)
						)
						),false
						);

						$data = $this->$model->find('first',array('conditions'=>array("$model.id"=>$id)));
						$this->set(compact('data'));
						$this->set('title_for_layout',  __('Disputes', true));

						if(isset($this->request->params['named'][$model]))
						$this->Session->write("Url.$model", $this->request->params['named'][$model]);
						if(isset($this->request->params['named']['sort']))
						$this->Session->write('Url.sort', $this->request->params['named']['sort']);
						if(isset($this->request->params['named']['direction']))
						$this->Session->write('Url.direction', $this->request->params['named']['direction']);
						$this->Session->write('Url.defaultTab', $defaultTab);

						if($this->request->is('ajax')){
							$this->render('ajax/admin_user_dispute_list');
						}else{
							$tabs = array('All'=>count($data));
							$this->set(compact('tabs'));
						}
	}

	/* public function admin_comment_delete($id = null, $blog_id = null)
	 {
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
		} */

	/* public function admin_comment_status($id = null, $blog_id = null) {
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
		} */

	/* public function admin_comment_process(){
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
		} */
}