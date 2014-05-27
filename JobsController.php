<?php
/**
 * Jobs Controller
 * PHP version 5.4
 * rommie Edited :   
 * pashkovdenis@gmail.com   
 * 
 */

// Jobs
class JobsController extends AppController {
	/**
	 * Jobs name
	 *
	 * @var string
	 * @access public
	 */
	var $name = 'Jobs';
	var $uses = array (
			'Job',
			'Category',
			'Skill',
			'JobSkill',
			'Region',
			'Country',
			'State' 
	);
	var $helpers = array (
			'Html',
			'General',
			'Js',
			'Time',
			'Verify',
			'Feedback' 
	);
	var $model = 'Job';
	var $controller = 'jobs';
	
	/*
	 * beforeFilter @return void
	 */
	public function beforeFilter() {
		parent::beforeFilter ();
		
		$this->set ( 'controller', $this->controller );
		$this->set ( 'model', $this->model );
	}
	
	/*
	 * public function delete_Job_milestone() { die("hiiiiii"); }
	 */
	public function admin_index($defaultTab = 'All', $project_id = null) {
		$model = $this->model;
		$controller = $this->controller;
		if (! isset ( $this->request->params ['named'] ['page'] )) {
			$this->Session->delete ( 'AdminSearch' );
			$this->Session->delete ( 'Url' );
		}
		$filters = $filters_without_status = array ();
		if (isset ( $project_id )) {
			$filters_without_status = $filters = array (
					$model . '.project_id' => $project_id 
			);
		}
		if ($defaultTab != 'All') {
			$filters [] = array (
					$model . '.status' => array_search ( $defaultTab, Configure::read ( 'Status' ) ) 
			);
		}
		
		if (! empty ( $this->request->data )) {
			$this->Session->delete ( 'AdminSearch' );
			$this->Session->delete ( 'Url' );
			
			App::uses ( 'Sanitize', 'Utility' );
			if (! empty ( $this->request->data ['' . $model . ''] ['title'] )) {
				$title = Sanitize::escape ( $this->request->data ['' . $model . ''] ['title'] );
				$this->Session->write ( 'AdminSearch.title', $title );
			}
			
			if (isset ( $this->request->data ['' . $model . ''] ['status'] ) && $this->request->data ['' . $model . ''] ['status'] != '') {
				$status = Sanitize::escape ( $this->request->data ['' . $model . ''] ['status'] );
				$this->Session->write ( 'AdminSearch.status', $status );
				$defaultTab = Configure::read ( 'Status.' . $status );
			}
		}
		
		$search_flag = 0;
		$search_status = '';
		if ($this->Session->check ( 'AdminSearch' )) {
			$keywords = $this->Session->read ( 'AdminSearch' );
			
			foreach ( $keywords as $key => $values ) {
				if ($key == 'status') {
					$search_status = $values;
					$filters [] = array (
							'' . $model . '.' . $key => $values 
					);
				} else {
					$filters [] = array (
							'' . $model . '.' . $key . ' LIKE' => "%" . $values . "%" 
					);
					$filters_without_status [] = array (
							'' . $model . '.' . $key . ' LIKE' => "%" . $values . "%" 
					);
				}
			}
			
			$search_flag = 1;
		}
		$this->set ( compact ( 'search_flag', 'defaultTab' ) );
		$this->paginate = array (
				'' . $model . '' => array (
						'limit' => Configure::read ( 'App.AdminPageLimit' ),
						'order' => array (
								'' . $model . '.id' => 'desc' 
						),
						'conditions' => $filters,
						'recursive' => 1 
				) 
		);
		
		$data = $this->paginate ( '' . $model . '' );
		$this->set ( compact ( 'data', 'project_id' ) );
		$this->set ( 'title_for_layout', __ ( '' . $model . 's', true ) );
		
		if (isset ( $this->request->params ['named'] ['page'] ))
			$this->Session->write ( 'Url.page', $this->request->params ['named'] ['page'] );
		if (isset ( $this->request->params ['named'] ['sort'] ))
			$this->Session->write ( 'Url.sort', $this->request->params ['named'] ['sort'] );
		if (isset ( $this->request->params ['named'] ['direction'] ))
			$this->Session->write ( 'Url.direction', $this->request->params ['named'] ['direction'] );
		$this->Session->write ( 'Url.defaultTab', $defaultTab );
		
		if ($this->request->is ( 'ajax' )) {
			$this->render ( 'ajax/admin_index' );
		} else {
			
			$active = 0;
			$inactive = 0;
			if ($search_status == '' || $search_status == Configure::read ( 'App.Status.active' )) {
				$temp = $filters_without_status;
				$temp [] = array (
						'' . $model . '.status' => Configure::read ( 'App.Status.active' ) 
				);
				$active = $this->$model->find ( 'count', array (
						'conditions' => $temp 
				) );
			}
			
			if ($search_status == '' || $search_status == Configure::read ( 'App.Status.inactive' )) {
				$temp = $filters_without_status;
				$temp [] = array (
						'' . $model . '.status' => Configure::read ( 'App.Status.inactive' ) 
				);
				$inactive = $this->$model->find ( 'count', array (
						'conditions' => $temp 
				) );
			}
			
			$tabs = array (
					'All' => $active + $inactive,
					'Active' => $active,
					'Inactive' => $inactive 
			);
			$this->set ( compact ( 'tabs' ) );
		}
	}
	public function referer($default = NULL, $local = false) {
		$defaultTab = $this->Session->read ( 'Url.defaultTab' );
		$Page = $this->Session->read ( 'Url.Page' );
		$sort = $this->Session->read ( 'Url.sort' );
		$direction = $this->Session->read ( 'Url.direction' );
		
		return Router::url ( array (
				'action' => 'index',
				$defaultTab,
				'Page' => $Page,
				'sort' => $sort,
				'direction' => $direction 
		), true );
	}
	
	/* delete feedbacks */
	public function admin_delete($id = null) {
		$model = $this->model;
		$controller = $this->controller;
		$this->loadModel ( 'JobsSkill' );
		$this->loadModel ( 'JobMilestone' );
		$this->loadModel ( 'JobAttachment' );
		$this->loadModel ( 'JobFile' );
		$this->loadModel ( 'JobBid' );
		$this->loadModel ( 'JobMilestone' );
		$this->$model->bindModel ( array (
				'hasMany' => array (
						'JobsSkill' => array (
								'dependent' => true 
						),
						'JobMilestone' => array (
								'dependent' => true 
						),
						'JobAttachment' => array (
								'dependent' => true 
						),
						'JobFile' => array (
								'dependent' => true 
						),
						'JobBid' => array (
								'dependent' => true 
						),
						'JobMilestone' => array (
								'dependent' => true 
						) 
				) 
		), false );
		
		$this->$model->id = $id;
		if (! $this->$model->exists ()) {
			throw new NotFoundException ( __ ( 'Invalid ' . $model . '' ) );
		}
		if (! isset ( $this->request->params ['named'] ['token'] ) || ($this->request->params ['named'] ['token'] != $this->request->params ['_Token'] ['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback ();
		}
		// if($id !=9)
		// {
		if ($this->$model->delete ()) {
			parent::rrmdir ( JOB_FILES_PATH . $id );
			$this->Session->setFlash ( __ ( '' . $model . ' deleted successfully' ), 'admin_flash_success' );
			$this->redirect ( $this->referer () );
		} 		// }
		else {
			$this->Session->setFlash ( __ ( 'You can\'t delete this record.', 'admin_flash_error' ) );
			$this->redirect ( $this->referer () );
		}
	}
	
	/* delete selected process */
	public function admin_process() {
		// pr($this->request->data);die;
		$model = $this->model;
		$controller = $this->controller;
		if (! $this->request->is ( 'post' )) {
			throw new MethodNotAllowedException ();
		}
		
		if (! isset ( $this->request->params ['named'] ['token'] ) || ($this->request->params ['named'] ['token'] != $this->request->params ['_Token'] ['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback ();
		}
		
		if (! empty ( $this->request->data )) {
			App::uses ( 'Sanitize', 'Utility' );
			$action = Sanitize::escape ( $this->request->data ['' . $model . ''] ['pageAction'] );
			
			$ids = $this->request->data ['' . $model . ''] ['id'];
			
			if (count ( $this->request->data ) == 0 || $this->request->data ['' . $model . ''] == null) {
				$this->Session->setFlash ( 'No items selected.', 'admin_flash_error' );
				$this->redirect ( $this->referer () );
			}
			
			if ($action == "delete") {
				$this->$model->deleteAll ( array (
						'' . $model . '.id' => $ids 
				) );
				$this->Session->setFlash ( '' . $model . ' have been deleted successfully', 'admin_flash_success' );
				$this->redirect ( $this->referer () );
			}
			if ($action == "activate") {
				$this->$model->updateAll ( array (
						'' . $model . '.status' => Configure::read ( 'App.Status.active' ) 
				), array (
						'' . $model . '.id' => $ids 
				) );
				
				$this->Session->setFlash ( '' . $model . ' have been activated successfully', 'admin_flash_success' );
				$this->redirect ( $this->referer () );
			}
			
			if ($action == "deactivate") {
				$this->$model->updateAll ( array (
						'' . $model . '.status' => Configure::read ( 'App.Status.inactive' ) 
				), array (
						'' . $model . '.id' => $ids 
				) );
				
				$this->Session->setFlash ( '' . $model . ' have been deactivated successfully', 'admin_flash_success' );
				$this->redirect ( $this->referer () );
			}
		} else {
			$this->redirect ( array (
					'controller' => '' . $controllers . '',
					'action' => 'index' 
			) );
		}
	}
	/**
	 * edit existing Agreement
	 */
	public function admin_edit($id = null) {
		$model = $this->model;
		$controller = $this->controller;
		$this->loadModel ( 'Project' );
		$this->$model->id = $id;
		if (! $this->$model->exists ()) {
			throw new NotFoundException ( __ ( 'Invalid ' . $model . '' ) );
		}
		$this->Job->bindModel ( array (
				'hasMany' => array (
						'JobSkill' 
				) 
		), false );
		if ($this->request->is ( 'post' ) || $this->request->is ( 'put' )) {
			
			if (! empty ( $this->request->data )) {
				
				if (! empty ( $this->request->data ['Job'] ['job_doc'] ['tmp_name'] )) {
					$file_array = $this->request->data ['Job'] ['job_doc'];
					$this->request->data ['Job'] ['job_doc'] = $this->request->data ['Job'] ['job_doc'] ['name'];
				} else {
					$file_array = '';
				}
				
				if (! isset ( $this->request->params ['data'] ['_Token'] ['key'] ) || ($this->request->params ['data'] ['_Token'] ['key'] != $this->request->params ['_Token'] ['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback ();
				}
				if (array_key_exists ( 'JobSkill', $this->request->data )) {
					unset ( $this->request->data ['JobSkill'] [0] );
				}
				// pr($this->request->data); die;
				if (isset ( $this->request->data ['Job'] ['type'] ) && $this->request->data ['Job'] ['type'] == 1) {
					unset ( $this->request->data ['Job'] ['type_option_value'] );
					unset ( $this->request->data ['Job'] ['duration_id'] );
					unset ( $this->request->data ['Job'] ['hourly_rate_id'] );
					unset ( $this->request->data ['Job'] ['hourly_min_rate'] );
					unset ( $this->request->data ['Job'] ['hourly_max_rate'] );
					if ($this->request->data ['Job'] ['budget_id'] != 9) {
						unset ( $this->request->data ['Job'] ['budget_min_rate'] );
						unset ( $this->request->data ['Job'] ['budget_max_rate'] );
					}
				} else if (isset ( $this->request->data ['Job'] ['type'] ) && $this->request->data ['Job'] ['type'] == 0) {
					unset ( $this->request->data ['Job'] ['budget_id'] );
					unset ( $this->request->data ['Job'] ['budget_min_rate'] );
					unset ( $this->request->data ['Job'] ['budget_max_rate'] );
					if ($this->request->data ['Job'] ['hourly_rate_id'] != 10) {
						unset ( $this->request->data ['Job'] ['hourly_min_rate'] );
						unset ( $this->request->data ['Job'] ['hourly_max_rate'] );
					}
				}
				if (isset ( $this->request->data ['Job'] ['date_type'] ) && $this->request->data ['Job'] ['date_type'] == 0) {
					unset ( $this->request->data ['Job'] ['start_date'] );
					unset ( $this->request->data ['Job'] ['end_date'] );
				}
				if (isset ( $this->request->data ['Job'] ['location_type'] ) && $this->request->data ['Job'] ['location_type'] == 0) {
					unset ( $this->request->data ['Job'] ['region_id'] );
					unset ( $this->request->data ['Job'] ['country_id'] );
					unset ( $this->request->data ['Job'] ['state_id'] );
					unset ( $this->request->data ['Job'] ['city'] );
					unset ( $this->request->data ['Job'] ['zipcode'] );
				}
				
				// validate Agreement data
				$this->$model->set ( $this->request->data );
				$this->$model->setValidation ( 'admin' );
				if ($this->$model->validates ()) {
					if ($this->$model->save ( $this->request->data )) {
						$id = $this->$model->id;
						$skil = array ();
						if (! empty ( $this->request->data ['JobSkill'] )) {
							foreach ( $this->request->data ['JobSkill'] as $k => $v ) {
								if (! empty ( $v ['skill_id'] )) {
									$skil [$k] ['skill_id'] = $v ['skill_id'];
									$skil [$k] ['job_id'] = $id;
								}
							}
							if (! empty ( $skil )) {
								$this->JobSkill->deleteAll ( array (
										'JobSkill.job_id' => $id 
								) );
								$this->JobSkill->saveAll ( $skil );
							}
						}
						if (! empty ( $file_array )) {
							$file_name = time ();
							/* this is being used to upload user big size profile image */
							$filename = parent::__upload ( $file_array, str_replace ( '{project_id}', $project_id, PROJECT_PLAN_PATH ), $file_name );
							
							$this->Job->saveField ( 'job_doc', $filename );
						}
						
						$this->Session->setFlash ( __ ( 'The ' . $model . ' information has been updated successfully', true ), 'admin_flash_success' );
						$this->redirect ( array (
								'action' => 'index' 
						) );
					} else {
						$this->Session->setFlash ( __ ( 'The ' . $model . ' could not be saved. Please, try again.', true ), 'admin_flash_error' );
					}
				} else {
					$this->Session->setFlash ( __ ( 'The ' . $model . ' could not be saved. Please, correct errors.', true ), 'admin_flash_error' );
				}
			}
		} else {
			$this->request->data = $this->$model->read ( null, $id );
			// pr($this->request->data);
		}
		// pr($this->request->data);die;
		$cat_id = 0;
		
		$cat_id = $this->request->data ['Job'] ['category_id'];
		// echo $cat_id;die;
		if (! empty ( $cat_id )) {
			$sub_categories = $this->Category->get_categories ( 'list', 'Category.name', array (
					'Category.parent_id' => $cat_id 
			) );
			
			$skills = $this->Skill->get_skills ( 'list', 'Skill.name', array (
					'Skill.category_id' => $cat_id 
			) );
		} else {
			$sub_categories = array ();
			$skills = array ();
		}
		$this->set ( compact ( 'sub_categories', 'skills' ) );
		$this->used_params ();
		$states = array ();
		$countries = array ();
		if (! empty ( $this->request->data ['Job'] ['region_id'] )) {
			$this->loadModel ( 'Country' );
			$region_id = $this->request->data ['Job'] ['region_id'];
			$countries = $this->Country->find ( 'list', array (
					'conditions' => array (
							'Country.region_id' => $region_id 
					) 
			) );
		}
		// pr($this->request->data);die;
		if (! empty ( $this->request->data ['Job'] ['country_id'] )) {
			$this->loadModel ( 'State' );
			$country_id = $this->request->data ['Job'] ['country_id'];
			$states = $this->State->find ( 'list', array (
					'conditions' => array (
							'State.country_id' => $country_id 
					) 
			) );
		}
		$this->set ( compact ( 'countries', 'states' ) );
	}
	public function admin_view($id = null) {
		$model = $this->model;
		$controller = $this->controller;
		$this->$model->id = $id;
		if (! $this->$model->exists ()) {
			throw new NotFoundException ( __ ( 'Invalid Job' ) );
		}
		$this->loadModel ( 'User' );
		$this->loadModel ( 'Category' );
		$this->$model->bindModel ( array (
				'belongsTo' => array (
						'Project',
						'HourlyRate',
						'Budget',
						'Duration',
						'Compensation',
						'JobFor',
						'User' => array (
								'fields' => array (
										'first_name',
										'last_name' 
								) 
						),
						'Region',
						'Country',
						'State',
						'Category' => array (
								'className' => 'Category',
								'foreignKey' => 'sub_category_id' 
						),
						'Availability' => array (
								'className' => 'Availability',
								'foreignKey' => 'expert_availability_id' 
						) 
				),
				'hasMany' => array(
					/*'JobSkill'=>array(
						'fields'=>array(
							'JobSkill.id','JobSkill.skill_id'
						),
						//'type'=>'inner'
						
					),*/
					'JobMilestone' => array (
								'fields' => array (
										'title',
										'description',
										'date' 
								) 
						),
						'JobAttachment' => array (
								'fields' => array (
										'file_name' 
								) 
						),
						'JobFile' => array (
								'fields' => array (
										'file_name' 
								) 
						) 
				) 
		), false );
		$this->JobSkill->bindModel ( array (
				'belongsTo' => array (
						'Skill' => array () 
				) 
		), false );
		$this->$model->recursive = 13;
		$data = $this->$model->read ( null, $id );
		$this->set ( compact ( 'data' ) );
		// pr($data);die;
	}
	
	/**
	 * toggle status existing Agreement
	 */
	public function admin_status($id = null) {
		$model = $this->model;
		$controller = $this->controller;
		$this->$model->id = $id;
		if (! $this->$model->exists ()) {
			throw new NotFoundException ( __ ( 'Invalid ' . $model . '' ) );
		}
		if (! isset ( $this->request->params ['named'] ['token'] ) || ($this->request->params ['named'] ['token'] != $this->request->params ['_Token'] ['key'])) {
			$blackHoleCallback = $this->Security->blackHoleCallback;
			$this->$blackHoleCallback ();
		}
		if ($this->$model->toggleStatus ( $id )) {
			$this->Session->setFlash ( __ ( '' . $model . '\'s status has been changed' ), 'admin_flash_success' );
			$this->redirect ( $this->referer () );
		}
		$this->Session->setFlash ( __ ( '' . $model . '\'s status was not changed', 'admin_flash_error' ) );
		$this->redirect ( $this->referer () );
	} /*
	   * Add new Agreement
	   */
	public function admin_add($project_id = null) {
		$model = $this->model;
		$controller = $this->controller;
		if ($project_id != null) {
			$this->request->data [$model] ['project_id'] = $project_id;
		}
		
		if ($this->request->is ( 'post' )) {
			
			if (! empty ( $this->request->data )) {
				// pr($this->request->data);die;
				
				if (! empty ( $this->request->data ['Job'] ['job_doc'] ['tmp_name'] )) {
					$file_array = $this->request->data ['Job'] ['job_doc'];
					$this->request->data ['Job'] ['job_doc'] = $this->request->data ['Job'] ['job_doc'] ['name'];
				} else {
					$file_array = '';
				}
				
				if (! isset ( $this->request->params ['data'] ['_Token'] ['key'] ) || ($this->request->params ['data'] ['_Token'] ['key'] != $this->request->params ['_Token'] ['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback ();
				}
				if (array_key_exists ( 'JobSkill', $this->request->data )) {
					unset ( $this->request->data ['JobSkill'] [0] );
				}
				if (isset ( $this->request->data ['Job'] ['type'] ) && $this->request->data ['Job'] ['type'] == 1) {
					unset ( $this->request->data ['Job'] ['type_option_value'] );
					unset ( $this->request->data ['Job'] ['duration_id'] );
					unset ( $this->request->data ['Job'] ['hourly_rate_id'] );
					unset ( $this->request->data ['Job'] ['hourly_min_rate'] );
					unset ( $this->request->data ['Job'] ['hourly_max_rate'] );
					if ($this->request->data ['Job'] ['budget_id'] != 9) {
						unset ( $this->request->data ['Job'] ['budget_min_rate'] );
						unset ( $this->request->data ['Job'] ['budget_max_rate'] );
					}
				} else if (isset ( $this->request->data ['Job'] ['type'] ) && $this->request->data ['Job'] ['type'] == 0) {
					unset ( $this->request->data ['Job'] ['budget_id'] );
					unset ( $this->request->data ['Job'] ['budget_min_rate'] );
					unset ( $this->request->data ['Job'] ['budget_max_rate'] );
					if ($this->request->data ['Job'] ['hourly_rate_id'] != 10) {
						unset ( $this->request->data ['Job'] ['hourly_min_rate'] );
						unset ( $this->request->data ['Job'] ['hourly_max_rate'] );
					}
				}
				if (isset ( $this->request->data ['Job'] ['date_type'] ) && $this->request->data ['Job'] ['date_type'] == 0) {
					unset ( $this->request->data ['Job'] ['start_date'] );
					unset ( $this->request->data ['Job'] ['end_date'] );
				}
				if (isset ( $this->request->data ['Job'] ['location_type'] ) && $this->request->data ['Job'] ['location_type'] == 0) {
					unset ( $this->request->data ['Job'] ['region_id'] );
					unset ( $this->request->data ['Job'] ['country_id'] );
					unset ( $this->request->data ['Job'] ['state_id'] );
					unset ( $this->request->data ['Job'] ['city'] );
					unset ( $this->request->data ['Job'] ['zipcode'] );
				}
				
				$this->$model->set ( $this->request->data );
				$this->$model->setValidation ( 'admin' );
				if ($this->$model->validates ()) {
					// pr($this->request->data); die;
					if ($this->$model->saveAll ( $this->request->data )) {
						$id = $this->$model->getLastInsertId ();
						$project_id = $this->request->data [$model] ['project_id'];
						$activationKey = mt_rand ( 1000000, 10000000 ) . $id;
						$this->Job->updateAll ( array (
								'Job.job_identification_no_no' => $activationKey 
						), array (
								'Job.id' => $id 
						) );
						$skil = array ();
						if (! empty ( $this->request->data ['JobSkill'] )) {
							foreach ( $this->request->data ['JobSkill'] as $k => $v ) {
								if (! empty ( $v ['skill_id'] )) {
									$skil [$k] ['skill_id'] = $v ['skill_id'];
									$skil [$k] ['job_id'] = $id;
								}
							}
							if (! empty ( $skil )) {
								$this->JobSkill->saveAll ( $skil );
							}
						}
						if (! empty ( $file_array )) {
							$file_name = time ();
							/* this is being used to upload user big size profile image */
							$filename = parent::__upload ( $file_array, str_replace ( '{project_id}', $project_id, PROJECT_PLAN_PATH ), $file_name );
							
							$this->Job->saveField ( 'job_doc', $filename );
						}
						$this->Session->setFlash ( __ ( '' . $model . ' has been saved successfully' ), 'admin_flash_success' );
						$this->redirect ( array (
								'action' => 'index',
								'All',
								$project_id 
						) );
					} else {
						$this->Session->setFlash ( __ ( 'The ' . $model . ' could not be saved. Please, try again.' ), 'admin_flash_error' );
					}
				} else {
					// pr($this->$model->validationErrors);die;
					$this->Session->setFlash ( 'The ' . $model . ' could not be saved.  Please, correct errors.', 'admin_flash_error' );
				}
			}
		}
		$sub_categories = array ();
		if (isset ( $this->request->data ['Job'] ['category_id'] ) && ! empty ( $this->request->data ['Job'] ['category_id'] )) {
			$sub_categories = $this->Category->find ( 'list', array (
					'conditions' => array (
							'Category.status' => Configure::read ( 'App.Status.active' ),
							'Category.type_for' => Configure::read ( 'App.Category.Job' ),
							'Category.parent_id' => $this->request->data ['Job'] ['category_id'] 
					) 
			) );
		}
		$skills = array ();
		$this->set ( compact ( 'project_id', 'skills', 'sub_categories' ) );
		$this->used_params ();
		$states = array ();
		$countries = array ();
		if (! empty ( $this->request->data ['Job'] ['region_id'] )) {
			$this->loadModel ( 'Country' );
			$region_id = $this->request->data ['Job'] ['region_id'];
			$countries = $this->Country->find ( 'list', array (
					'conditions' => array (
							'Country.region_id' => $region_id 
					) 
			) );
		}
		if (! empty ( $this->request->data ['Job'] ['country_id'] )) {
			$this->loadModel ( 'State' );
			$country_id = $this->request->data ['Job'] ['country_id'];
			$states = $this->State->find ( 'list', array (
					'conditions' => array (
							'State.country_id' => $country_id 
					) 
			) );
		}
		$this->set ( compact ( 'countries', 'states', 'sub_categories' ) );
	}
	protected function used_params() {
		$this->loadModel ( 'Project' );
		$this->loadModel ( 'Category' );
		$this->loadModel ( 'Country' );
		$this->loadModel ( 'Compensation' );
		$this->loadModel ( 'Budget' );
		$this->loadModel ( 'JobFor' );
		$this->loadModel ( 'HourlyRate' );
		$this->loadModel ( 'Duration' );
		$this->loadModel ( 'Availability' );
		$this->loadModel ( 'Duration' );
		$this->loadModel ( 'Region' );
		
		$hourlyRates = $this->HourlyRate->find ( 'list', array (
				'conditions' => array (
						'HourlyRate.status' => Configure::read ( 'App.Status.active' ) 
				) 
		) );
		$projectManagerAvailabilities = $this->Availability->find ( 'list', array (
				'conditions' => array (
						'Availability.status' => Configure::read ( 'App.Status.active' ) 
				) 
		) );
		/*
		 * pr($hourlyRates); pr($projectManagerAvailabilities); die;
		 */
		// $projects=$this->Project->find('list',array('conditions'=>array('Project.project_status_id !='=>Configure::read('App.Status.active'))));
		
		$projects = $this->Project->get_project_list ();
		
		$categories = $this->Category->find ( 'list', array (
				'conditions' => array (
						'Category.status' => Configure::read ( 'App.Status.active' ),
						'Category.type_for' => Configure::read ( 'App.Category.Job' ),
						'Category.parent_id' => 0 
				) 
		) );
		
		$regions = $this->Region->find ( 'list', array (
				'conditions' => array (
						'Region.status' => Configure::read ( 'App.Status.active' ) 
				) 
		) );
		
		// $countries=$this->Country->get_countries('list',array('Country.id','Country.name'));
		
		$compensations = $this->Compensation->get_compensation_list ();
		
		$budgets = $this->Budget->get_budget_list ();
		
		$job_fors = $this->JobFor->get_job_fors_list ();
		
		$duration = $this->Duration->get_duration_list ();
		
		return $this->set ( compact ( 'projects', 'categories', 'compensations', 'budgets', 'job_fors', 'duration', 'hourlyRates', 'projectManagerAvailabilities', 'regions' ) );
	}
	
	/**
	 * **********************Front Code Start From Here**************************
	 */
	
	/*
	 * public function project_jobs($defaultTab='All',$project_id=null) { $model=$this->model; $controller=$this->controller; if(!isset($this->request->params['named']['page'])){ $this->Session->delete('AdminSearch'); $this->Session->delete('Url'); } $filters = $filters_without_status = array(); if(isset($project_id) && !empty($project_id)){ $filters_without_status = $filters = array($model.'.project_id'=>$project_id); } if($defaultTab!='All'){ $filters[] = array($model.'.status'=>array_search($defaultTab, Configure::read('Status'))); } if(!empty($this->request->data)){ $this->Session->delete('AdminSearch'); $this->Session->delete('Url'); App::uses('Sanitize', 'Utility'); if(!empty($this->request->data[''.$model.'']['title'])){ $title = Sanitize::escape($this->request->data[''.$model.'']['title']); $this->Session->write('AdminSearch.title', $title); } if(isset($this->request->data[''.$model.'']['status']) && $this->request->data[''.$model.'']['status']!=''){ $status = Sanitize::escape($this->request->data[''.$model.'']['status']); $this->Session->write('AdminSearch.status', $status); $defaultTab = Configure::read('Status.'.$status); } } $search_flag=0;	$search_status=''; if($this->Session->check('AdminSearch')){ $keywords = $this->Session->read('AdminSearch'); foreach($keywords as $key=>$values){ if($key == 'status'){ $search_status=$values; $filters[] = array(''.$model.'.'.$key =>$values); } else{ $filters[] = array(''.$model.'.'.$key.' LIKE'=>"%".$values."%"); $filters_without_status[] = array(''.$model.'.'.$key.' LIKE'=>"%".$values."%"); } } $search_flag=1; } $this->set(compact('search_flag','defaultTab')); $this->paginate = array( ''.$model.''=>array( 'limit'=>Configure::read('App.PageLimit'), 'order'=>array(''.$model.'.id'=>'desc'), 'conditions'=>$filters, 'recursive'=>1 ) ); $data = $this->paginate(''.$model.''); $this->set(compact('data','project_id')); $this->set('title_for_layout', __(''.$model.'s', true)); if(isset($this->request->params['named']['page'])) $this->Session->write('Url.page', $this->request->params['named']['page']); if(isset($this->request->params['named']['sort'])) $this->Session->write('Url.sort', $this->request->params['named']['sort']); if(isset($this->request->params['named']['direction'])) $this->Session->write('Url.direction', $this->request->params['named']['direction']); $this->Session->write('Url.defaultTab', $defaultTab); if($this->request->is('ajax')){ $this->render('ajax/admin_index'); }else{ $active=0;$inactive=0; if($search_status=='' || $search_status==Configure::read('App.Status.active')){ $temp=$filters_without_status; $temp[] = array(''.$model.'.status'=>Configure::read('App.Status.active')); $active = $this->$model->find('count',array('conditions'=>$temp)); } if($search_status=='' || $search_status==Configure::read('App.Status.inactive')){ $temp=$filters_without_status; $temp[] = array(''.$model.'.status'=>Configure::read('App.Status.inactive')); $inactive = $this->$model->find('count',array('conditions'=>$temp)); } $tabs = array('All'=>$active+$inactive, 'Active'=>$active,'Inactive'=>$inactive); $this->set(compact('tabs')); } }
	 */
	
	
	
	/* public function job_bid_post(){
	
		$this->loadModel('Agreement');
		$this->loadModel('LawJurisdiction');
		$this->loadModel('Duration');
		$this->loadModel('JobBid');
		
		if ($this->request->is('post') || $this->request->is('put')) {
			
			if(!empty($this->request->data)) {
				//pr($this->request->data);die;
				if (!isset($this->request->params['data']['_Token']['key']) || ($this->request->params['data']['_Token']['key'] != $this->request->params['_Token']['key'])) {
					$blackHoleCallback = $this->Security->blackHoleCallback;
					$this->$blackHoleCallback();
				}
				$this->JobBid->set($this->request->data);
				$this->JobBid->setValidation('user');
				if ($this->JobBid->validates($this->request->data, array('validate'=>'only'))) {
					
					$this->request->data['JobBid']['user_id'] = $this->Session->read('Auth.User.id');
					
					if ($this->JobBid->save($this->request->data)) {
						$this->Session->setFlash(__('The Bid has been posted successfully.',true), 'admin_flash_success');
						//$this->redirect(array('action' => 'index'));
					} else {
						$this->Session->setFlash(__('The Bid could not be saved. Please, try again.',true), 'admin_flash_error');
					}
				
				
				}else{
				
					$this->Session->setFlash('The Bid could not be saved.  Please, correct errors.', 'admin_flash_error');
				}
				
			}
		}	
		
		$agreementList = $this->Agreement->find('list',array('conditions'=>array('Agreement.status'=>Configure::read('App.Status.active'))));
		$lawJurisdictionList = $this->LawJurisdiction->find('list',array('conditions'=>array('LawJurisdiction.status'=>Configure::read('App.Status.active'))));
		$durationList = $this->Duration->find('list',array('conditions'=>array('Duration.status'=>Configure::read('App.Status.active'))));
	
	} */
	
	
	
	public function job_attachementupload() {
		$this->loadModel ( 'FileTemp' );
		if (isset ( $this->request->data ["FileTemp"] ['project_file'] ['tmp_name'] )) {
			
			if (! empty ( $this->request->data ["FileTemp"] ['project_file'] ['tmp_name'] )) {
				$file_array = $this->request->data ["FileTemp"] ['project_file'];
				$this->request->data ["FileTemp"] ['project_file'] = $this->request->data ["FileTemp"] ['project_file'] ['name'];
			}
			
			if (! empty ( $file_array )) {
				$file_name = time ();
				/* this is being used to upload user big size profile image */
				$filename = parent::__upload ( $file_array, PROJECT_TEMP_THUMB_DIR_FILE, $file_name );
			}
		}
		$name = $this->request->data ['FileTemp'] ['project_file'];
		
		$this->request->data ['FileTemp'] ['user_id'] = $this->Auth->User ( 'id' );
		$this->request->data ['FileTemp'] ['project_file'] = $filename;
		$this->request->data ['FileTemp'] ['file_name'] = $name;
		
		if (! empty ( $this->request->data )) {
			$avataruploaded = $this->FileTemp->saveAll ( $this->request->data );
			$lastInsert_id = $this->FileTemp->id;
			$created = date ( 'F j, Y, g:i a', strtotime ( $this->FileTemp->field ( 'created' ) ) );
			if ($avataruploaded) {
				echo "success|" . $name . "|" . $lastInsert_id . "|" . $created;
			} else {
				echo "failed";
			}
		}
		die ();
	}
	public function delete_job_attachement($id) {
		$this->layout = false;
		$this->autoRender = false;
		$this->loadModel ( 'FileTemp' );
		$temp = $this->FileTemp->find ( 'first', array (
				'conditions' => array (
						'FileTemp.id' => $id 
				) 
		) );
		$file = $temp ['FileTemp'] ['project_file'];
		$this->FileTemp->id = $id;
		unlink ( PROJECT_TEMP_THUMB_DIR_FILE . $file );
		$this->FileTemp->delete ();
	}
	public function delete_job_general_edit_attachement() {
		$this->layout = false;
		$this->autoRender = false;
		$this->loadModel ( 'JobAttachment' );
		$temp = $this->JobAttachment->find ( 'first', array (
				'conditions' => array (
						'JobAttachment.id' => $this->params ['pass'] [0] 
				) 
		) );
		$file = $temp ['JobAttachment'] ['file_name'];
		$this->JobAttachment->id = $temp ['JobAttachment'] ['id'];
		unlink ( str_replace ( '{job_id}', $this->params ['pass'] [1], JOB_ATTACHEMENT_PATH_FOLDER ) . '/' . $file );
		$this->JobAttachment->delete ();
	}
	public function download_job_general_edit_attachement() {
		$this->layout = false;
		$this->loadModel ( 'JobAttachment' );
		$data_file = $this->JobAttachment->find ( 'first', array (
				'conditions' => array (
						'JobAttachment.id' => $this->params ['pass'] [0] 
				) 
		) );
		$fullPath = str_replace ( '{job_id}', $this->params ['pass'] [1], JOB_ATTACHEMENT_PATH_FOLDER ) . '/' . $data_file ['JobAttachment'] ['file_name'];
		if ($fd = fopen ( $fullPath, "r" )) {
			$fsize = filesize ( $fullPath );
			$path_parts = pathinfo ( $fullPath );
			$ext = strtolower ( $path_parts ["extension"] );
			// $fl = $fullPath;
			header ( 'Content-Description: File Transfer' );
			header ( 'Content-Disposition: attachment; filename=' . basename ( $fullPath ) );
			header ( 'Content-Transfer-Encoding: binary' );
			header ( 'Expires: 0' );
			header ( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header ( 'Pragma: public' );
			header ( 'Content-Length: ' . filesize ( $fullPath ) );
			ob_clean ();
			flush ();
			readfile ( $fullPath );
			exit ();
		}
	}
	public function download_job_attachement($id) {
		$this->layout = false;
		$this->loadModel ( 'FileTemp' );
		$data_file = $this->FileTemp->find ( 'first', array (
				'conditions' => array (
						'FileTemp.id' => $id 
				) 
		) );
		$fullPath = 'img/' . PROJECT_TEMP_THUMB_DIR_FILE_VIEW . $data_file ['FileTemp'] ['project_file'];
		$fsize = filesize ( $fullPath );
		if ($fd = fopen ( $fullPath, "r" )) {
				$fsize = filesize ( $fullPath );
				header ( 'Content-Description: File Transfer' );
				header ( "Content-type: application/force-download" );
				header ( 'Content-Disposition: attachment; filename=' . $data_file ['FileTemp'] ['file_name'] );
				header ( 'Content-Transfer-Encoding: Binary' );
				header ( 'Content-Type: application/octet-stream' );
				header ( 'Expires: 0' );
				header ( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
				header ( 'Pragma: public' );
				header ( 'Content-Length: ' . $fsize );
				// ob_clean();
				flush ();
				readfile ( $fullPath );
				exit ();
			} else {
				echo "File not Found";
			}
		
	
	}
	public function admin_download_attachment($job_id = null, $file_name = null) {
		$this->layout = false;
		$this->loadModel ( 'JobFile' );
		$this->loadModel ( 'JobAttachment' );
		$data_file = $this->JobAttachment->find ( 'first', array (
				'conditions' => array (
						'JobAttachment.job_id' => $job_id,
						'JobAttachment.file_name' => $file_name 
				) 
		) );
		$fullPath = str_replace ( '{job_id}', $job_id, JOB_ATTACHEMENTS_PATH_FOLDER ) . $data_file ['JobAttachment'] ['file_name'];
		
		if ($fd = fopen ( $fullPath, "r" )) {
			$fsize = filesize ( $fullPath );
			$path_parts = pathinfo ( $fullPath );
			$ext = strtolower ( $path_parts ["extension"] );
			// $fl = $fullPath;
			header ( 'Content-Description: File Transfer' );
			header ( "Content-type: application/force-download" );
			header ( 'Content-Disposition: attachment; filename=' . basename ( $fullPath ) );
			header ( 'Content-Transfer-Encoding: Binary' );
			header ( 'Content-Type: application/octet-stream' );
			header ( 'Expires: 0' );
			header ( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header ( 'Pragma: public' );
			header ( 'Content-Length: ' . $fsize );
			// ob_clean();
			flush ();
			readfile ( $fullPath );
			exit ();
		}
		die ();
	}
	public function admin_download_file($job_id = null, $file_name = null) {
		$this->layout = false;
		$this->loadModel ( 'JobFile' );
		
		$data_file = $this->JobFile->find ( 'first', array (
				'conditions' => array (
						'JobFile.job_id' => $job_id,
						'JobFile.file_name' => $file_name 
				) 
		) );
		$fullPath = str_replace ( '{job_id}', $job_id, JOB_FILES_PATH_FOLDER ) . '/' . $data_file ['JobFile'] ['file_name'];
		
		if ($fd = fopen ( $fullPath, "r" )) {
			$fsize = filesize ( $fullPath );
			$path_parts = pathinfo ( $fullPath );
			$ext = strtolower ( $path_parts ["extension"] );
			// $fl = $fullPath;
			header ( 'Content-Description: File Transfer' );
			header ( "Content-type: application/force-download" );
			header ( 'Content-Disposition: attachment; filename=' . basename ( $fullPath ) );
			header ( 'Content-Transfer-Encoding: Binary' );
			header ( 'Content-Type: application/octet-stream' );
			header ( 'Expires: 0' );
			header ( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header ( 'Pragma: public' );
			header ( 'Content-Length: ' . $fsize );
			// ob_clean();
			flush ();
			readfile ( $fullPath );
			exit ();
		}
		die ();
	}
	
	/*
	 * REad contract agrement with reader on html pashkovdenis@gmail.com
	 */
	public function read_pdf($id = null) {
		$this->autoRender = FALSE;
		$this->loadModel ( "Colloberation" );
		$model = new Colloberation ();
		$model->loadSingle ( $id );
		header ( "Content-type: application/pdf" );
		header ( "Pragma: no-cache" );
		header ( "Expires: 0" );
		echo file_get_contents ( $model->link );
	}
	
	/*
	 * Job general Starts here pashkovdenis@gmail.com 2014 Post An new job methods Here :
	 */
	public function job_general($project_id = null, $id = null) {
		$this->loadModel ( "User" );
		$this->loadModel ( "Category" );
		$this->loadModel("JobBid") ;  
		 $this->loadModel("Project") ;
        $loaded_raw_project  =  $this->Project->find("first", array("conditions"=>array("Project.id"=>$project_id)));
        if ($loaded_raw_project["Project"]["visibility"]==0)
            $this->set("private_project",  true) ;


		$job_skills = $this->Category->get_job_skills ();
		$update = false;
		if ($id != null) {
			$update = true;
			$this->set ( "update", $id );
		}
		
		// Check wherever it is new Job : ?
		if (isset ( $_GET ["new"] )) {
			// Clear Cache for pervious job creating flow :
			$this->Session->delete ( "temp_job" );
			$this->Session->delete ( "job_compensation" );
			$this->Session->delete ( "job_timeline" );
			$this->Session->delete ( "job_general" );
		}
		
		$this->set ( "job_skills", $job_skills );
		$user_model = new User ();
		$user_local = $user_model->find ( "first", array (
				"conditions" => array (
						"id" => $this->Auth->user ( "id" ) 
				) 
		) );
		if ($user_local ["User"] ["role_id"] == 4) {
			$this->Session->setFlash ( __ ( " Expert Cannot  create a Job " ), 'default', array (
					"class" => "error" 
			) );
			$this->redirect ( "/" );
		}
		
		$this->layout = 'lay_my_project_job';
		$this->set ( 'title_for_layout', 'Job General' );
		// Assign model instance for Job Model ;
		$model = $this->model;
		$this->loadModel ( 'FileTemp' );
		$this->loadModel ( 'JobFile' );
		$this->loadModel ( 'JobAttachment' );
		$this->loadModel ( "Project" );
		$this->loadModel ( "Skill" );
		$skill_model = new Skill ();
		
		$clb_loaded = $this->Project->query ( "SELECT * FROM clb_projects WHERE  project='{$project_id}' " );
		
		if ($id == null && $this->Session->read ( "temp_job" ) != "")
			$id = $this->Session->read ( "temp_job" );
		$jobid = $id;
		
		$results_apply = array();
		// Id Selet
		
		if ($id != null) {
			
			$selc = array();
			$ids = $skill_model->query ( "SELECT skill_id FROM jobs_skills WHERE job_id='{$id}' " );
			
			foreach ( $ids as $skid ) {
				$selc [] = $skid ["jobs_skills"] ["skill_id"];
			}
			
			$skills_apply = $this->Skill->get_skills ( 'list', 'Skill.name', array (
					'Skill.id' => $selc 
			) );
			
			foreach ( $skills_apply as $id => $name ) {
				$result = new stdClass ();
				$result->id = $id;
				$result->skill = $name;
				$single_skill = $this->Skill->find ( "first", array (
						"conditions" => array (
								"id" => $id 
						) 
				) );
				$category = $this->Category->find ( "first", array (
						"conditions" => array (
								"Category.id" => $single_skill ["Skill"] ["category_id"] 
						) 
				) );
				$result->catid = $category ["Category"] ["id"];
				$result->catname = $category ["Category"] ["name"];
				$results_apply [] = $result;
			}
		}
		
		$results_apply = array_reverse ( $results_apply );
		
		//
		if (count ( $results_apply )) {
			$this->set ( "applyskills", $results_apply );
		}
		
		// Create Collaboration :
		if (isset ( $clb_loaded [0] ["clb_projects"] ["id"] )) {
			$this->set ( "collaboration", $clb_loaded [0] ["clb_projects"] ["clb"] );
			$this->loadModel ( "Colloberation" );
			
			if ($clb_loaded [0] ["clb_projects"] ["freelance"] == 1)
				$this->set ( "freelance", 1 );
			
			$Colloberation = new Colloberation ();
			$this->set ( "col_list", $Colloberation->getAllProject ( $project_id ) );
		}
		
		if ($jobid) {
			
			$job_loaded = $this->Job->find ( "first", array (
					"conditions" => array (
							"id" => $jobid 
					) 
			) );
			$this->set ( "JobData", $job_loaded );
		}
		
		$this->Job->bindModel ( array (
				'hasAndBelongsToMany' => array (
						'Skill' 
				),
				'hasMany' => array (
						'JobAttachment' 
				) 
		), false );
		$this->loadModel ( 'Category' );
		if (! empty ( $this->request->data )) {
			// check for rtoken DAta L
			
			if (! isset ( $this->request->params ['data'] ['_Token'] ['key'] ) || ($this->request->params ['data'] ['_Token'] ['key'] != $this->request->params ['_Token'] ['key'])) {
				$blackHoleCallback = $this->Security->blackHoleCallback;
				$this->$blackHoleCallback ();
			}
			
			$this->$model->set ( $this->request->data );
			$this->$model->setValidation ( 'add_job_front' );
			if ($this->$model->validates ()) {
				$this->request->data ['Job'] ['status'] = 1;
				$this->request->data ['Job'] ['user_id'] = $this->Auth->User ( 'id' );
				// pr($this->request->data); die;
				
				if (isset ( $this->request->data ["update"] )) {
					$this->request->data ["Job"] ["id"] = $this->request->data ["update"];
					$this->request->data ["Job"] ["status"] = 1;
					// Send  update Messages To aLL  Applied Users :   
					  $jobmodel  =  new JobBid() ;  
					  $bids =   $jobmodel->find("all",  array("conditions"=>array("job_id"=>$id)));   
					  foreach($bids as $bif){
					  	$userBif1  =  new User() ; 
					  	$userBif  =  $userBif1 ->find("first",array("conditions"=>array("id"=>$bif["JobBid"]["user_id"]))) ; 
					  	$text =  JOB_CHANGED_MESSAGE  ;   
					  //	$text =  str_replace("{username}",  $text["User"]["username"],  $text) ; 
					  $text =  str_replace("{username}",  "username",  $text) ; 
					  	$text =  str_replace("{job}",  "<a href='".SITE_URL."/jobs/job_detail/{$id}'> " .$this->request->data ['Job'] ['title']  . "</a> " ,  $text) ;
					  	$this->sendMail( $userBif["User"]["email"], "Job Details Updated",  $text,  SITE_EMAIL) ; 
					  	 
					  	
					  	
					  	
					  	
					  } 
				} else {
					$this->request->data ["Job"] ["status"] = 0;
				}
				
				if ($this->$model->saveAll ( $this->request->data )) {
					$id = $this->Job->id;
					$this->Session->write ( "temp_job", $id );
					
					$project_id = $this->request->data [$model] ['project_id'];
					$activationKey = mt_rand ( 1000000, 10000000 ) . $id;
					$this->Job->updateAll ( array (
							'Job.job_identification_no_no' => $activationKey 
					), array (
							'Job.id' => $id 
					) );
					
					@parent::__copy_directory ( JOB_ATTACHEMENT_PATH_DEFAULT, JOB_ATTACHEMENT_PATH . $id );
					
					if (empty ( $this->request->data ['Job'] ['JobAttachment'] )) {
						// copy file from project temp to job folder
						@parent::__copy_directory ( PROJECT_TEMP_THUMB_DIR_FILE, str_replace ( '{job_id}', $id, JOB_ATTACHEMENT_PATH_FOLDER ) );
						// unlink all temp folder image and file
						$oldfiles = $this->FileTemp->find ( "all", array (
								"conditions" => array (
										"FileTemp.user_id" => $this->Auth->User ( 'id' ) 
								),
								'fields' => 'FileTemp.project_file' 
						) );
						if (isset ( $oldfiles ) && ! empty ( $oldfiles )) {
							
							foreach ( $oldfiles as $oldfile ) {
								unlink ( PROJECT_TEMP_THUMB_DIR_FILE . $oldfile ['FileTemp'] ['project_file'] );
							}
						}
						// delete project temp, image temp table data
						$this->FileTemp->deleteAll ( array (
								'FileTemp.user_id' => $this->Session->read ( 'Auth.User.id' ) 
						) );
					}
					
					// allow next step
					$this->Session->write ( "job_general", "allow" );
					$this->Session->write ( "job_timeline", "allow" );
					// Update Job collaboration
					if (isset ( $_POST ["collaboration"] )) {
						// save she
						$this->loadModel ( "Colloberation" );
						$Colloberation = new Colloberation ();
						$Colloberation->query ( "INSERT INTO  clb_job SET job_id='{$id}' , clb='{$_POST["collaboration"]}'  " );
					}
					
					$this->Session->setFlash ( __ ( ' ' . $model . ' general has been posted successfully.' ), 'default', array (
							"class" => "success" 
					) );
					$this->redirect ( array (
							'controller' => 'jobs',
							'action' => 'job_timeline',
							$id 
					) );
				} else {
					
					$this->Session->setFlash ( __ ( 'The ' . $model . ' could not be saved. Please, try again.' ), 'default', array (
							"class" => "error" 
					) );
				}
			} else {
				
				// print_r($this->$model->invalidFields()) ;
				
				// pr($this->request->data);die;
				$jobfile = $this->JobAttachment->find ( 'all', array (
						'fields' => array (
								'file_name',
								'id',
								'job_id',
								'created' 
						),
						'conditions' => array (
								'JobAttachment.job_id' => $this->request->data ['Job'] ['id'] 
						) 
				) );
				$this->set ( compact ( 'jobfile' ) );
				$this->Session->setFlash ( __ ( 'Please correct the error listed below.' ), 'default', array (
						"class" => "error" 
				) );
			}
		} else {
			
			$jobfile = $this->JobAttachment->find ( 'all', array (
					'fields' => array (
							'file_name',
							'id',
							'job_id',
							'created' 
					),
					'conditions' => array (
							'JobAttachment.job_id' => $id 
					) 
			) );
			$this->set ( compact ( 'jobfile' ) );
			
			$this->request->data = $this->Job->read ( null, $id );
			// pr($this->request->data);
		}
		
		$job_file = $this->FileTemp->find ( 'all', array (
				'fields' => array (
						'project_file',
						'id',
						'file_name' 
				),
				'conditions' => array (
						'FileTemp.user_id' => $this->Auth->User ( 'id' ) 
				) 
		) );
		
		$sub_categories = array ();
		if (isset ( $this->request->data ['Job'] ['category_id'] ) && ! empty ( $this->request->data ['Job'] ['category_id'] )) {
			$sub_categories = $this->Category->find ( 'list', array (
					'conditions' => array (
							'Category.status' => Configure::read ( 'App.Status.active' ),
							'Category.type_for' => Configure::read ( 'App.Category.Job' ),
							'Category.parent_id' => $this->request->data ['Job'] ['category_id'] 
					) 
			) );
		}
		
		$skills = array ();
		if (isset ( $this->request->data ['Job'] ['category_id'] ) && ! empty ( $this->request->data ['Job'] ['category_id'] )) {
			$skills = $this->Skill->get_skills ( 'list', 'Skill.name', array (
					'Skill.category_id' => $this->request->data ['Job'] ['category_id'] 
			) );
		}
		$this->Set ( "counter", count ( $skills ) );
		
		$this->set ( compact ( 'project_id', 'skills', 'sub_categories' ) );
		$this->used_params ();
		$states = array ();
		$countries = array ();
		if (! empty ( $this->request->data ['Job'] ['region_id'] )) {
			$this->loadModel ( 'Country' );
			$region_id = $this->request->data ['Job'] ['region_id'];
			$countries = $this->Country->find ( 'list', array (
					'conditions' => array (
							'Country.region_id' => $region_id 
					) 
			) );
		}
		if (! empty ( $this->request->data ['Job'] ['country_id'] )) {
			$this->loadModel ( 'State' );
			$country_id = $this->request->data ['Job'] ['country_id'];
			$states = $this->State->find ( 'list', array (
					'conditions' => array (
							'State.country_id' => $country_id 
					) 
			) );
		}
		
		$project = $this->Project->find ( "first", array (
				"conditions" => array (
						"id" => $project_id 
				) 
		) );
		$this->set ( "project_name", $project ["Project"] ["title"] );
		
		$this->set ( compact ( 'countries', 'states', 'sub_categories', 'project_id', 'job_file' ) );
	}
	
	// NExt Step is Job Milestones
	//
	public function add_Job_milestone() {
		if ($this->RequestHandler->isAjax ()) {
			$this->loadModel ( 'JobMilestone' );
			$test_flag = '';
			if (! empty ( $this->request->data )) {
				
				$this->JobMilestone->set ( $this->request->data );
				$this->JobMilestone->setValidation ( 'add_job_milestone' );
				$id = $this->request->data ['JobMilestone'] ['job_id'];
				// die;
				if ($this->JobMilestone->validates ()) {
					
					if ($this->JobMilestone->saveAll ( $this->request->data )) {
						$test_flag = 'success';
					}
				} else {
					$test_flag = 'validation';
				}
				$this->set ( compact ( 'test_flag', 'id' ) );
				$this->render ( '/Elements/Front/ele_add_job_milestone' );
			} else {
				$this->request->data = $this->JobMilestone->read ( null, $this->params ['pass'] [0] );
				$sendArray ['content'] = $this->request->data;
				echo json_encode ( $sendArray );
			}
		}
	}
	public function update_job_milestone($id) {
		if ($this->RequestHandler->isAjax ()) {
			$this->loadModel ( 'JobMilestone' );
			$Jobmilestones = $this->JobMilestone->find ( 'all', array (
					'conditions' => array (
							'JobMilestone.job_id' => $id 
					) 
			) );
			$this->set ( compact ( 'Jobmilestones' ) );
			$this->render ( '/Elements/Front/ele_job_milestone_table' );
		}
	}
	public function delete_project_milestone($id) {
		$this->layout = false;
		$this->autoRender = false;
		$this->loadModel ( 'JobMilestone' );
		
		if (! empty ( $id )) {
			$this->JobMilestone->id = $id;
			$this->JobMilestone->delete ();
			echo '<script> window.location = SiteUrl+"/jobs/job_timeline"; </script>';
		}
	}
	
	/*
	 * Job timeline Herte
	 */
	public function job_timeline($id = NULL) {
		$model = $this->model;
		if ($id == null && $this->Session->read ( "temp_job" ) != "")
			$id = $this->Session->read ( "temp_job" );
		$this->set ( 'title_for_layout', 'Job Timeline' );
		$this->layout = 'lay_my_project_job';
		$this->loadModel ( 'Availability' );
		$this->loadModel ( 'JobMilestone' );
		$validation = false;
		$this->set ( "job_id", $id );
		$this->Job->bindModel ( array (
				'hasMany' => array (
						'JobMilestone' 
				) 
		), false );
		$Jobmilestones = $this->JobMilestone->find ( 'all', array (
				'conditions' => array (
						'JobMilestone.job_id' => $id 
				) 
		) );
		
		$job_loaded = $this->Job->find ( "first", array (
				"conditions" => array (
						"id" => $id 
				) 
		) );
		$this->set ( "project_id", $job_loaded ["Job"] ["project_id"] );
		
		if (! empty ( $this->request->data )) {
			// pr($this->request->data);die;
			if (! isset ( $this->request->params ['data'] ['_Token'] ['key'] ) || ($this->request->params ['data'] ['_Token'] ['key'] != $this->request->params ['_Token'] ['key'])) {
				$blackHoleCallback = $this->Security->blackHoleCallback;
				$this->$blackHoleCallback ();
			}
			$this->$model->set ( $this->request->data );
			$this->$model->setValidation ( 'add_job_front' );
			$this->$model->JobMilestone->setValidation ( 'add_job_milestone' );
			if ($this->$model->saveAll ( $this->request->data, array (
					'validate' => 'only' 
			) )) {
				
				if ($this->$model->saveAll ( $this->request->data )) {
					
					$this->Session->write ( "job_compensation", "allow" );
					
					$this->Session->setFlash ( __ ( ' ' . $model . ' timeline has been posted successfully.' ), 'default', array (
							"class" => "success" 
					) );
					$this->redirect ( array (
							'controller' => 'jobs',
							'action' => 'job_compensation',
							$id 
					) );
				} else {
					$validation = true;
					$this->Session->setFlash ( __ ( 'The ' . $model . ' could not be saved. Please, try again.' ), 'default', array (
							"class" => "error" 
					) );
				}
			} else {
				$validation = true;
				// $this->Session->setFlash(__('The '.$model.' could not be saved. Please, correct errors.'),'default',array("class"=>"error"));
			}
		} else {
			$validation = false;
			$this->request->data = $this->Job->read ( null, $id );
		}
		
		$this->loadModel ( "Job" );
		$project_id = $this->Job->find ( "first", array (
				"conditions" => array (
						"id" => $id 
				) 
		) );
		
		$project = $this->Project->find ( "first", array (
				"conditions" => array (
						"id" => $project_id ["Job"] ["project_id"] 
				) 
		) );
		$this->set ( "project_name", $project ["Project"] ["title"] );
		
		$availablity = $this->Availability->get_project_manager_availability ();
		$this->set ( compact ( 'id', 'availablity', 'Jobmilestones', 'validation' ) );
		$this->used_params ();
	}
	
	/*
	 * Upload job file
	 */
	public function job_fileupload() {
		$this->loadModel ( 'FileTemp' );
		
		if (isset ( $this->request->data ["FileTemp"] ['project_file'] ['tmp_name'] )) {
			
			if (! empty ( $this->request->data ["FileTemp"] ['project_file'] ['tmp_name'] )) {
				$file_array = $this->request->data ["FileTemp"] ['project_file'];
				$this->request->data ["FileTemp"] ['project_file'] = $this->request->data ["FileTemp"] ['project_file'] ['name'];
			}
			
			if (! empty ( $file_array )) {
				$file_name = time ();
				$n = explode ( ".", $this->request->data ["JobFileTemp"] ['job_file'] );
				$file_name = array_shift ( $n );
				$file_name = str_replace ( " ", "_", $file_name );
				/* this is being used to upload user big size profile image */
				
				$filename = parent::__upload ( $file_array, PROJECT_TEMP_THUMB_DIR_FILE, $file_name );
			}
		}
		$name = $this->request->data ["FileTemp"] ['project_file'];
		$this->request->data ['FileTemp'] ['user_id'] = $this->Auth->User ( 'id' );
		$this->request->data ['FileTemp'] ['project_file'] = $filename;
		$this->request->data ['FileTemp'] ['file_name'] = $name;
		
		if (! empty ( $this->request->data )) {
			$avataruploaded = $this->FileTemp->saveAll ( $this->request->data );
			$lastInsert_id = $this->FileTemp->id;
			$created = date ( 'F j, Y, g:i a', strtotime ( $this->FileTemp->field ( 'created' ) ) );
			if ($avataruploaded) {
				echo "success|" . $name . "|" . $lastInsert_id . "|" . $created;
			} else {
				echo "failed";
			}
		}
		die ();
	}
	public function delete_job_file($id) {
		$this->layout = false;
		$this->autoRender = false;
		$this->loadModel ( 'FileTemp' );
		$temp = $this->FileTemp->find ( 'first', array (
				'conditions' => array (
						'FileTemp.id' => $id 
				) 
		) );
		$file = $temp ['FileTemp'] ['project_file'];
		$this->FileTemp->id = $id;
		unlink ( PROJECT_TEMP_THUMB_DIR_FILE . $file );
		$this->FileTemp->delete ();
	}
	public function download_job_file($id) {
		$this->layout = false;
		$this->loadModel ( 'FileTemp' );
		$data_file = $this->FileTemp->find ( 'first', array (
				'conditions' => array (
						'FileTemp.id' => $id 
				) 
		) );
		$fullPath = 'img/' . PROJECT_TEMP_THUMB_DIR_FILE_VIEW . $data_file ['FileTemp'] ['project_file'];
		
		if ($fd = fopen ( $fullPath, "r" )) {
			$fsize = filesize ( $fullPath );
			$path_parts = pathinfo ( $fullPath );
			$ext = strtolower ( $path_parts ["extension"] );
			// $fl = $fullPath;
			header ( 'Content-Description: File Transfer' );
			header ( 'Content-Disposition: attachment; filename=' . str_replace ( " ", "_", $data_file ['FileTemp'] ['file_name'] ) );
			header ( 'Content-Transfer-Encoding: binary' );
			header ( 'Expires: 0' );
			header ( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header ( 'Pragma: public' );
			header ( 'Content-Length: ' . filesize ( $fullPath ) );
			ob_clean ();
			flush ();
			readfile ( $fullPath );
			exit ();
		}
	}
	public function delete_job_compensation_edit_file() {
		$this->layout = false;
		$this->autoRender = false;
		$this->loadModel ( 'JobFile' );
		$temp = $this->JobFile->find ( 'first', array (
				'conditions' => array (
						'JobFile.id' => $this->params ['pass'] [1] 
				) 
		) );
		$file = $temp ['JobFile'] ['file_name'];
		$this->JobFile->id = $temp ['JobFile'] ['id'];
		unlink ( str_replace ( '{job_id}', $this->params ['pass'] [0], JOB_FILES_PATH_FOLDER ) . '/' . $file );
		$this->JobFile->delete ();
	}
	public function download_job_compensation_edit_file() {
		$this->layout = false;
		$this->loadModel ( 'JobFile' );
		$data_file = $this->JobFile->find ( 'first', array (
				'conditions' => array (
						'JobFile.id' => $this->params ['pass'] [1] 
				) 
		) );
		$fullPath = str_replace ( '{job_id}', $this->params ['pass'] [0], JOB_FILES_PATH_FOLDER ) . '/' . $data_file ['JobFile'] ['file_name'];
		if ($fd = fopen ( $fullPath, "r" )) {
			$fsize = filesize ( $fullPath );
			$path_parts = pathinfo ( $fullPath );
			$ext = strtolower ( $path_parts ["extension"] );
			// $fl = $fullPath;
			header ( 'Content-Description: File Transfer' );
			header ( 'Content-Disposition: attachment; filename=' . basename ( $fullPath ) );
			header ( 'Content-Transfer-Encoding: binary' );
			header ( 'Expires: 0' );
			header ( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header ( 'Pragma: public' );
			header ( 'Content-Length: ' . filesize ( $fullPath ) );
			ob_clean ();
			flush ();
			readfile ( $fullPath );
			exit ();
		}
	}
	
	/*
	 * Job Compensation Goes Here :
	 */
	public function job_compensation($id = NULL) {
		if (empty ( $this->request->data ['Job'] ['id'] )) {
			parent::remove_temp_files_of_job ();
		}
		
		if ($id == null && $this->Session->read ( "temp_job" ) != "")
			$id = $this->Session->read ( "temp_job" );
		
		$model = $this->model;
		$this->layout = 'lay_my_project_job';
		$this->set ( 'title_for_layout', 'Job Compensation' );
		$this->loadModel ( 'FileTemp' );
		$this->loadModel ( 'JobFile' );
		$this->Job->bindModel ( array (
				'hasMany' => array (
						'JobFile' 
				) 
		), false );
		
		if (! empty ( $this->request->data )) {
			
			if (! isset ( $this->request->params ['data'] ['_Token'] ['key'] ) || ($this->request->params ['data'] ['_Token'] ['key'] != $this->request->params ['_Token'] ['key'])) {
				$blackHoleCallback = $this->Security->blackHoleCallback;
				$this->$blackHoleCallback ();
			}
			
			$this->$model->set ( $this->request->data );
			$this->$model->setValidation ( 'add_job_front' );
			
			if ($this->$model->validates ()) {
				
				// Create new Workroom for The job Here :
				$this->loadModel ( "Workroom" );
				
				if ($this->$model->saveAll ( $this->request->data )) {
					$id = $this->$model->id;
					$w = new Workroom ();
					$this->loadModel ( "Job" );
					$project_id = $this->Job->find ( "first", array (
							"conditions" => array (
									"id" => $id 
							) 
					) );
					
					$r = $w->createforJob ( $this->Auth->user ( "id" ), $id, $project_id ["Job"] ["project_id"] );
					$w->query ( "UPDATE jobs SET work_room='{$r}'  WHERE id ='{$id}' " );
					// Update Job Status
					//
					$w->query ( "UPDATE jobs SET status= '1' WHERE id ='{$id}' " );
					
					$w->query ( "UPDATE jobs SET cash_value='{$this->request->data["Job"]["cash_value"]}' , future_value='" . $this->request->data ["Job"] ["future_value"] . "'  WHERE id ='{$id}' " );
					
					// echo $this->request->data['JobFile']['file_name'];die;
					if (empty ( $this->request->data ['JobFile'] ['file_name'] )) {
						// copy file from project temp to job folder
						@parent::__copy_directory ( PROJECT_TEMP_THUMB_DIR_FILE, str_replace ( '{job_id}', $id, JOB_FILES_PATH_FOLDER ) );
						// unlink all temp folder image and file
						$oldfiles = $this->FileTemp->find ( "all", array (
								"conditions" => array (
										"FileTemp.user_id" => $this->Auth->User ( 'id' ) 
								),
								'fields' => 'FileTemp.project_file' 
						) );
						if (isset ( $oldfiles ) && ! empty ( $oldfiles )) {
							
							foreach ( $oldfiles as $oldfile ) {
								unlink ( PROJECT_TEMP_THUMB_DIR_FILE . $oldfile ['FileTemp'] ['project_file'] );
							}
						}
						// delete project temp, image temp table data
						$this->FileTemp->deleteAll ( array (
								'FileTemp.user_id' => $this->Session->read ( 'Auth.User.id' ) 
						) );
					}
					
					$this->Session->delete ( "job_compensation" );
					$this->Session->delete ( "job_timeline" );
					$this->Session->delete ( "job_general" );
					
					// update :
					
					$this->Session->setFlash ( __ ( ' ' . $model . ' has been posted successfully.' ), 'default', array (
							"class" => "success" 
					) );
					$this->redirect ( array (
							'controller' => 'projects',
							'action' => 'my_project' 
					) );
				} else {
					
					$this->Session->setFlash ( __ ( 'The ' . $model . ' could not be saved. Please, try again.' ), 'default', array (
							"class" => "error" 
					) );
				}
			} else {
				$job_file = $this->FileTemp->find ( 'all', array (
						'fields' => array (
								'project_file',
								'id' 
						),
						'conditions' => array (
								'FileTemp.user_id' => $this->Auth->User ( 'id' ) 
						) 
				) );
				$this->Session->setFlash ( __ ( 'The ' . $model . ' could not be saved. Please, try again.' ), 'default', array (
						"class" => "error" 
				) );
			}
		} else {
			$jobfile = $this->JobFile->find ( 'all', array (
					'fields' => array (
							'file_name',
							'id',
							'job_id',
							'created' 
					),
					'conditions' => array (
							'JobFile.job_id' => $id 
					) 
			) );
			$this->set ( compact ( 'jobfile' ) );
			$this->request->data = $this->Job->read ( null, $id );
		}
		$this->loadModel ( "Job" );
		$project_id = $this->Job->find ( "first", array (
				"conditions" => array (
						"id" => $id 
				) 
		) );
		$this->set ( "job_id", $id );
		
		$project = $this->Project->find ( "first", array (
				"conditions" => array (
						"id" => $project_id ["Job"] ["project_id"] 
				) 
		) );
		$this->set ( "project_name", $project ["Project"] ["title"] );
		$job_file = $this->FileTemp->find ( 'all', array (
				'fields' => array (
						'project_file',
						'id' 
				),
				'conditions' => array (
						'FileTemp.user_id' => $this->Auth->User ( 'id' ) 
				) 
		) );
		$this->set ( compact ( 'job_file' ) );
	}
	
	/*
	 * Search Job 2014 pashkovdenis@gmail.com
	 */
	public function search_job() {
		$this->layout = 'lay_search_job';
		$paging = '';
		$this->set ( 'title_for_layout', 'Search Job' );
		$countries = array ();
		$conditions = array ();
		$orderby = array (
				'Job.id' => 'DESC' 
		);
		$states = array ();
		if (! isset ( $this->request->params ['named'] ['page'] )) {
			$this->Session->delete ( 'FrontSearch' );
			$this->Session->delete ( 'Url' );
		}
		$this->loadModel ( 'Agreement' );
		$this->loadModel ( 'Compensation' );
		$this->loadModel ( 'Availability' );
		$this->loadModel ( 'JobsSkill' );
		$this->loadModel ( 'UserDetail' );
		
		$this->User->bindModel ( array (
				'hasOne' => array (
						'UserDetail' 
				) 
		), false );
		$this->UserDetail->bindModel ( array (
				'belongsTo' => array (
						'Country' => array (
								'conditions' => array (
										'Country.status' => Configure::read ( 'App.Status.active' ) 
								) 
						) 
				) 
		), false );
		$this->Project->bindModel ( array (
				'belongsTo' => array (
						'User',
						'Category' => array (
								'className' => 'Category',
								'foreignKey' => 'category_id' 
						),
						'ProjectChildCategory' => array (
								'className' => 'Category',
								'foreignKey' => 'sub_category_id' 
						) 
				) 
		), false );
		
		$this->Job->bindModel ( array (
				'belongsTo' => array (
						'Project',
						'Category' => array (
								'className' => 'Category',
								'foreignKey' => 'category_id' 
						),
						'ChildCategory' => array (
								'className' => 'Category',
								'foreignKey' => 'sub_category_id' 
						),
						'Region',
						'Country',
						'State' 
				),
				'hasAndBelongsToMany' => array (
						'Skill' 
				) 
		), false );
		
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
		
		if (isset ( $this->request->data ['Paging'] ['page_no'] ) && ! empty ( $this->request->data ['Paging'] ['page_no'] )) {
			$paging = $this->request->data ['Paging'] ['page_no'];
		}
		
		if (! empty ( $this->request->data )) {
			
			$this->Session->delete ( 'FrontSearch' );
			$this->Session->delete ( 'Url' );
			
			App::uses ( 'Sanitize', 'Utility' );
			if (! empty ( $this->request->data ['Job'] ['keyword'] )) {
				$search_keyword = Sanitize::escape ( $this->request->data ['Job'] ['keyword'] );
				$this->Session->write ( 'FrontSearch.search_keyword', $search_keyword );
			}
			if (! empty ( $this->request->data ['Job'] ['name'] )) {
				$title = Sanitize::escape ( $this->request->data ['Job'] ['name'] );
				$this->Session->write ( 'FrontSearch.title', $title );
			}
			if (! empty ( $this->request->data ['Job'] ['region_id'] )) {
				$region_id = Sanitize::escape ( $this->request->data ['Job'] ['region_id'] );
				$this->Session->write ( 'FrontSearch.region_id', $region_id );
			}
			if (! empty ( $this->request->data ['Job'] ['country_id'] )) {
				$country_id = Sanitize::escape ( $this->request->data ['Job'] ['country_id'] );
				$this->Session->write ( 'FrontSearch.country_id', $country_id );
			}
			if (! empty ( $this->request->data ['Job'] ['state_id'] )) {
				$state_id = Sanitize::escape ( $this->request->data ['Job'] ['state_id'] );
				$this->Session->write ( 'FrontSearch.state_id', $state_id );
			}
			if (! empty ( $this->request->data ['Job'] ['hour_days'] )) {
				$hour_days = Sanitize::escape ( $this->request->data ['Job'] ['hour_days'] );
				$this->Session->write ( 'FrontSearch.hour_days', $hour_days );
			}
			if (! empty ( $this->request->data ['Job'] ['expert_availability_id'] )) {
				$expert_availability_id = $this->request->data ['Job'] ['expert_availability_id'];
				$this->Session->write ( 'FrontSearch.expert_availability_id', $expert_availability_id );
			}
			if (! empty ( $this->request->data ['Job'] ['expert_availability_to'] )) {
				$expert_availability_to = $this->request->data ['Job'] ['expert_availability_to'];
				$this->Session->write ( 'FrontSearch.expert_availability_to', $expert_availability_to );
			}
			if (! empty ( $this->request->data ['Job'] ['expert_availability_from'] )) {
				$expert_availability_from = $this->request->data ['Job'] ['expert_availability_from'];
				$this->Session->write ( 'FrontSearch.expert_availability_from', $expert_availability_from );
			}
			if (! empty ( $this->request->data ['Job'] ['refrence_budget_to'] )) {
				$refrence_budget_to = $this->request->data ['Job'] ['refrence_budget_to'];
				$this->Session->write ( 'FrontSearch.refrence_budget_to', $refrence_budget_to );
			}
			if (! empty ( $this->request->data ['Job'] ['refrence_budget_from'] )) {
				$refrence_budget_from = $this->request->data ['Job'] ['refrence_budget_from'];
				$this->Session->write ( 'FrontSearch.refrence_budget_from', $refrence_budget_from );
			}
			
			if (! empty ( $this->request->data ['Job'] ['category_id'] )) {
				$category_id = $this->request->data ['Job'] ['category_id'];
				$this->Session->write ( 'FrontSearch.category_id', $category_id );
			}
			if (! empty ( $this->request->data ['Job'] ['sub_category_id'] )) {
				$sub_category_id = $this->request->data ['Job'] ['sub_category_id'];
				$this->Session->write ( 'FrontSearch.sub_category_id', $sub_category_id );
			}
			if (! empty ( $this->request->data ['Skill'] ['Skill'] )) {
				$jobid = $this->JobsSkill->find ( 'all', array (
						'fields' => array (
								'job_id' 
						),
						'conditions' => array (
								'JobsSkill.skill_id' => $this->request->data ['Skill'] ['Skill'] 
						) 
				) );
				
				foreach ( $jobid as $key => $job_id ) {
					$newJobId [] = $job_id ['JobsSkill'] ['job_id'];
				}
				
				$newJobId = array_values ( array_unique ( $newJobId ) );
				
				$this->Session->write ( 'FrontSearch.id', $newJobId );
			}
			if (! empty ( $this->request->data ['Job'] ['sortby'] ) && $this->request->data ['Job'] ['sortby'] == 'job_name') {
				
				$orderby = array (
						'Job.title' => 'ASC' 
				);
			}
			if (! empty ( $this->request->data ['Job'] ['sortby'] ) && $this->request->data ['Job'] ['sortby'] == 'posting_date') {
				$orderby = array (
						'Job.created' => 'ASC' 
				);
			}
		}
		
		if ($this->Session->check ( 'FrontSearch' )) {
			
			$keywords = $this->Session->read ( 'FrontSearch' );
			
			// pr($keywords);die;
			foreach ( $keywords as $key => $values ) {
				
				if ($key == 'search_keyword') {
					$conditions [] = array (
							'OR' => array (
									'Job.title LIKE' => "%" . $values . "%",
									'Job.description LIKE' => "%" . $values . "%" 
							) 
					);
				} else if ($key == 'title') {
					$conditions [] = array (
							'Job.' . $key . ' LIKE' => "%" . $values . "%" 
					);
				} else if ($key == 'hour_days') {
					
					if ($values == 24) {
						$new_date = parent::get_ago_date ( - 1 );
						$conditions [] = array (
								'DATE(Job.created) <=' => $new_date 
						);
					}
					if ($values == 3) {
						
						$new_date = parent::get_ago_date ( - 3 );
						$conditions [] = array (
								'DATE(Job.created) <=' => $new_date 
						);
					}
					if ($values == 7) {
						$new_date = parent::get_ago_date ( - 7 );
						$conditions [] = array (
								'DATE(Job.created) <=' => $new_date 
						);
					}
				} else if ($key == 'expert_availability_to') {
					
					$conditions [] = array (
							'Job.expert_availability >=' => $values 
					);
				} else if ($key == 'expert_availability_from') {
					
					$conditions [] = array (
							'Job.expert_availability <=' => $values 
					);
				} else if ($key == 'refrence_budget_to') {
					
					$conditions [] = array (
							'Job.refrence_budget >=' => $values 
					);
				} else if ($key == 'refrence_budget_from') {
					
					$conditions [] = array (
							'Job.refrence_budget <=' => $values 
					);
				} else {
					$conditions [] = array (
							'Job.' . $key => $values 
					);
				}
			}
		}
		
		$conditions [] = array (
				'Job.status' => 1 
		);
		$conditions [] = array (
				'Job.posting_visibility' => '0',
                                'Project.visibility'=>'1'
		); // Show onyl Public :
		
		$this->paginate = array (
				'limit' => Configure::read ( 'App.PageLimit' ),
				'order' => $orderby,
				'page' => $paging,
				'conditions' => $conditions,
				'contain' => array (
						'Project' => array (
								
								'User' => array (
										'fields' => array (
												'User.first_name',
												'User.last_name',
												'User.id',
												'User.username' 
										),
										'UserDetail' => array (
												'fields' => array (
														'UserDetail.country_id',
														'UserDetail.id' 
												),
												'Country' => array (
														'conditions' => array (
																'Country.status' => Configure::read ( 'App.Status.active' ) 
														),
														'fields' => array (
																'Country.name',
																'Country.country_flag' 
														) 
												) 
										) 
								)
								,
								
								'fields' => array (
										'Project.title',
										'Project.id',
										'Project.project_image' 
								),
								
								'Category' => array (
										'conditions' => array (
												'Category.status' => Configure::read ( 'App.Status.active' ) 
										),
										'fields' => array (
												'Category.name' 
										) 
								),
								'ProjectChildCategory' => array (
										'conditions' => array (
												'ProjectChildCategory.status' => Configure::read ( 'App.Status.active' ) 
										),
										'fields' => array (
												'ProjectChildCategory.name',
												'ProjectChildCategory.id' 
										) 
								) 
						),
						'Category' => array (
								'conditions' => array (
										'Category.status' => Configure::read ( 'App.Status.active' ) 
								),
								'fields' => array (
										'Category.id',
										'Category.parent_id',
										'Category.name' 
								) 
						),
						
						'ChildCategory' => array (
								'conditions' => array (
										'ChildCategory.status' => Configure::read ( 'App.Status.active' ) 
								),
								'fields' => array (
										'ChildCategory.name',
										'ChildCategory.id' 
								) 
						),
						
						'Skill' => array (
								'fields' => array (
										'Skill.id',
										'Skill.name' 
								) 
						),
						
						'Region' => array (
								'conditions' => array (
										'Region.status' => Configure::read ( 'App.Status.active' ) 
								),
								'fields' => array (
										'Region.name' 
								) 
						),
						'Country' => array (
								'conditions' => array (
										'Country.status' => Configure::read ( 'App.Status.active' ) 
								),
								'fields' => array (
										'Country.name',
										'Country.country_flag'
								) 
						),
						'State' => array (
								'conditions' => array (
										'State.status' => Configure::read ( 'App.Status.active' ) 
								),
								'fields' => array (
										'State.name' 
								) 
						) 
				) 
		);
		$job_list = $this->paginate ( 'Job' );
		
		$job_availability = $this->Availability->get_availability_front ();
		
		$job_compensation = $this->Compensation->get_compensation_front ();
		
		$job_aggreement_type = $this->Agreement->get_agreement_list ();
		
		$job_catgories = $this->Category->get_categories_front ( Configure::read ( 'App.Category.Job' ) );
		
		$job_skills = $this->Category->get_job_skills ();
		
		$region = $this->Region->getResionListForUserRegistraion ();
		
		if (! empty ( $this->request->data ['Job'] ['region_id'] )) {
			$countries = $this->Country->getCountryListByRegionId ( $this->request->data ['Job'] ['region_id'] );
		}
		if (! empty ( $this->request->data ['Job'] ['country_id'] )) {
			$states = $this->State->getStateList ( $this->request->data ['Job'] ['country_id'] );
		}
		
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
		
		$this->set ( compact ( 'job_list', 'job_catgories', 'region', 'countries', 'states', 'job_skills', 'job_aggreement_type', 'job_compensation', 'job_availability' ) );
		
		if ($this->request->is ( 'ajax' )) {
			$this->layout = false;
			$this->render ( '/Elements/Front/ele_search_job_right_sidebar' );
		}
	}
	
	/*
	 * My Jobs Controller Stack For Provider :
	 */
	public function decline($id = null) {
		$this->autoRender = false;
		$this->loadModel ( 'JobBid' );
		$this->loadModel ( "Project" );
		$this->loadModel ( "Users" );
		$this->loadModel ( "Template" );
		//
		$user_id = parent::__get_session_user_id ();
		if ($id) {
			$obj = $this->JobBid->find ( "first", array (
					"conditions" => array (
							"job_id" => $id,
							"user_id" => $user_id 
					) 
			) );
			$this->JobBid->query ( "DELETE FROM  job_bids WHERE user_id='{$user_id}' AND job_id='{$id}' ANd status='3'  " );
			$proj = $this->Project->find ( "first", array (
					"conditions" => array (
							"id" => $obj ["JobBid"] ["project_id"] 
					) 
			) );
			$user_id_proj = ($proj ["Project"] ["user_id"]);
			$user = $this->Users->find ( "first", array (
					"conditions" => array (
							"id" => $user_id_proj 
					) 
			) );
			
			$from = array (
					Configure::read ( 'App.AdminMail' ) => Configure::read ( 'Site.title' ) 
			);
			$mailMessage = '';
			$template = $this->Template->find ( 'first', array (
					'conditions' => array (
							'Template.slug' => 'decline_invi' 
					) 
			) );
			$mailMessage = str_replace ( array (
					'{user}',
					'{project}' 
			), array (
					$user ["Users"] ["username"],
					$proj ["Project"] ["title"] 
			), $template ['Template'] ['content'] );
			
			$email = new CakeEmail ( 'gmail' );
			$email->template ( 'default', "default" );
			$email->emailFormat ( 'html' );
			$email->from ( $from );
			$email->to ( $user ["Users"] ["email"] );
			$email->subject ( "Expert has decline Invitation." );
			$email->send ( $mailMessage );
		}
		$this->redirect ( array (
				'controller' => 'jobs',
				'action' => 'my_job' 
		) );
		die ();
	}
	
	/*
	 * My jobs Goes Here :
	 * 2014 pashkovdenis@gmail.com
	 */
	public function my_job() {
		$this->layout = 'lay_my_project_job';
		$this->set ( 'title_for_layout', 'My Jobs' );
		// Load Projects With Other Stuff :
		$this->loadModel ( 'Job' );
		$this->loadModel ( 'JobBid' );
		$this->loadModel ( "Workroom" );
		$this->loadModel ( "jobInvite" );
		$paging = '';
		$number_of_record = Configure::read ( 'App.PageLimit' );
		$user_id = parent::__get_session_user_id ();
		$this->Project->Behaviors->attach ( 'Containable' );
		if (isset ( $this->request->data ['Paging'] ['page_no'] ) && ! empty ( $this->request->data ['Paging'] ['page_no'] )) {
			$paging = $this->request->data ['Paging'] ['page_no'];
		}
		// Bind Model :
		$this->JobBid->bindModel ( array (
				'belongsTo' => array (
						'User' 
				) 
		), false );
		
		$this->Job->bindModel ( array (
				'hasMany' => array (
						'JobMilestone' => 

						array (
								'fields' => array (
										'JobMilestone.date',
										'JobMilestone.job_id',
										'JobMilestone.id' 
								),
								'order' => array (
										'JobMilestone.date' => 'ASC' 
								) 
						) 
				),
				'hasOne' => array (
						'JobBid' => array (
								'conditions' => array (
										'JobBid.user_id' => $user_id 
								),
								'fields' => array (
										'JobBid.user_id',
										'JobBid.id',
										'JobBid.job_id' 
								) 
						) 
				) 
		), false );
		
		$this->Project->bindModel ( array (
				'belongsTo' => array (
						'User' => array (
								'fields' => array (
										'User.id',
										'User.first_name',
										'User.last_name',
										'User.username' 
								) 
						) 
				),
				'hasMany' => array (
						'Job' => array (
								'conditions' => array (
										'Job.status' => array (
												Configure::read ( 'App.Job.Active' ),
												Configure::read ( 'App.Job.Awarded' ),
												Configure::read ( 'App.Job.Completed' ) 
										) 
								),
								'fields' => array (
										'Job.id',
										'Job.status',
										'Job.title',
										'Job.description' 
								) 
						),
						'ProjectMilestone' => array (
								'fields' => array (
										'ProjectMilestone.project_id',
										'ProjectMilestone.date' 
								),
								'order' => array (
										'ProjectMilestone.date' => 'ASC' 
								) 
						) 
				) 
		), false );
		
		$filters = array ();
		$project_ids = array ();
		$list = $this->JobBid->find ( "all", array (
				"conditions" => array (
						"user_id" => $user_id 
				) 
		) );
		
		$apply = array ();
		$last_status = 0;
		$job_status = array ();
		
		$this->set ( "invited", jobInvite::getInvited ( $this->Auth->user ( "id" ) ) );
		
		foreach ( $list as $pro ) {
			
			// Check Status For Job
			if ($pro ["JobBid"] ["status"] > $last_status && $last_status != 2) {
				$apply [$pro ["JobBid"] ["project_id"]] = $pro ["JobBid"] ["status"];
				$last_status = $pro ["JobBid"] ["status"];
			}
			
			$job_status [$pro ["JobBid"] ["job_id"]] = $pro ["JobBid"] ["status"];
			if (! in_array ( $pro ["JobBid"] ["project_id"], $project_ids ))
				$project_ids [] = $pro ["JobBid"] ["project_id"];
		}
		
		// Add invited PRojects :
		$invited = array_unique ( jobInvite::getProjectIds ( $this->Auth->user ( "id" ) ) );
		
		if (count ( $invited ) > 0) {
			$project_ids = array_merge ( $project_ids, $invited );
		}
		
		$filters [] = array (
				'Project.id' => $project_ids 
		);
		$this->set ( "apply", $apply );
		$this->set ( "job_status", $job_status ); // Job_Status stack
		
		$this->paginate = array (
				'limit' => $number_of_record,
				'order' => array (
						'Project.id' => 'Desc' 
				),
				'conditions' => $filters,
				'contain' => array (
						'User',
						'Job' => array (
								'JobMilestone',
								'JobBid' => array (
										'User' 
								) 
						),
						'ProjectMilestone' 
				),
				'page' => $paging 
		);
		
		$data = $this->paginate ( 'Project' );
		
		$this->set ( 'data', $data );
		$this->set ( 'current_user', $this->Auth->user ( "id" ) ); // Current User
		$this->set ( compact ( 'my_job_list' ) );
		if ($this->request->is ( 'ajax' )) {
			
			$this->layout = false;
			$this->render ( '/Elements/Front/ele_my_job' );
		}
	}
	
	/*
	 * View single job pashkovdenis@gmail.com 2013
	 */
	public function job_detail($id = null, $teamup = false) {
		$this->layout = 'lay_job_detail';
		$this->set ( 'title_for_layout', 'Job Detail' );
		$this->loadModel ( 'Job' );
		$this->Job->id = $id;
		$current_user = $this->Auth->user ( "id" );
		
		if (! $this->Job->exists ()) {
			throw new NotFoundException ( __ ( 'Invalid Job' ) );
		}
		
		$this->loadModel ( 'Agreement' );
		$this->loadModel ( 'Compensation' );
		$this->loadModel ( 'Availability' );
		$this->loadModel ( 'JobAttachment' );
		$this->loadModel ( 'JobFile' );
		$this->loadModel ( 'Category' );
		$this->loadModel ( 'WorkingStatus' );
		$this->loadModel ( 'Category' );
		$this->loadModel ( 'SkillsUser' );
		$this->loadModel ( 'UsersWorkingStatus' );
		$this->loadModel ( "Teamup" );
		$this->loadModel('Availability');
		$this->loadModel ( 'Job' );
		$this->loadModel ( 'JobBid' );
		$this->loadModel ( 'JobFileTemp' );
		$this->loadModel ( 'JobBidFile' );
		$this->loadModel ( "Workroom" );
		
		$this->loadModel ( "User" );
		$this->loadModel ( "Project" );
		
		$this->JobBid->bindModel ( array (
				'hasMany' => array (
						'JobBidFile' 
				) 
		), false );
		
		$orderby = array (
				'User.id' => 'DESC' 
		);
		
		$this->User->bindModel ( array (
				'hasOne' => array (
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
		), false );
		
		
		if ($teamup != false && isset ( $_POST ["chat"] ) == false) {
			$rt = $this->Session->read ( "tem" . $id );
			
			if ($this->Auth->user ( "id" ) && Teamup::isUp ( $id, $this->Auth->user ( "id" ) )) {
				$this->set ( "teamup_flag", true );
				$this->Session->write ( "tem" . $id, 1 );
			}
		}
		
		$this->set ( "job_id", $id );
		$this->set ( "to_user", $teamup );
		
		$this->Project->bindModel ( array (
				'belongsTo' => array (
						'User',
						'Category' => array (
								'className' => 'Category',
								'foreignKey' => 'category_id' 
						),
						'ProjectChildCategory' => array (
								'className' => 'Category',
								'foreignKey' => 'sub_category_id' 
						) 
				) 
		), false );
		
		$this->User->UserDetail->bindModel ( array (
				'belongsTo' => array (
						'Country' => array (
							'conditions' => array (
							 'Country.status' => Configure::read ( 'App.Status.active' )) 
						),
						'ExpertiseCategory'=>array(
							'className'=>'Category',
							'foreignKey'=>'expertise_category_id'
						), 
						'LeaderCategory'=>array(
							'className'=>'Category',
							'foreignKey'=>'leadership_category_id'
					) 
				) 
		), false );
		$this->Job->bindModel ( array (
				'belongsTo' => array (
						'Project',
						'Category' => array (
								'className' => 'Category',
								'foreignKey' => 'category_id' 
						),
						'ChildCategory' => array (
								'className' => 'Category',
								'foreignKey' => 'sub_category_id' 
						),
						'Region',
						'Country',
						'State' 
				),
				'hasAndBelongsToMany' => array (
						'Skill' 
				) 
		), false );
		
		$this->Job->Behaviors->attach ( 'Containable' );
		$data = $this->Job->find ( 'first', array (
				'conditions' => array (
						'Job.id' => $id 
				),
				'contain' => array (
						'Project' => array (
								'conditions' => array (
										'Project.status' => Configure::read ( 'App.Status.active' ) 
								),
								'fields' => array (
										'Project.title',
										'Project.id',
										'Project.user_id',
										'Project.project_image' 
								),
								'User' => array (
										'fields' => array (
												'User.first_name',
												'User.last_name',
												'User.id',
												'User.username'
										),
										'UserDetail' => array (
															   
												'Country' => array (
														'conditions' => array (
														'Country.status' => Configure::read ( 'App.Status.active' )),
														'fields' => array ('Country.name','Country.country_flag' ) 
												),
												'ExpertiseCategory'=>array(
													'conditions'=>array('ExpertiseCategory.status'=>Configure::read('App.Status.active')),
													'fields'=>array('ExpertiseCategory.name')
											    ),
												'LeaderCategory'=>array(
													'conditions'=>array('LeaderCategory.status'=>Configure::read('App.Status.active')),
													'fields'=>array('LeaderCategory.name')
												)
										)
								),
								'ProjectChildCategory' => array (
										'conditions' => array (
												'ProjectChildCategory.status' => Configure::read ( 'App.Status.active' ) 
										),
										'fields' => array (
												'ProjectChildCategory.name',
												'ProjectChildCategory.id' 
										) 
								),
								'Category' => array (
										'conditions' => array (
												'Category.status' => Configure::read ( 'App.Status.active' ) 
										),
										'fields' => array (
												'Category.name'
							
										) 
								) 
						),
						'Category' => array (
								'conditions' => array (
										'Category.status' => Configure::read ( 'App.Status.active' ) 
								),
								'fields' => array (
										'Category.id',
										'Category.parent_id',
										'Category.name'
										
								) 
						),
						'ChildCategory' => array (
								'conditions' => array (
										'ChildCategory.status' => Configure::read ( 'App.Status.active' ) 
								),
								'fields' => array (
										'ChildCategory.name',
										'ChildCategory.id' 
								) 
						),
						'Skill' => array (
								'conditions' => array (
										'Skill.status' => Configure::read ( 'App.Status.active' ) 
								),
								'fields' => array (
										'Skill.id',
										'Skill.name' 
								) 
						),
						'Region' => array (
								'conditions' => array (
										'Region.status' => Configure::read ( 'App.Status.active' ) 
								),
								'fields' => array (
										'Region.name' 
								) 
						),
						'Country' => array (
								'conditions' => array (
										'Country.status' => Configure::read ( 'App.Status.active' ) 
								),
								'fields' => array (
										'Country.name',
										'Country.country_flag' 
								) 
						),
						'State' => array (
								'conditions' => array (
										'State.status' => Configure::read ( 'App.Status.active' ) 
								),
								'fields' => array (
										'State.name' 
								) 
						) 
				) 
		) );
		
		$job_id = ($data ["Job"] ["id"]);
		$job_user_admin = $data ["Job"] ["user_id"];
		
		$this->loadModel ( "Duration" );
		$dur = new Duration ();
		// create Duration :
		$this->loadModel ( "Availability" );
		$av = new Availability ();
		
		// Preapre Lists of Jobs Here :
		
		//print_r($data );
		//echo $data ["Job"] ["category_id"];
	
			$r = $dur->find ( "first", array (
					"conditions" => array (
							"id" => $data ["Job"] ["duration_id"] 
					) 
			) );
			$c = $this->Category->find ( "first", array( 
					"conditions" => array(
							"Category.id" => $data ["Job"] ["category_id"] 
					) 
			) );
			$jobs1 ["Job"] ["duration"] = $r ["Duration"] ["name"];
			$jobs1 ["Job"] ["cat_name"] = $c ["Category"] ["name"];
			$avail = $av->find ( "first", array( 
					"conditions" => array( 
							"id" => $data ["Job"] ["expert_availability_id"] 
					) 
			) );
			$jobs1 ["Job"] ["avail"] = $avail ["Availability"] ["name"];
			$this->set("jobs1",$jobs1);
		
		$this->loadModel ( "JobBid" );
		$bids = new JobBid ();
		$users_to_load = array ();
		
		$select = $bids->find ( "all", array (
				"conditions" => array (
						"job_id" => $id 
				) 
		) );
		
		$check_uer = array ();
		
		foreach ( $select as $k => $ob )
			$check_uer [] = $ob ["JobBid"] ["user_id"];
		
		$clear_true = false;
		
		
		if ($current_user != $job_user_admin && in_array ( $current_user, $check_uer ))
			$clear_true = true;
		
		foreach ( $select as $k => $ob ) {
			
			$users_to_load [] = $ob ["JobBid"] ["user_id"];
			
			$user = new User ();
			$j = $user->find ( "first", array (
					"conditions" => array (
							"id" => $ob ["JobBid"] ["user_id"] 
					) 
			) );
			
			
				
			// Select Work Rom
			$w = $user->query ( "SELECT id FROM workroom WHERE user_id ='{$ob["JobBid"]["user_id"]}' AND  job_id = '{$ob["JobBid"]["job_id"]}'      " );
			$check = $this->Project->find ( "count", array (
					"conditions" => array (
							"Project.id" => $ob ["JobBid"] ["project_id"],
							"user_id" => $this->Auth->user ( "id" ) 
					) 
			) );
			
			
			
			if ($check > 0)
				$select [$k] ["up"] = 1;
			else
				$select [$k] ["up"] = 0;
			
			$c = $user->query ( "SELECT COUNT(*) as c FROM  teamup_jobs  WHERE job_id='{$ob["JobBid"]["job_id"]}'  AND  to_user='{$ob["JobBid"]["user_id"]}' " );
			if (isset ( $c [0] [0] ["c"] ) && $c [0] [0] ["c"] > 0)
				$select [$k] ["up"] = 0;
			
			$select [$k] ["User"] = array_shift ( $j );
			if (isset ( $w [0] ["workroom"] ["id"] ))
				$select [$k] ["User"] ["room"] = $w [0] ["workroom"] ["id"];
		}
		$this->loadModel("Availability")  ;
		 
		
		
		
		
		$this->set ( "bids", $select );
		$job_attachement = $this->JobAttachment->find ( 'all', array (
				'conditions' => array (
						'JobAttachment.job_id' => $id 
				) 
		) );
		$job_file = $this->JobFile->find ( 'all', array (
				'conditions' => array (
						'JobFile.job_id' => $id 
				) 
		) );
		
		// Set Contract Page Fro Stuff :
		$this->loadModel ( "Teamup" );
		$teamup = new Teamup ();
		$count = $teamup->find ( "count", array (
				"conditions" => array (
						"job_id" => $id 
				) 
		) );
		
		if ($count > 0) {
			
			$team = $teamup->find ( "first", array (
					"conditions" => array (
							"job_id" => $id 
					) 
			) );
			$this->set ( "teamup", "/teamup/milestones/{$id}/{$team["Teamup"]["to_user"]}/{$team["Teamup"]["id"]}" );
		}
		
		// Load Bids Here :
		// 2013
		
		$this->User->bindModel ( array (
				'hasOne' => array (
						'UserDetail' 
				),
				'hasAndBelongsToMany' => array (
						'Skill' 
				) 
		), false );
		
		$this->User->UserDetail->bindModel ( array (
				'belongsTo' => array (
						'Country',
						'ExpertiseCategory' => array (
								'className' => 'Category',
								'foreignKey' => 'expertise_category_id' 
						),
						'LeaderCategory'=>array(
							'className'=>'Category',
							'foreignKey'=>'leadership_category_id'
						) 
						
				) 
		), false );
		
		$this->User->Behaviors->attach ( 'Containable' );
		$this->paginate = array (
				'conditions' => array (
						"User.id" => $users_to_load 
				),
				'limit' => Configure::read ( 'App.PageLimit' ),
				'order' => $orderby,
				'page' => 1,
				'contain' => array (
						
						'UserDetail' => array (
								'fields' => array (
										'UserDetail.about_us',
										'UserDetail.user_id',
										'UserDetail.max_reference_rate',
										'UserDetail.min_reference_rate',
                                                                                'UserDetail.gender'
								),
								'Country' => array (
										'conditions' => array (
												'Country.status' => Configure::read ( 'App.Status.active' ) 
										),
										'fields' => array (
												'Country.name',
												'Country.country_flag' 
										) 
								),'ExpertiseCategory'=>array(
													'conditions'=>array('ExpertiseCategory.status'=>Configure::read('App.Status.active')),
													'fields'=>array('ExpertiseCategory.name')
											    ),
												'LeaderCategory'=>array(
													'conditions'=>array('LeaderCategory.status'=>Configure::read('App.Status.active')),
													'fields'=>array('LeaderCategory.name')
												)
							 					
						),
						'Skill' => array (
								'conditions' => array (
										'Skill.status' => Configure::read ( 'App.Status.active' ) 
								) 
						) 
				)
				,
				'fields' => array (
						'User.id',
						'User.role_id',
						'UserDetail.image',
						'User.first_name',
						'User.last_name',
					
				) 
		);
		$data2 = $this->paginate ( 'User' );
		
		
		foreach ( $select as $k => $ob ) {
			
			$user = new User ();
			$j = $user->find ( "first", array (
					"conditions" => array (
							"id" => $ob ["JobBid"] ["user_id"] 
					) 
			) );
			
			
			
			$files = $user->query ( "select job_bid_file FROM job_bid_files WHERE job_bid_id ='{$ob["JobBid"]["id"]}' " );
			$w = $user->query ( "SELECT id FROM workroom WHERE user_id ='{$ob["JobBid"]["user_id"]}' AND  job_id = '{$ob["JobBid"]["job_id"]}'      " );
			$check = $this->Project->find ( "count", array (
					"conditions" => array (
							"Project.id" => $ob ["JobBid"] ["project_id"],
							"user_id" => $this->Auth->user ( "id" ) 
					) 
			) );
			if ($check > 0)
				$data2 [$k] ["up"] = 1;
			else
				$data2 [$k] ["up"] = 0;
			
			$c = $user->query ( "SELECT COUNT(*) as c FROM  teamup_jobs  WHERE job_id='{$ob["JobBid"]["job_id"]}'  AND  to_user='{$ob["JobBid"]["user_id"]}' " );
			if ($c [0] [0] ["c"] > 0)
				$data2 [$k] ["up"] = 0;
				
				// Load Bids Files :
			$files_list = array ();
			foreach ( $files as $f )
				$files_list [] = $f ["job_bid_files"] ["job_bid_file"];
			$data2 [$k] ["bid_id"] = $ob ["JobBid"] ["id"];
			
			$data2 [$k] ["User"] = array_shift ( $j );
			if (isset ( $w [0] ["workroom"] ["id"] ))
				$data2 [$k] ["User"] ["room"] = $w [0] ["workroom"] ["id"];
			$data2 [$k] ["Files"] = $files_list;
		}
		
		// Data 2 :
		$this->set ( "jobs_count", count ( $data2 ) );
		
		$hide = true;
		
		if ($current_user != $data ["Job"] ["user_id"] && in_array ( $current_user, $users_to_load ) == false)
			$hide = false;
		
		if ($current_user != "") {
			foreach ( $data2 as $i => $v ) {
				
				if ($clear_true) {
					
					if ($v ["User"] ["id"] != $current_user) {
						
						unset ( $data2 [$i] );
					}
				}
			}
		}
		$this->loadModel ( 'Availability' );
		$data_avail= array();
			$data_num_pro= array();
			
			$this->loadModel('WorkingStatus');
			$data_value1=array();
		
		if ($this->Auth->user ( "id" ) != "" && $hide){
			$this->set ( "data_bid", $data2 );
			
			foreach($data2 as $data_key=>$data_value)
			{
				$data_num_pro[$data_key]["projects_num"]  =  User::getNumberExpertProject($data_value["User"]["id"]) ; 
				
				$avail  = $this->User->query("SELECT availability_id FROM  user_details WHERE user_id='{$data_value["User"]["id"]}' ");
				//$model =  (new Availability())->find("first" , array("conditions"=>array("id"=>$avail[0]["user_details"]["availability_id"])));
				$model =  new Availability();
				$model1=$model->find("first" , array("conditions"=>array("id"=>$avail[0]["user_details"]["availability_id"])));
				if (!empty($model1["Availability"]["name"]))
					 $data_avail[$data_key]["avail"] =   $model1["Availability"]["name"];
				else
					 $data_avail[$data_key]["avail"] = "N/A" ; 
					 
					 
					  
			 $status_id = $this->User->query("SELECT working_status_id FROM users_working_statuses WHERE user_id = '{$data_value["User"]["id"]}' ");
			 
			$leaderData=array();	
			if (isset($status_id[0]["users_working_statuses"])){
				$status =   $this->User->query("SELECT name FROM working_statuses WHERE id  = '{$status_id[0]["users_working_statuses"]["working_status_id"]}' ") ;
					$leaderData["status"] =  $status[0]["working_statuses"]["name"]  ; }else
				{
					$data_value1["status"] = "";
				}
				 
			
				$data_value1["status"]=$leaderData["status"]	;
					
					
			}
			
			
		}
			$this->set ( "data_value1", $data_value1);
			$this->set ( "data_avail", $data_avail );
			$this->set ( "noof_projects", $data_num_pro );
		//print_r($data2);	
		$jobFileTemps2 = $this->JobFileTemp->find ( 'all', array (
				'conditions' => array (
						'JobFileTemp.user_id' => $this->Auth->user ( "id" ) 
				),
				'fields' => array (
						'job_file',
						'id' 
				) 
		) );
		
		 
		
		$this->set ( "jobFileTemps", $jobFileTemps2 );
		$this->set ( compact ( 'data', 'job_attachement', 'job_file' ) );
	}
	
	
	
	
	
	
	public function getfileApply($bid, $file) {
		$this->autoRender = false;
		
		$fullPath = 'img/job_bid_file/' . $bid . "/bid_files/" . $file;
		
		if ($fd = fopen ( $fullPath, "r" )) {
			$fsize = filesize ( $fullPath );
			
			header ( 'Content-Description: File Transfer' );
			header ( 'Content-Type: application/octet-stream' );
			header ( 'Content-Disposition: attachment; filename=' . $file );
			header ( 'Content-Transfer-Encoding: binary' );
			header ( 'Expires: 0' );
			header ( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header ( 'Pragma: public' );
			header ( 'Content-Length: ' . filesize ( $fullPath ) );
			// ob_clean();
			flush ();
			readfile ( $fullPath );
			exit ();
		}
	}
	
	/*
	 * File File BY Job Apply Form : 2014 :
	 */
	public function job_apply_fileupload() {
		$this->loadModel ( 'JobFileTemp' );
		if (isset ( $this->request->data ["JobFileTemp"] ['job_file'] ['tmp_name'] )) {
			if (! empty ( $this->request->data ["JobFileTemp"] ['job_file'] ['tmp_name'] )) {
				$file_array = $this->request->data ["JobFileTemp"] ['job_file'];
				$this->request->data ["JobFileTemp"] ['job_file'] = $this->request->data ["JobFileTemp"] ['job_file'] ['name'];
			}
			
			if (! empty ( $file_array )) {
				$n = explode ( ".", $this->request->data ["JobFileTemp"] ['job_file'] );
				$file_name = array_shift ( $n );
				$file_name = str_replace ( " ", "_", $file_name );
				/* this is being used to upload user big size profile image */
				$filename = parent::__upload ( $file_array, JOB_APPLY_TEMP_THUMB_DIR_FILE, $file_name );
			}
		}
		$this->request->data ['JobFileTemp'] ['user_id'] = $this->Auth->User ( 'id' );
		$this->request->data ['JobFileTemp'] ['job_file'] = $filename;
		
		if (! empty ( $this->request->data )) {
			
			$avataruploaded = $this->JobFileTemp->saveAll ( $this->request->data );
			
			$lastInsert_id = $this->JobFileTemp->id;
			if ($avataruploaded) {
				echo "success|" . $filename . "|" . $lastInsert_id;
			} else {
				echo "failed";
			}
		}
		die ();
	}



	public function delete_job_milestone($id) {
		$this->layout = false;
		$this->autoRender = false;
		$this->loadModel ( 'JobMilestone' );
		
		if (! empty ( $id )) {
			$this->JobMilestone->id = $id;
			$this->JobMilestone->delete ();
			$this->Session->setFlash ( __ ( 'Job milestone has been posted successfully.' ), 'default', array (
					"class" => "success" 
			) );
			die ();
			
		}
	}
	public function delete_job_apply_file($id) {
		$this->layout = false;
		$this->autoRender = false;
		$this->loadModel ( 'FileTemp' );
		$temp = $this->JobFileTemp->find ( 'first', array (
				'conditions' => array (
						'JobFileTemp.id' => $id 
				) 
		) );
		$file = $temp ['JobFileTemp'] ['job_file'];
		$this->JobFileTemp->id = $id;
		unlink ( 'img/'.JOB_APPLY_TEMP_THUMB_DIR_FILE . $file );
		$this->JobFileTemp->delete ();
	}
	public function download_job_apply_file($id) {
		$this->layout = false;
		$this->loadModel ( 'JobFileTemp' );
		$data_file = $this->JobFileTemp->find ( 'first', array (
				'conditions' => array (
						'JobFileTemp.id' => $id 
				) 
		) );
		$fullPath =  'img/'. JOB_APPLY_TEMP_THUMB_DIR_FILE_VIEW . $data_file ['JobFileTemp'] ['job_file'];
		
		if ($fd = fopen ( $fullPath, "r" )) {
			$fsize = filesize ( $fullPath );
			$path_parts = pathinfo ( $fullPath );
			$ext = strtolower ( $path_parts ["extension"] );
			header ( 'Content-Description: File Transfer' );
			header ( 'Content-Type: application/octet-stream' );
			header ( 'Content-Disposition: attachment; filename=' . basename ( $fullPath ) );
			header ( 'Content-Transfer-Encoding: binary' );
			header ( 'Expires: 0' );
			header ( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header ( 'Pragma: public' );
			header ( 'Content-Length: ' . filesize ( $fullPath ) );
			// ob_clean();
			flush ();
			readfile ( $fullPath );
			exit ();
		}
	}
	
	/*
	 * Apply to the job pashkovdenis@gmail.com 2014
	 */
	public function apply_job() {
		$this->layout = 'lay_job_application_form';
		$this->set ( 'title_for_layout', 'Apply Job' );
		$this->loadModel ( 'Job' );
		$this->loadModel ( 'JobBid' );
		$this->loadModel ( 'JobFileTemp' );
		$this->loadModel ( 'JobBidFile' );
		$this->loadModel ( "Workroom" );
		$this->loadModel ( "User" );
		$this->loadModel ( "Project" );
		
		$this->JobBid->bindModel ( array (
				'hasMany' => array (
						'JobBidFile' 
				) 
		), false );
		
		if (! empty ( $this->request->data )) {
			
			$this->JobBid->set ( $this->request->data );
			$this->JobBid->setValidation ( 'admin' );
			
			if ($this->JobBid->validates ()) {
				
				$data_to_check = $this->request->data;
				
				// Array ( [job_id] => 155 [proposal] => wdwq [availability] => 2323 [duration] => 23 [experience] => student [future_value] => 2313 [cash_value] => 23 )
				// Array ( [job_id] => 155 [proposal] => asdaqw [availability] => 23 [duration] => 23 [experience] => student [future_value] => 23 [cash_value] => 23 ) )
				
				$jobValue = $this->Job->find ( 'first', array (
						'conditions' => array (
								'Job.id' => $data_to_check ["job_id"] 
						) 
				) );
				
				$this->request->data ["JobBid"] = array();
				
				$this->request->data ['JobBid'] = array_merge ( $data_to_check, $this->request->data ['JobBid'] );
				$this->request->data ['JobBid'] ['user_id'] = parent::__get_session_user_id ();
				$this->request->data ['JobBid'] ['project_id'] = $jobValue ['Job'] ['project_id'];
				$this->request->data ['JobBid'] ['status'] = 1;
				
				$data = array();
				$data ["JoBid"] ["job_id"] = $data_to_check ["job_id"];
				$data ["JoBid"] ["proposal"] = $data_to_check ["proposal"];
				$data ["JoBid"] ["availability"] = $data_to_check ["availability"];
				$data ["JoBid"] ["duration"] = $data_to_check ["duration"];
				$data ["JoBid"] ["experience"] = $data_to_check ["JobBid"] ["experience"];
				$data ["JoBid"] ["future_value"] = $data_to_check ["future_value"];
				$data ["JoBid"] ["cash_value"] = $data_to_check ["cash_value"];
				$data ["JoBid"] ["project_id"] = $jobValue ["Job"] ["project_id"];
				$data ["JoBid"] ["user_id"] = $this->Auth->user ( "id" );
				$data ["JoBid"] ["status"] = 1;
				
				$c = $this->JobBid->find ( "count", array (
						"conditions" => array (
								"user_id" => $this->Auth->user ( "id" ),
								"job_id" => $data_to_check ["job_id"] 
						) 
				) );
				
				if ($c == 0) {
					
					if ($this->JobBid->saveAll ( $data ["JoBid"] )) {
						
						$id = $this->JobBid->id;
						@parent::__copy_directory ( JOB_BID_PATH_DEFAULT, JOB_BID_PATH . $id );
						@parent::__copy_directory ( JOB_APPLY_TEMP_THUMB_DIR_FILE, JOB_BID_PATH . $id . '/bid_files' );
						
						$oldfiles = $this->JobFileTemp->find ( "all", array (
								"conditions" => array (
										"JobFileTemp.user_id" => $this->Auth->User ( 'id' ) 
								),
								'fields' => 'JobFileTemp.job_file' 
						) );
						if (isset ( $oldfiles ) && ! empty ( $oldfiles )) {
							foreach ( $oldfiles as $oldfile ) {
								@unlink ( SITE_URL.JOB_APPLY_TEMP_THUMB_DIR_FILE . $oldfile ['JobFileTemp'] ['job_file'] );
							}
						}
						
						// Send Email For user
						$leader = $this->Project->find ( "first", array (
								"conditions" => array (
										"Project.id" => $jobValue ['Job'] ['project_id'] 
								) 
						) );
						$user_leader = $this->User->find ( "first", array (
								"conditions" => array (
										"User.id" => $leader ["Project"] ["user_id"] 
								) 
						) );
						$current_user = $this->User->find ( "first", array (
								"conditions" => array (
										"User.id" => $this->request->data ['JobBid'] ['user_id'] 
								) 
						) );
						// Set inbox System message
						
						$this->loadModel ( "SystemInbox" );
						SystemInbox::insert ( $leader ["Project"] ["user_id"], JOB_APPLY_TEXT . " " . $jobValue ["Job"] ["title"] . "@" . $leader ["Project"] ["title"], $this->params->pass [0] );
						
						/*
						 * Send Text For Job Apply 2013
						 */
						
						$email = new CakeEmail ( 'gmail' );
						$email->template ( 'default', "default" );
						$email->emailFormat ( 'html' );
						
						$email->from ( $user_leader ["User"] ["email"] );
						$email->to ( $user_leader ["User"] ["email"] );
						// Email
						$text = EMAIL_JOBAPPLIED;
						$text = str_replace ( "{user}", $user_leader ["User"] ["username"], $text );
						$text = str_replace ( "{job}", $jobValue ["Job"] ["title"], $text );
						
						$email->subject ( "New job request " );
						$email->send ( $text );
						
						$work = new Workroom ();
						$work->query ( "DELETE FROM  job_invite WHERE job_id='{$this->params->pass[0]}' AND to_user='{$this->Auth->user("id")}' " );
						
						$work->applyJob ( $this->request->data ['JobBid'] ['project_id'], $this->request->data ['JobBid'] ['user_id'], $this->params->pass [0] );
						$this->JobFileTemp->deleteAll ( array (
								'JobFileTemp.user_id' => parent::__get_session_user_id () 
						) );
						$this->Session->setFlash ( __ ( 'Job request has been sent' ), 'default', array (
								"class" => "success" 
						) );
					}
				} else {
					$this->Session->setFlash ( __ ( 'You already applied for   this job' ), 'default', array (
							"class" => "error" 
					) );
				}
				
				$this->redirect ( array (
						'controller' => 'jobs',
						'action' => 'job_detail',
						$data_to_check ["job_id"] 
				) );
			}
		} else {
			parent::remove_temp_files_of_job_bid ();
		}
		
		// Apply Job Stack :
		
		$jobFileTemps = $this->JobFileTemp->find ( 'all', array (
				'conditions' => array (
						'JobFileTemp.user_id' => parent::__get_session_user_id () 
				),
				'fields' => array (
						'job_file',
						'id' 
				) 
		) );
		$this->set ( compact ( 'jobFileTemps' ) );
	}
	public function download_job_attachement_from_job_detail() {
		$this->layout = false;
		$this->loadModel ( 'JobAttachment' );
		$data_file = $this->JobAttachment->find ( 'first', array (
				'conditions' => array (
						'JobAttachment.id' => $this->params ['pass'] [0] 
				) 
		) );
		
		$fullPath = str_replace ( '{job_id}', $this->params ['pass'] [1], JOB_ATTACHEMENT_PATH_FOLDER ) . '/' . $data_file ['JobAttachment'] ['file_name'];
		if ($fd = fopen ( $fullPath, "r" )) {
			$fsize = filesize ( $fullPath );
			$path_parts = pathinfo ( $fullPath );
			$ext = strtolower ( $path_parts ["extension"] );
			// $fl = $fullPath;
			header ( 'Content-Description: File Transfer' );
			header ( 'Content-Disposition: attachment; filename=' . basename ( $fullPath ) );
			header ( 'Content-Transfer-Encoding: binary' );
			header ( 'Expires: 0' );
			header ( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header ( 'Pragma: public' );
			header ( 'Content-Length: ' . filesize ( $fullPath ) );
			ob_clean ();
			flush ();
			readfile ( $fullPath );
			exit ();
		}
	}
	public function download_job_file_from_job_detail() {
		$this->layout = false;
		$this->loadModel ( 'JobFile' );
		$data_file = $this->JobFile->find ( 'first', array (
				'conditions' => array (
						'JobFile.id' => $this->params ['pass'] [0] 
				) 
		) );
		$name = str_replace ( " ", "_", $data_file ['JobFile'] ["file_name"] );
		
		
		$fullPath = str_replace ( '{job_id}', $this->params ['pass'] [1], JOB_FILES_PATH_FOLDER ) . '/' . $data_file ['JobFile'] ['project_file'];
		if ($fd = fopen ( $fullPath, "r" )) {
			$fsize = filesize ( $fullPath );
			$path_parts = pathinfo ( $fullPath );
			$ext = strtolower ( $path_parts ["extension"] );
			// $fl = $fullPath;
			header ( 'Content-Description: File Transfer' );
			header ( 'Content-Disposition: attachment; filename=' . $name );
			header ( 'Content-Transfer-Encoding: binary' );
			header ( 'Expires: 0' );
			header ( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header ( 'Pragma: public' );
			header ( 'Content-Length: ' . filesize ( $fullPath ) );
			ob_clean ();
			flush ();
			readfile ( $fullPath );
			exit ();
		}
	}
	
	/*
	 * Get Skills DropDown for applied Stack :
	 */
	public function getSkillDrop($counter) {
		$this->autoRender = false;
		$this->loadModel ( "Category" );
		$this->loadModel ( "Skill" );
		$this->loadModel ( "Job" );
		
		$skill_model = new Skill ();
		$Category_model = new Category ();
		
		$str = "

			 <div id='skill_div_{$counter}' class='skill_div' style='margin-bottom:10px;'> 
			 <div style='float: left;width: 224px;'>
                                  
				  <select class='jbc custom_dropdown' > 
					 <option>  Select Category  </option>   ";
		$ct = $Category_model->find ( "all", array (
				"conditions" => array (
						"Category.type_for" => 2 
				) 
		) );
		foreach ( $ct as $c ) {
			$str .= "<option  value='{$c["Category"]["id"]}' >   {$c["Category"]["name"]}  </option> ";
		}
		
		$str .= "   
				 	</select> 
					 		  
					</div> 		
					 		 
					 <div class='skills' style='float:left'> 
                                            
				 	 <select name='data[Skill][Skill][]' class='skillslistsrop  custom_dropdown'> 
					 		  <option>  Select Skill </option>  
							  <option>   Select Category First  </option> 
					  </select>  
					  </div>
			 		
				  
				";
		
		// Select Skill :
		//
		$str .= " <a href='javascript:void(0)' class='remove_skill  delete2'>     </a>  
				 </div> 

			  	
				";
		echo $str;
	}
        public function getSkillDropUser($counter) {
		$this->autoRender = false;
		$this->loadModel ( "Category" );
		$this->loadModel ( "Skill" );
		$this->loadModel ( "Job" );
		
		$skill_model = new Skill ();
		$Category_model = new Category ();
		
		$str = "

			 <div id='skill_div_{$counter}' class='skill_div' style='margin-bottom:10px;'> 
			 <div style='float: left;width: 224px;'>
                                 
				  <select class='jbc custom_dropdown' > 
					 <option>  Select Category  </option>   ";
		$ct = $Category_model->find ( "all", array (
				"conditions" => array (
						"Category.type_for" => 2 
				) 
		) );
		foreach ( $ct as $c ) {
			$str .= "<option  value='{$c["Category"]["id"]}' >   {$c["Category"]["name"]}  </option> ";
		}
		
		$str .= "   
				 	</select> 
					 		  
					</div> 		
					 		 
					 <div class='skills' style='float:left'> 
                                            
				 	 <select name='data[Skill][Skill][]' class='skillslistsrop  custom_dropdown'> 
					 		  <option>  Select Skill </option>  
							  <option>   Select Category First  </option> 
					  </select>  
					  </div>
			 		<style>.rating{ margin-left:15px;}.rating .sbHolder{ width: 65px;} .rating .sbSelector{width: 50px;}</style>
                                        <div class='rating' style='float:left'> 
                                            
				 	 <select name='data[Skill][rate][]' class='custom_dropdown' style='width=50px;'> 
					 		  <option> Rating</option>  
							  
					  </select>  
					  </div>
				";
		
		// Select Skill :
		//
		$str .= " <a href='javascript:void(0)' class='remove_skill  delete2'>     </a>  
				 </div> 

			  	
				";
		echo $str;
	}
	
	/*
	 * get Dropd for Skills Selected : 2014
	 */
	public function getsskilloptions($catid) {
		$this->autoRender = false;
		$this->loadModel ( "Skill" );
		$skill_model = new Skill ();
		$skills = $skill_model->find ( "all", array (
				"conditions" => array (
						"category_id" => $catid 
				) 
		) );
		$str = "  <select name='data[Skill][Skill][]' class='skillslistsrop  custom_dropdown'> 
					 		  <option>  Select Skill </option>   ";
		foreach ( $skills as $s ) {
			$str .= "<option value='{$s["Skill"]["id"]}' > {$s["Skill"]["name"]}   </option> ";
		}
		
		echo $str . "</select>  ";
	}
	public function update_job($id = null, $teamup = false) 
	{
		//$id=explode(',',$id);
		

		$this->autoRender = false ;
        $this->loadModel("JobBid");
        $model = new JobBid() ;

		 $model->query("UPDATE job_bids SET proposal='".$_POST['proposal']."'  , availability='".$_POST['data']['availability']."', duration='".$_POST['data']['duration']."', experience='".$_POST['data']['JobBid']['experience']."',  future_value='".$_POST['data']['future_value']."', cash_value='".$_POST['data']['cash_value']."' WHERE user_id ='".$_POST['data']['job_id']."' AND job_id='".$_POST['data']['user_txt_id']."'");
		$this->redirect ( array (
							'controller' => 'jobs',
							'action' => 'job_detail',
							$_POST['data']['user_txt_id'] 
					) );
	}
}
