<?php
/**
 * Settings Controller
 *
 * PHP version 5.4
 *
 */
class SettingsController extends AppController {
	/**
	 * Settings name
	 *
	 * @var string
	 * @access public
	 */
	var	$name	=	'Settings';
	var	$uses	=	array('Setting');
	var $helpers = array('Html');
	/*
	 * beforeFilter
	 * @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter();
	}


	/**
	 * edit existing user
	 */
	public function admin_index() {

		if ($this->request->is('post') || $this->request->is('put')) {
				
			if(!empty($this->request->data)) {

				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}

				//validate user data
				$this->Setting->set($this->request->data);
				$this->Setting->setValidation('admin');
				if ($this->Setting->validates()) {
						
					if ($this->Setting->saveAll($this->request->data)) {
						$this->Session->setFlash(__('The Settings has been updated successfully',true), 'admin_flash_success');
						$this->redirect($this->referer());
					} else {
						$data = $this->Setting->find('all', array('fields'=>array('id', 'label', 'description'),  'conditions' => array('NOT' => array('Setting.name' => array('front_video', 'banner_speed', 'featured_staff_speed')))));

						for($i=0; $i<count($data); $i++){
							$this->request->data[$i]['Setting']['label'] = $data[$i]['Setting']['label'];
							$this->request->data[$i]['Setting']['description'] = $data[$i]['Setting']['description'];
						}

						$this->Session->setFlash(__('The Settings could not be updated. Please, try again.',true), 'admin_flash_error');
					}
				}
				else {
						
					$data = $this->Setting->find('all', array('fields'=>array('id', 'label', 'description'),  'conditions' => array('NOT' => array('Setting.name' => array('front_video', 'banner_speed', 'featured_staff_speed')))));

					for($i=0; $i<count($data); $i++){
						$this->request->data[$i]['Setting']['label'] = $data[$i]['Setting']['label'];
						$this->request->data[$i]['Setting']['description'] = $data[$i]['Setting']['description'];
					}
					$this->Session->setFlash(__('The Settings could not be updated. Please, correct errors.', true), 'admin_flash_error');
				}
			}
		}
		else {

			$this->request->data = $this->Setting->find('all', array(
        'conditions' => array('NOT' => array('Setting.name' => array('front_video', 'banner_speed', 'featured_staff_speed')))));

		}
			
	}
}