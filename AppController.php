<?php
/**
 * Application controller
 *
 * This file is the base controller of all other controllers
 *
 * PHP version 5
 *
 * @category Controllers
 * @version  1.0
 */
App::uses ( 'CakeEmail', 'Network/Email' );
class AppController extends Controller {
	
	/**
	 * Components
	 *
	 * @var array
	 * @access public
	 */
	var $components = array (
			'Security',
			'RequestHandler',
			'Session',
			'Auth',
			'Cookie',
			'Upload' 
	);
	
	/**
	 * Models
	 *
	 * @var array
	 * @access public
	 */
	var $uses = array ();
	
	/**
	 * Helpers
	 *
	 * @var array
	 * @access public
	 */
	var $helpers = array (
			'Html',
			'Form',
			'Session',
			'Text',
			'Js',
			'Layout',
			'Time',
			'ExPaginator',
			'Admin',
			'General' 
	);
	
	/**
	 * beforeFilter
	 *
	 * @return void
	 */
	public function beforeFilter() {
		$user_data = array ();
		/* Define array for element */
		
		$this->Security->blackHoleCallback = '__securityError';
		$this->disableCache ();
		
		$this->loadModel ( 'Slider' );
		$imageArray = $this->Slider->find ( 'all', array (
				'order' => array (
						'Slider.id' => 'DESC' 
				),
				'limit' => '3' 
		) );
		$count_image = count ( $imageArray );
		
		$this->set ( compact ( 'imageArray', 'count_image' ) );
		/* remove extra space in form fields */
		if (! empty ( $this->request->data )) {
			array_walk_recursive ( $this->request->data, create_function ( '&$value, &$key', '$value = trim($value);' ) );
		}
		$this->get_all_settings ();
		$this->get_all_validations ();
		$this->get_footer_link ();
		
		if (isset ( $this->request->params ['admin'] )) {
			
			$this->layout = 'admin';
			$this->Auth->authenticate = array (
					'Form' => array (
							'userModel' => 'User',
							'scope' => array (
									'User.status' => Configure::read ( 'App.Status.active' ),
									'User.role_id' => Configure::read ( 'App.Role.Admin' ) 
							),
							'fields' => array (
									'username' => 'username',
									'password' => 'password' 
							) 
					) 
			);
			$this->Auth->loginError = __ ( "login_failed_invalid_username_or_password" );
			$this->Auth->loginAction = array (
					'admin' => true,
					'controller' => 'admins',
					'action' => 'login' 
			);
			$this->Auth->loginRedirect = array (
					'admin' => true,
					'controller' => 'admins',
					'action' => 'dashboard' 
			);
			$this->Auth->authError = __ ( ' ' );
			$this->Auth->autoRedirect = true;
			$this->Auth->allow ( 'admin_login' );
		} else {
			$this->Auth->userModel = 'User';
			$this->Auth->authenticate = array (
					'Form' => array (
							'scope' => array (
									'User.status' => Configure::read ( 'App.Status.active' ) 
							) 
					) 
			);
			$this->Auth->loginError = "Login failed. Invalid username or password";
			$this->Auth->loginAction = array (
					'controller' => 'users',
					'action' => 'login' 
			);
			$this->Auth->loginRedirect = array (
					'controller' => 'projects',
					'action' => 'my_project' 
			);
			$this->Auth->authError = ' ';
			$this->Auth->autoRedirect = true;
			$this->Auth->flash = array (
					'element' => 'flash_info',
					'key' => 'auth',
					'params' => array () 
			);
			$this->Auth->allow ( 'login' );
		}
		
		$this->Auth->authorize = array (
				'Controller' 
		);
		// $this->isAuthorized();
		if ($this->RequestHandler->isAjax ()) {
			$this->layout = 'ajax';
			$this->autoRender = false;
			$this->Security->validatePost = false;
			Configure::write ( 'debug', 2 );
		}
		if ($this->Session->check ( 'Auth.User.id' )) {
			$this->loadModel ( 'User' );
			$this->loadModel ( 'Project' );
			$user_data = $this->User->find ( 'first', array (
					'conditions' => array (
							'User.id' => $this->Session->read ( 'Auth.User.id' ) 
					),
					'fields' => array (
							'User.first_name',
							'User.last_name',
							'User.email',
							'User.username' 
					) 
			) );
			$project_data = $this->Project->find ( 'all', array (
					'conditions' => array (
							'Project.user_id' => $this->Session->read ( 'Auth.User.id' ) 
					) 
			) );
			// $this->request->data['Feedback'] = $user_data['User']['email'];
			self::user_restrict_access_after_login ();
			// self::remove_temp_files_project_job();// remove the temp files and temp image of project.
			$this->Set ( compact ( 'user_data', 'project_data' ) );
			$myImage = self::getMyImage ();
			$this->set ( 'myImage', $myImage );
		}
	}
	
	/**
	 * isAuthorized
	 *
	 * @return void
	 */
	function isAuthorized() {
		if (isset ( $this->params ['admin'] )) {
			
			if ($this->Auth->user ()) {
				if ($this->Auth->user ( 'role_id' ) != 1) {
					$this->redirect ( '/' );
				} else {
					return true;
				}
			}
		} else {
			if ($this->Auth->user ()) {
				Configure::write ( 'public_url', $this->Auth->user ( 'public_url' ) );
				if (($this->params ['action'] == 'login' || $this->params ['action'] == 'register' || $this->params ['action'] == 'forgotpassword')) {
					$this->redirect ( array (
							'controller' => 'pages',
							'action' => 'home' 
					) );
				} else {
					return true;
				}
			}
			return true;
		}
	}
	
	/**
	 * blackHoleCallback for SecurityComponent
	 *
	 * @return void
	 */
	public function __securityError() {
	}
	
	/*
	 * Set Redirect Stuff For Both MEthods stack
	 */
	public function beforeRender() {
		$this->_configureErrorLayout ();
		
		if ($this->Session->read ( "back" ) != "") {
			$b = $this->Session->read ( "back" );
			$this->Session->delete ( "back" );
			$this->redirect ( $b );
		}
	}
	
	/**
	 * sendMail
	 *
	 * @return void
	 * @access private
	 */
	public function sendMail($to, $subject, $message, $from, $layout = 'default') {
		$email = new CakeEmail ( 'gmail' );
		
		$email->template ( 'default', $layout );
		$email->emailFormat ( 'html' );
		$email->from ( $from );
		$email->to ( $to );
		$email->subject ( $subject );
		
		if ($email->send ( $message ))
			return true;
		return false;
	}
	public function _configureErrorLayout() {
		$debug_mode = Configure::read ( 'debug' );
		if ($debug_mode > 0) {
			$layout_front = 'error';
			$layout_admin = 'error';
		} else {
			$layout_front = 'front_error_online';
			$layout_admin = 'admin_error_online';
		}
		if ($this->name == 'CakeError') {
			if ($this->_isAdminMode ()) {
				$this->layout = $layout_admin;
			} else {
				$this->layout = $layout_front;
			}
		}
	}
	public function _isAdminMode() {
		$adminRoute = Configure::read ( 'Routing.prefixes' );
		if (isset ( $this->params ['prefix'] ) && in_array ( $this->params ['prefix'], $adminRoute )) {
			return true;
		}
		return false;
	}
	public function get_all_settings() {
		$this->loadModel ( 'Setting' );
		$settings = $this->Setting->find ( 'all', array (
				'fields' => array (
						'Setting.value' 
				) 
		) );
		Configure::write ( 'Site.title', $settings [0] ['Setting'] ['value'] );
		Configure::write ( 'App.SiteName', $settings [0] ['Setting'] ['value'] );
		Configure::write ( 'App.AdminMail', $settings [1] ['Setting'] ['value'] );
		Configure::write ( 'App.Facebook.Verification.Friends', $settings [2] ['Setting'] ['value'] );
	}
	public function get_all_validations() {
		$this->loadModel ( 'ValidationLevel' );
		$validations = $this->ValidationLevel->find ( 'all', array (
				'fields' => array (
						'slug',
						'status' 
				) 
		) );
		
		$mail_validation = $validations [0] ['ValidationLevel'] ['status'];
		$ip_validation = $validations [1] ['ValidationLevel'] ['status'];
		$bank_account_validation = $validations [2] ['ValidationLevel'] ['status'];
		$passport_validation = $validations [3] ['ValidationLevel'] ['status'];
		$address_validation = $validations [4] ['ValidationLevel'] ['status'];
		$call_validation = $validations [5] ['ValidationLevel'] ['status'];
		// $this->set(compact('mail_validation','ip_validation','bank_account_validation','passport_validation','address_validation','call_validation'));
		Configure::write ( 'App.mail_validation', $mail_validation );
		Configure::write ( 'App.ip_validation', $ip_validation );
		Configure::write ( 'App.bank_account_validation', $bank_account_validation );
		Configure::write ( 'App.passport_validation', $passport_validation );
		Configure::write ( 'App.address_validation', $address_validation );
		Configure::write ( 'App.call_validation', $call_validation );
	}
	public function project_status() {
		$settings = $this->Project->find ( 'all', array (
				'fields' => array (
						'Setting.value' 
				) 
		) );
		Configure::write ( 'Site.title', $settings [0] ['Setting'] ['value'] );
		Configure::write ( 'App.SiteName', $settings [0] ['Setting'] ['value'] );
		Configure::write ( 'App.AdminMail', $settings [1] ['Setting'] ['value'] );
	}
	public function DisplayQuery($model) {
		$log = $this->$model->getDataSource ()->getLog ( false, false );
		debug ( $log );
	}
	public function is_ajax() {
		if ($this->RequestHandler->isAjax ()) {
			return true;
		} else {
			$this->redirect ( $this->referer () );
		}
	}
	public function getuserDetailid() {
		$this->loadModel ( 'UserDetail' );
		$user_id = $this->UserDetail->find ( 'first', array (
				'fields' => array (
						'id' 
				),
				'conditions' => array (
						'UserDetail.user_id' => $this->Auth->User ( 'id' ) 
				) 
		) );
		return $user_id ['UserDetail'] ['id'];
	}
	
	/**
	 * xml2array() will convert the given XML text to an array in the XML structure.
	 * Link: http://www.bin-co.com/php/scripts/xml2array/
	 * Arguments : $contents - The XML text
	 * $get_attributes - 1 or 0. If this is 1 the function will get the attributes as well as the tag values - this results in a different array structure in the return value.
	 * $priority - Can be 'tag' or 'attribute'. This will change the way the resulting array sturcture. For 'tag', the tags are given more importance.
	 * Return: The parsed XML in an array form. Use print_r() to see the resulting array structure.
	 * Examples: $array = xml2array(file_get_contents('feed.xml'));
	 * $array = xml2array(file_get_contents('feed.xml', 1, 'attribute'));
	 */
	function xml2array($contents, $get_attributes = 1, $priority = 'tag') {
		if (! $contents)
			return array ();
		
		if (! function_exists ( 'xml_parser_create' )) {
			// print "'xml_parser_create()' function not found!";
			return array ();
		}
		
		// Get the XML parser of PHP - PHP must have this module for the parser to work
		$parser = xml_parser_create ( '' );
		xml_parser_set_option ( $parser, XML_OPTION_TARGET_ENCODING, "UTF-8" ); // http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
		xml_parser_set_option ( $parser, XML_OPTION_CASE_FOLDING, 0 );
		xml_parser_set_option ( $parser, XML_OPTION_SKIP_WHITE, 1 );
		xml_parse_into_struct ( $parser, trim ( $contents ), $xml_values );
		xml_parser_free ( $parser );
		
		if (! $xml_values)
			return; // Hmm...
				        
		// Initializations
		$xml_array = array ();
		$parents = array ();
		$opened_tags = array ();
		$arr = array ();
		
		$current = &$xml_array; // Refference
		                        
		// Go through the tags.
		$repeated_tag_index = array (); // Multiple tags with same name will be turned into an array
		foreach ( $xml_values as $data ) {
			unset ( $attributes, $value ); // Remove existing values, or there will be trouble
			                               
			// This command will extract these variables into the foreach scope
			                               // tag(string), type(string), level(int), attributes(array).
			extract ( $data ); // We could use the array by itself, but this cooler.
			
			$result = array ();
			$attributes_data = array ();
			
			if (isset ( $value )) {
				if ($priority == 'tag')
					$result = $value;
				else
					$result ['value'] = $value; // Put the value in a assoc array if we are in the 'Attribute' mode
			}
			
			// Set the attributes too.
			if (isset ( $attributes ) and $get_attributes) {
				foreach ( $attributes as $attr => $val ) {
					if ($priority == 'tag')
						$attributes_data [$attr] = $val;
					else
						$result ['attr'] [$attr] = $val; // Set all the attributes in a array called 'attr'
				}
			}
			
			// See tag status and do the needed.
			if ($type == "open") { // The starting of the tag '<tag>'
				$parent [$level - 1] = &$current;
				if (! is_array ( $current ) or (! in_array ( $tag, array_keys ( $current ) ))) { // Insert New tag
					$current [$tag] = $result;
					if ($attributes_data)
						$current [$tag . '_attr'] = $attributes_data;
					$repeated_tag_index [$tag . '_' . $level] = 1;
					
					$current = &$current [$tag];
				} else { // There was another element with the same tag name
					
					if (isset ( $current [$tag] [0] )) { // If there is a 0th element it is already an array
						$current [$tag] [$repeated_tag_index [$tag . '_' . $level]] = $result;
						$repeated_tag_index [$tag . '_' . $level] ++;
					} else { // This section will make the value an array if multiple tags with the same name appear together
						$current [$tag] = array (
								$current [$tag],
								$result 
						); // This will combine the existing item and the new item together to make an array
						$repeated_tag_index [$tag . '_' . $level] = 2;
						
						if (isset ( $current [$tag . '_attr'] )) { // The attribute of the last(0th) tag must be moved as well
							$current [$tag] ['0_attr'] = $current [$tag . '_attr'];
							unset ( $current [$tag . '_attr'] );
						}
					}
					$last_item_index = $repeated_tag_index [$tag . '_' . $level] - 1;
					$current = &$current [$tag] [$last_item_index];
				}
			} elseif ($type == "complete") { // Tags that ends in 1 line '<tag />'
			                                 // See if the key is already taken.
				if (! isset ( $current [$tag] )) { // New Key
					$current [$tag] = $result;
					$repeated_tag_index [$tag . '_' . $level] = 1;
					if ($priority == 'tag' and $attributes_data)
						$current [$tag . '_attr'] = $attributes_data;
				} else { // If taken, put all things inside a list(array)
					if (isset ( $current [$tag] [0] ) and is_array ( $current [$tag] )) { // If it is already an array...
					                                                                      
						// ...push the new element into that array.
						$current [$tag] [$repeated_tag_index [$tag . '_' . $level]] = $result;
						
						if ($priority == 'tag' and $get_attributes and $attributes_data) {
							$current [$tag] [$repeated_tag_index [$tag . '_' . $level] . '_attr'] = $attributes_data;
						}
						$repeated_tag_index [$tag . '_' . $level] ++;
					} else { // If it is not an array...
						$current [$tag] = array (
								$current [$tag],
								$result 
						); // ...Make it an array using using the existing value and the new value
						$repeated_tag_index [$tag . '_' . $level] = 1;
						if ($priority == 'tag' and $get_attributes) {
							if (isset ( $current [$tag . '_attr'] )) { // The attribute of the last(0th) tag must be moved as well
								
								$current [$tag] ['0_attr'] = $current [$tag . '_attr'];
								unset ( $current [$tag . '_attr'] );
							}
							
							if ($attributes_data) {
								$current [$tag] [$repeated_tag_index [$tag . '_' . $level] . '_attr'] = $attributes_data;
							}
						}
						$repeated_tag_index [$tag . '_' . $level] ++; // 0 and 1 index is already taken
					}
				}
			} elseif ($type == 'close') { // End of tag '</tag>'
				$current = &$parent [$level - 1];
			}
		}
		
		return ($xml_array);
	}
	public function rrmdir($dir) {
		foreach ( glob ( $dir . '/*' ) as $file ) {
			if (is_dir ( $file ))
				$this->rrmdir ( $file );
			else
				unlink ( $file );
		}
		
		rmdir ( $dir );
	}
	
	/*
	 * function rrm($dir){ }
	 */
	function base_encode($data) {
		for($i = 0; $i < 4; $i ++) {
			$data = base64_encode ( $data );
		}
		return $data;
	}
	public function base_decode($data) {
		for($i = 0; $i < 4; $i ++) {
			$data = base64_decode ( $data );
		}
		return $data;
	}
	
	/**
	 * Created By: khemit verma
	 * Date: jan 22, 2013
	 * This is being used to upload file with modification.
	 */
	function __uploadFile($file_array = array(), $directory = NULL, $filename = NULL, $width = NULL, $height = NULL, $isResize = true) {
		if (empty ( $file_array )) {
			$this->render ();
		} else {
			$extension = $this->__fileExtension ( $file_array ['type'] );
			if (empty ( $filename )) {
				$filename = time () . $extension;
			} else {
				$filename = $filename . $extension;
			}
			// $this->cleanUpFields();
			// set the upload destination folder
			$destination = realpath ( $directory ) . '/';
			// grab the file
			$file = $file_array;
			/*
			 * echo $filename; pr($file); die;
			 */
			// upload the image using the upload component
			$imageSize = getimagesize ( $file_array ['tmp_name'] );
			$width_ori = $imageSize [0];
			$height_ori = $imageSize [1];
			if ($width > $width_ori || $height > $height_ori) {
				$width = $width_ori;
				$height = $height_ori;
			}
			/*
			 * if(!$isResize) { $imageSize = getimagesize($file_array['tmp_name']); $width = $imageSize[0]; $height = $imageSize[1]; }
			 */
			
			$result = $this->Upload->upload ( $file, $destination, $filename, array (
					'type' => 'resizecrop',
					'size' => array (
							$width,
							$height 
					),
					'output' => 'jpg' 
			) );
			return $filename;
		}
	}
	
	/**
	 * Created By: khemit verma
	 * Date: jan 22, 2013
	 * This is being used to upload file without modification.
	 */
	function __upload($file_array = array(), $directory = NULL, $filename = NULL) {
		if (! empty ( $file_array )) {
			if (empty ( $filename )) {
				$filename = time ();
			}
			
			$extension = $this->__fileExtension ( $file_array ['type'] );
			$destination = $directory . $filename . $extension;
			$filename = $filename . $extension;
			if (move_uploaded_file ( $file_array ['tmp_name'], $destination )) {
				
				return $filename;
			} else {
				return false;
			}
		}
		return false;
	}
	
	/**
	 * Created By: khemit verma
	 * Date: jan 22, 2013
	 * This is being used to get file extension only image file.
	 */
	function __fileExtension($filetype = NULL) {
		switch ($filetype) {
			case 'image/gif' :
				return '.gif';
				break;
			case 'image/jpeg' :
				return '.jpg';
				break;
			case 'image/jpg' :
				return '.jpg';
				break;
			case 'image/png' :
				return '.png';
				break;
			case 'application/octet-stream' :
				return '.docx';
				break;
			case 'image/x-png' :
				return '.png';
				break;
			case 'image/pjpeg' :
				return '.jpg';
				break;
			case 'text/plain' :
				return '.txt';
				break;
			case 'application/vnd.oasis.opendocument.text' :
				return '.odt';
				break;
			case 'text/x-vcard' :
				return '.vcf';
				break;
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' :
				return '.xlsx';
				break;
			case 'application/vnd.ms-powerpoint' :
				return '.ppt';
				break;
			case 'application/msword' :
				return '.doc';
				break;
			case 'application/pdf' :
				return '.pdf';
				break;
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' :
				return '.docx';
				break;
		}
	}
	
	/**
	 * Created By: khemit verma
	 * Date: jan 22, 2013
	 * This is being used to create user folder diorectories.
	 */
	function __copy_directory($source, $destination) {
		if (is_dir ( $source )) {
			@mkdir ( $destination );
			$directory = dir ( $source );
			while ( FALSE !== ($readdirectory = $directory->read ()) ) {
				if ($readdirectory == '.' || $readdirectory == '..') {
					continue;
				}
				$PathDir = $source . '/' . $readdirectory;
				if (is_dir ( $PathDir )) {
					$this->__copy_directory ( $PathDir, $destination . '/' . $readdirectory );
					continue;
				}
				copy ( $PathDir, $destination . '/' . $readdirectory );
			}
			$directory->close ();
		} else {
			copy ( $source, $destination );
		}
	}
	function get_footer_link() {
		$this->loadModel ( 'Page' );
		$footer_link = $this->Page->find ( 'all', array (
				'conditions' => array (
						'Page.status' => Configure::read ( 'App.Status.active' ) 
				),
				'fields' => array (
						'Page.id',
						'Page.parent_id',
						'Page.slug',
						'Page.title' 
				),
				"order" => "title ASC " 
		) );
		$this->set ( 'footer_link', $footer_link );
	}
	function __random_number() {
		$random_no = rand ( 5, 15 );
		$random_no = $random_no . "MARKET_PLACE" . time ();
		return $random_no;
	}
	function __activateAccount($activation_id) {
		$this->loadModel ( 'UserDetail' );
		$userDetail = $this->UserDetail->find ( 'first', array (
				'conditions' => array (
						'md5(activation_key)' => $activation_id 
				),
				'fields' => array (
						'id' 
				) 
		) );
		
		if (! empty ( $userDetail )) {
			$this->UserDetail->id = $userDetail ['UserDetail'] ['id'];
			$this->request->data ['UserDetail'] ['activation_key'] = 0;
			$this->request->data ['UserDetail'] ['mail_validation'] = 1;
			
			if ($this->UserDetail->save ( $this->request->data )) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	function __get_session_user_id() {
		if ($this->Session->check ( 'Auth.User.id' )) {
			return $this->Session->read ( 'Auth.User.id' );
		}
		return 0;
	}
	function __get_user_detail_id() {
		$this->loadModel ( 'UserDetail' );
		if ($this->Session->check ( 'Auth.User.id' )) {
			$user_data = $this->UserDetail->find ( 'first', array (
					'conditions' => array (
							'UserDetail.user_id' => $this->Session->read ( 'Auth.User.id' ) 
					),
					'fields' => array (
							'UserDetail.id' 
					) 
			) );
			// pr($user_data);
			return $user_data ['UserDetail'] ['id'];
		}
		return 0;
	}
	function __user_detail_id($id) {
		$this->loadModel ( 'UserDetail' );
		
		$user_data = $this->UserDetail->find ( 'first', array (
				'conditions' => array (
						'UserDetail.user_id' => $id 
				),
				'fields' => array (
						'UserDetail.id' 
				) 
		) );
		if (! empty ( $user_data )) {
			return $user_data ['UserDetail'] ['id'];
		}
		
		return 0;
	}
	
	function __user_detail_name($id) {
		$this->loadModel ( 'User' );
		
		$user_data = $this->UserDetail->find ( 'first', array (
				'conditions' => array (
						'User.id' => $id 
				),
				'fields' => array (
						'User.first_name',
						'User.last_name'
				) 
		) );
		if (! empty ( $user_data )) {
			return $user_data ['User'] ['first_name']." ".$user_data ['User'] ['last_name'] ;
		}
		
		return 0;
	}
	
	function user_restrict_access_after_login() {
		$flag = false;
		
		$this->loadModel("User")  ;
		$loaded  =   $this->User->find("first",  array("conditions"=>array("User.id"=> $this->Session->read ( 'Auth.User.id' )), "fields"=>"is_new") );
		 
		 
		
		
	 
		$user_restrict_access_after_login = array (
				'regitration' => array (
						'controller' => 'users',
						'action' => 'register' 
				),
				'login' => array (
						'controller' => 'users',
						'action' => 'login' 
				),
				'forgot_password' => array (
						'controller' => 'users',
						'action' => 'forgot_password' 
				),
				'reset_password' => array (
						'controller' => 'users',
						'action' => 'reset_password' 
				),
				
				"Invitepopup" => array (
						'controller' => 'Invitepopup',
						'action' => 'fromemail' 
				) 
		)
		;
		foreach ( $user_restrict_access_after_login as $k => $v ) {
			if ($this->request->params ['controller'] == $v ['controller'] && $this->request->params ['action'] == $v ['action']) {
				$flag = true;
				break;
			}
		}
		
		
		
		if ($flag) {
			
		 

			// redirect  to eit Profile  Page :
			if ($loaded["User"]["is_new"]  == 1 ) {
				$this->User->query("UPDATE users SET is_new =  0  WHERE id='{$this->Session->read ( 'Auth.User.id' )}' ");
				$this->redirect("/users/user_profile_overview") ;
		
				exit ;   
			}
			
			
			
			
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
			
			
			
		}
		
		
		
	}
	public function social_media_authentication_mail($user_id = 0, $msg = NULL) {
		$user_details = $this->User->find ( 'first', array (
				'conditions' => array (
						"User.id" => $user_id,
						"$model.role_id !=" => Configure::read ( 'App.Role.Admin' ) 
				) 
		) );
		if (isset ( $user_details ) && ! empty ( $user_details )) {
			/**
			 * ***********Send mail***********
			 */
			$first_name = $user_details ['User'] ["first_name"];
			$to = $user_details ["User"] ["email"];
			$from = array (
					Configure::read ( 'App.AdminMail' ) => Configure::read ( 'Site.title' ) 
			);
			$mailMessage = '';
			$template = $this->Template->find ( 'first', array (
					'conditions' => array (
							'Template.slug' => 'user_registration' 
					) 
			) );
			$email_subject = $template ['Template'] ['subject'];
			$subject = '[' . Configure::read ( 'Site.title' ) . ']' . $email_subject;
			
			$mailMessage = str_replace ( array (
					'{NAME}',
					'{SITE}',
					'{MESSAGE}' 
			), array (
					$first_name,
					Configure::read ( 'Site.title' ),
					$msg 
			), $template ['Template'] ['content'] );
			$this->sendMail ( $to, $subject, $mailMessage, $from );
		/**
		 * ***********End mail***********
		 */
		}
	}
	
	
	/*
	 * Redirect  
	 * pashkovdenis@gmail.com  
	 * 2014  
	 *  
	 */
 
	
	public function user_redirect() { 
		
     
			  
		
		
		if ($this->Session->read ( "afterlogin" )) {
			$after = $this->Session->read ( "afterlogin" );
			$this->Session->delete ( "afterlogin" ); 
		 
			
			$this->redirect ( $after );
		}
		
		 
		
		if ($this->Session->check ( 'Auth.User.role_id' )) {
			
			$this->user_restrict_access_after_login();
			
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
		}
	} 
	
	
	
	
	
	
	// get ago  :   
	
	
	public function get_ago_date($day = 1) {
		$date = date ( 'Y-m-d' );
		$newdate = strtotime ( "$day day", strtotime ( $date ) );
		$newdate = date ( 'Y-m-d', $newdate );
		return $newdate;
	}  
	
	
	
	
	
	public function remove_temp_files_project_job() {
		$flag = true;
		if ($this->request->params ['action'] == 'project_general') {
			$flag = false;
		} elseif ($this->request->params ['action'] == 'project_picupload') {
			$flag = false;
		} elseif ($this->request->params ['action'] == 'project_fileupload') {
			$flag = false;
		} elseif ($this->request->params ['action'] == 'job_attachementupload') {
			$flag = false;
		} elseif ($this->request->params ['action'] == 'job_general') {
			$flag = false;
		} elseif ($this->request->params ['action'] == 'project_business_stuff') {
			$flag = false;
		} elseif ($this->request->params ['action'] == 'project_fileupload') {
			$flag = false;
		} elseif ($this->request->params ['action'] == 'job_general') {
			$flag = false;
		} elseif ($this->request->params ['action'] == 'job_attachementupload') {
			$flag = false;
		} elseif ($this->request->params ['action'] == 'job_fileupload') {
			$flag = false;
		} elseif ($this->request->params ['action'] == 'job_compensation') {
			$flag = false;
		} 

		elseif ($this->RequestHandler->isAjax ()) {
			$flag = false;
		}
		
		if ($flag) {
			$this->loadModel ( 'FileTemp' );
			$temp = $this->FileTemp->find ( 'all', array (
					'conditions' => array (
							'FileTemp.user_id' => $this->Session->read ( 'Auth.User.id' ) 
					) 
			) );
			
			if (! empty ( $temp )) {
				foreach ( $temp as $temp_file ) {
					$file = $temp_file ['FileTemp'] ['project_file'];
					$id = $temp_file ['FileTemp'] ['id'];
					if (! empty ( $file )) {
						unlink ( PROJECT_TEMP_THUMB_DIR_FILE . $file );
						$this->FileTemp->deleteAll ( array (
								'FileTemp.id' => $id 
						), true );
					}
				}
			}
			/* end of Ajax uploaded file delete */
				
				/* Ajax uploaded file delete for projects files*/
				
				
				$this->loadModel ( 'ImageTemp' );
			$temp = $this->ImageTemp->find ( 'first', array (
					'conditions' => array (
							'ImageTemp.user_id' => $this->Session->read ( 'Auth.User.id' ) 
					) 
			) );
			
			$file = $temp ['ImageTemp'] ['project_image'];
			$id = $temp ['ImageTemp'] ['id'];
			if (! empty ( $file )) {
				
				unlink ( PROJECT_TEMP_THUMB_DIR_232_232 . 'thumb_' . $file );
				unlink ( PROJECT_TEMP_BIG_DIR . 'big_' . $file );
				unlink ( PROJECT_TEMP_SMAll_DIR . 'small_' . $file );
				unlink ( PROJECT_TEMP_ORIGINAL_DIR . $file );
				$this->ImageTemp->delete ( array (
						'ImageTemp.id' => $id 
				), true );
			}
			/* end of Ajax uploaded file delete */
		}
	}
	public function remove_temp_files_of_job() {
		/* Ajax uploaded file delete for projects files */
		$this->loadModel ( 'FileTemp' );
		$temp = $this->FileTemp->find ( 'all', array (
				'conditions' => array (
						'FileTemp.user_id' => $this->Session->read ( 'Auth.User.id' ) 
				) 
		) );
		// pr($temp);die;
		foreach ( $temp as $temp_file ) {
			$file = $temp_file ['FileTemp'] ['project_file'];
			$id = $temp_file ['FileTemp'] ['id'];
			if (! empty ( $file )) {
				unlink ( PROJECT_TEMP_THUMB_DIR_FILE . $file );
				$this->FileTemp->deleteAll ( array (
						'FileTemp.id' => $id 
				), true );
			}
		}
		
		/* end of Ajax uploaded file delete */
	}
	public function remove_temp_files_of_job_bid() {
		/* Ajax uploaded file delete for projects files */
		$this->loadModel ( 'JobFileTemp' );
		$temp = $this->JobFileTemp->find ( 'all', array (
				'conditions' => array (
						'JobFileTemp.user_id' => $this->Session->read ( 'Auth.User.id' ) 
				) 
		) );
		// pr($temp);die;
		foreach ( $temp as $temp_file ) {
			$file = $temp_file ['JobFileTemp'] ['job_file'];
			$id = $temp_file ['JobFileTemp'] ['id'];
			if (! empty ( $file )) {
				unlink ( JOB_APPLY_TEMP_THUMB_DIR_FILE . $file );
				$this->JobFileTemp->deleteAll ( array (
						'JobFileTemp.id' => $id 
				), true );
			}
		}
		
		/* end of Ajax uploaded file delete */
	}
	public function getMyImage() {
		// $this->autoRender =false;
		$this->User->bindModel ( array (
				'hasOne' => array (
						'UserDetail' 
				) 
		), false );
		$image_data = $this->User->find ( 'first', array (
				'fields' => array (
						'id',
						'first_name',
						'last_name',
						'UserDetail.image',
                                                'UserDetail.gender'
				),
				'conditions' => array (
						'User.id' => $this->Auth->user ( 'id' ) 
				) 
		) );
		
		return $image_data;
	}
}
