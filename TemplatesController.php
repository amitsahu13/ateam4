<?php
/**
 * Templates Controller
 *
 * PHP version 5.4
 *
 */
class TemplatesController extends AppController {
	/**
	 * Controller name
	 *
	 * @var string
	 * @access public
	 */
	var	$name	=	'Templates';
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('login');
	}
	/*
	 * List all Templates in admin panel
	 */
	public function admin_index($defaultTab='All') {
		if(!isset($this->request->params['named']['Template'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}

		$filters_without_status = $filters = array();


		if(!empty($this->request->data)){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
				
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data['Template']['title'])){
				$title = Sanitize::escape($this->request->data['Template']['title']);
				$this->Session->write('AdminSearch.title', $title);
			}
				
			if(isset($this->request->data['Template']['status']) && $this->request->data['Template']['status']!=''){
				$status = Sanitize::escape($this->request->data['Template']['status']);
				$this->Session->write('AdminSearch.status', $status);
				$defaultTab = Configure::read('Status.'.$status);
			}
		}

		$search_flag=0;
		if($this->Session->check('AdminSearch')){
			$keywords  = $this->Session->read('AdminSearch');
				
			foreach($keywords as $key=>$values){
				if($key == 'status'){
					$filters[] = array('Template.'.$key =>$values);
				}
				else{
				 $filters[] = array('Template.'.$key.' LIKE'=>"%".$values."%");
				 $filters_without_status = array('Template.'.$key.' LIKE'=>"%".$values."%");
				}
			}
				
			$search_flag=1;
		}
		$this->set(compact('search_flag','defaultTab'));

		$this->paginate = array(
			'Template'=>array(	
				'limit'=>Configure::read('App.AdminPageLimit'), 
				'order'=>array('Template.name'=>'ASC'),
				'conditions'=>$filters,
				'recursive'=>1
		)
		);

		$data = $this->paginate('Template');


		$this->set(compact('data'));
		$this->set('title_for_layout',  __('Email Templates', true));

		if(isset($this->request->params['named']['Template']))
		$this->Session->write('Url.Template', $this->request->params['named']['Template']);
		if(isset($this->request->params['named']['sort']))
		$this->Session->write('Url.sort', $this->request->params['named']['sort']);
		if(isset($this->request->params['named']['direction']))
		$this->Session->write('Url.direction', $this->request->params['named']['direction']);
		$this->Session->write('Url.defaultTab', $defaultTab);

		if($this->request->is('ajax')){
			$this->render('ajax/admin_index');
		}else{
			$temp=$filters_without_status;
			$all = $this->Template->find('count');
				
			$tabs = array('All'=>$all);
			$this->set(compact('tabs'));
		}
	}


	/**
	 * edit existing admin
	 */
	public function admin_edit($id = null) {
		$this->Template->id = $id;
		if (!$this->Template->exists()) {
			throw new NotFoundException(__('Invalid Template'));
		}

		if ($this->request->is('post') || $this->request->is('put')) {
				
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}

				$this->Template->set($this->request->data);
				$this->Template->setValidation('admin');
				if ($this->Template->validates()) {
					if ($this->Template->saveAll($this->request->data)) {
						$this->Session->setFlash(__('The Template information has been updated successfully',true), 'admin_flash_success');
						$this->redirect(array('action' => 'index'));
					} else {
						$this->Session->setFlash(__('The Template could not be saved. Please, try again.',true), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash(__('The Admin could not be saved. Please, correct errors.', true), 'admin_flash_error');
				}
			}
		}
		else {
			$this->request->data = $this->Template->read(null, $id);
		}
	}

	function referer($default = NULL, $local = false)
	{
		$defaultTab = $this->Session->read('Url.defaultTab');
		$Template = $this->Session->read('Url.Template');
		$sort = $this->Session->read('Url.sort');
		$direction = $this->Session->read('Url.direction');

		return Router::url(array('action'=>'index', $defaultTab,'Template'=>$Template,'sort'=>$sort,'direction'=>$direction),true);
	}

	public function admin_display($id,$layout){
		$this->layout = 'Emails/html/'.$layout;
		$data = $this->Template->read(null,$id);
		$this->set(compact(array('data')));
		$this->render('admin_display');
	}
}