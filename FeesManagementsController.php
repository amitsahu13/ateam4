<?php
/**
 * FeesManagement Controller
 *
 * PHP version 5.4
 *
 */
class FeesManagementsController extends AppController {
	/**
	 * JobFors name
	 *
	 * @var string
	 * @access public
	 */
	var	$name	=	'FeesManagements';
	var	$uses	=	array('FeesManagement');
	var $helpers = array('Html','General');
	var $model='FeesManagement';
	var $controller='fees_managements';
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();

		$this->set('controller',$this->controller);
		$this->set('model',$this->model);

	}


	public function admin_index($id=null) {

		$model=$this->model;
		$controller=$this->controller;

		$this->$model->id = $id;
		if ($this->request->is('post') || $this->request->is('put')) {
			if(!empty($this->request->data)) {
					
					
				if(empty($this->request->data[$model]['add_member_project_option']))
				{
					unset($this->request->data[$model]['add_member_project_option_value']);
				}
				if(empty($this->request->data[$model]['authenticate_myself_option']))
				{
					unset($this->request->data[$model]['authenticate_myself_option_value']);
				}
				if(empty($this->request->data[$model]['contracts_pdfs_option']))
				{
					unset($this->request->data[$model]['contracts_pdfs_option_value']);
				}
				if(empty($this->request->data[$model]['transferred_delayed_percentage_option']))
				{
					unset($this->request->data[$model]['transferred_delayed_percentage_option_value']);
				}
				if(empty($this->request->data[$model]['equity_sharing_option']))
				{
					unset($this->request->data[$model]['equity_sharing_option_value']);
				}
				if(empty($this->request->data[$model]['dispute_management_option']))
				{
					unset($this->request->data[$model]['dispute_management_option_value']);
				}
				if(empty($this->request->data[$model]['nda_usage_option']))
				{
					unset($this->request->data[$model]['nda_usage_option_value']);
				}
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				//validate Agreement data
				$this->$model->set($this->request->data);
				$this->$model->setValidation('admin');
				if ($this->$model->validates()) {
					if ($this->$model->save($this->request->data)) {
						$this->Session->setFlash(__('The '.$model.' information has been updated successfully',true), 'admin_flash_success');
						$this->redirect(array('action' => 'index'));
					} else {
						$this->Session->setFlash(__('The '.$model.' could not be saved. Please, try again.',true), 'admin_flash_error');
					}
				}
				else {
					$this->Session->setFlash(__('The '.$model.' could not be saved. Please, correct errors.', true), 'admin_flash_error');
				}
			}
		}
		else {
			$data = $this->$model->find('first');
			if(!empty($data))
			{
				$this->request->data = $data;
			}
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

}