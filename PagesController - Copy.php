<?php
/**
 * Pages Controller
 *
 * PHP version 5.4
 *
 */
class PagesController extends AppController {
	/**
	 * Controller name
	 *
	 * @var string
	 * @access public
	 */
	var	$name	=	'Pages';


	public $helpers = array('General','Html');
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('index');
	}
	/*
	 * List all Pages in admin panel
	 */
	public function admin_index($defaultTab='All') {

		if(!isset($this->request->params['named']['Page'])){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
		}
		//pr($this->Session->read('AdminSearch'));die;
		$filters_without_status = $filters = array();
		if($defaultTab!='All'){

			$filters[] = array('Page.status'=>array_search($defaultTab, Configure::read('Status')));
		}
		if(!empty($this->request->data)){
			$this->Session->delete('AdminSearch');
			$this->Session->delete('Url');
			App::uses('Sanitize', 'Utility');
			if(!empty($this->request->data['Page']['title'])){
				$title = Sanitize::escape($this->request->data['Page']['title']);
				$this->Session->write('AdminSearch.title', $title);
			}
				
			if(isset($this->request->data['Page']['status']) && $this->request->data['Page']['status']!=''){
				$status = Sanitize::escape($this->request->data['Page']['status']);
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
					$filters[] = array('Page.'.$key =>$values);
						
				}
				else{
				 $filters[] = array('Page.'.$key.' LIKE'=>"%".$values."%");
				 $filters_without_status = array('Page.'.$key.' LIKE'=>"%".$values."%");
				}
			}
				
			$search_flag=1;
		}

		$this->set(compact('search_flag','defaultTab'));

		$this->paginate = array(
			'Page'=>array(	
				'limit'=>Configure::read('App.AdminPageLimit'), 
				'order'=>array('Page.title'=>'ASC'),
				'conditions'=>$filters,
				'recursive'=>1
		)
		);

		$data = $this->paginate('Page');
		$all = count($data);

		$this->set(compact('data'));
		$this->set('title_for_layout',  __('Pages', true));

		if(isset($this->request->params['named']['Page']))
		$this->Session->write('Url.Page', $this->request->params['named']['Page']);
		if(isset($this->request->params['named']['sort']))
		$this->Session->write('Url.sort', $this->request->params['named']['sort']);
		if(isset($this->request->params['named']['direction']))
		$this->Session->write('Url.direction', $this->request->params['named']['direction']);
		$this->Session->write('Url.defaultTab', $defaultTab);

		if($this->request->is('ajax')){
				
			$this->render('ajax/admin_index');
		}else{

			/*$active=0;$inactive=0;
			 if($search_status=='' || $search_status==Configure::read('App.Status.active')){
				$temp=$filters_without_status;
				$temp[] = array('Page.status'=>Configure::read('App.Status.active'));
				$active = $this->Page->find('count',array('conditions'=>$temp));
				}
					
				if($search_status=='' || $search_status==Configure::read('App.Status.inactive')){
				$temp=$filters_without_status;
				$temp[] = array('Page.status'=>Configure::read('App.Status.inactive'));
				$inactive = $this->Page->find('count',array('conditions'=>$temp));
				} */
			if($search_flag==0)
			$all = $this->Page->find('count');
				
			$tabs = array('All'=>$all);
			$this->set(compact('tabs'));
		}
	}




	/**
	 * edit existing admin
	 */
	public function admin_edit($id = null) {

		$this->Page->id = $id;
		if (!$this->Page->exists()) {
			throw new NotFoundException(__('Invalid Page'));
		}

		if ($this->request->is('post') || $this->request->is('put')) {
				
			if(!empty($this->request->data)) {
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				//validate Page data
				$this->Page->set($this->request->data);
				$this->Page->setValidation('admin');
				if ($this->Page->validates()) {
					if ($this->Page->save($this->request->data)) {
						$this->Session->setFlash(__('The Page information has been updated successfully',true), 'admin_flash_success');
						$this->redirect(array('action' => 'index'));
					} else {
						$this->Session->setFlash(__('The Page could not be saved. Please, try again.',true), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash(__('The Page could not be saved. Please, correct errors.', true), 'admin_flash_error');
				}
			}
		}
		else {
			$this->request->data = $this->Page->read(null, $id);
		}
	}

	function referer($default = NULL, $local = false)
	{
		$defaultTab = $this->Session->read('Url.defaultTab');
		$Page = $this->Session->read('Url.Page');
		$sort = $this->Session->read('Url.sort');
		$direction = $this->Session->read('Url.direction');

		return Router::url(array('action'=>'index', $defaultTab,'Page'=>$Page,'sort'=>$sort,'direction'=>$direction),true);
	}

	public function index($type=null){
		$this->layout = 'lay_home_page';
		$this->set('title_for_layout',"Market Place");
		$this->loadModel('Video');
		$video_data=$this->Video->find('all',array('limit'=>'1','order'=>array('Video.id'=>'desc'),'conditions'=>array('Video.status'=>Configure::read('App.Status.active'))));
		//pr($video_data);
		$this->set(compact('video_data'));
	}


	public function view()
	{
		$data=$this->Page->find('first',array('conditions'=>array('Page.slug'=>
		$this->params['static'])));
		pr($data);die;
	}


}