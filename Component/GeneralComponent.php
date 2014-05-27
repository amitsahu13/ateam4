<?php
App::uses('Component', 'Controller');
class GeneralComponent extends Component{

	/*create new slug*/
	public $name='General';

	public $components = array('Upload', 'Image');


	public function createSlug($string)
	{
		return low(Inflector::slug($string, '-'));
	}

	public function dateToDMY(&$date){
		$date = date('d-m-Y',strtotime($date));
	}
	public function getFolder($project_id){
		return floor($project_id/30000)+1;
	}
	public function dateFromDMY(&$date){
		if(empty($date)){
			$date = '';
			return;
		}
		$a = explode('-',$date);

		$b[0]=$a[2];
		$b[1]=$a[1];
		$b[2]=$a[0];
		$date = implode('-',$b);
	}

	public function imageUpload($model_id,$model_name ,&$file,$image_name,$other=null){
		$extension_allowed=array('.jpg','.gif','.png','.jpeg','.JPG','JPEG','.PNG','.GIF');
		$folder = $model_id;
		if($model_name=='User'){
			$destinations[0] = USER_AVTAR_LARGE_DIR . DS . $folder;
			$destinations[1] = USER_AVTAR_MEDIUM_DIR . DS . $folder;
			$destinations[2] = USER_AVTAR_SMALL_DIR . DS . $folder;
			$destinations[3] = USER_AVTAR_ORG_DIR . DS . $folder;
				
			$width[0] = USER_AVTAR_LARGE_WIDTH;
			$width[1] = USER_AVTAR_MEDIUM_WIDTH;
			$width[2] = USER_AVTAR_SMALL_WIDTH;
		}

		$count=count($destinations);
		$ext= strstr($file['name'],'.');
		$model_id=str_replace("/","_",$model_id);
		$file['name'] = $model_id . $file['name'];

		if(in_array($ext,$extension_allowed)){
			for($i=0;$i<$count;$i++){
				$destination=WWW_ROOT . DS . $destinations[$i]. DS;
					
				if(!file_exists($destination)){
					if (!mkdir($destination)) {
						break;
						$this->Session->setFlash('Image could not be saved. Please, try again.','admin_flash_bad');
					}
				}

				$this->Image->setImage($image_name);
				$this->Image->setQuality(100);
				if(isset($width[$i])){
					$this->Image->resize(array('type' => 'resizecrop', 'size' => $width[$i]));
				}
					
				if (!$this->Image->generate($destination . $file['name']) ){
					print_r($this->Image->errors);
				}
					
				if($other && file_exists($destination.$other)){
					unlink($destination.$other);
				}
			}
		}else {
			return 'Error';
		}
		return $file['name'];
	}

	/*wrap long text*/
	function wrap_long_txt($value=null,$start=null,$end=null){
		$len=strlen($value);
		if($len > $end){
			$str_edit=mb_substr($value,$start,$end);
			return $str_edit.' ...';
				
		}else{
			return $value;
		}
	}

	public function resumeUpload($project_id,$file,$others=null){
		$destination = WWW_ROOT . PROJECT_RESUME_DIR . DS . $project_id;
		if(!is_dir($destination)){
			mkdir($destination);
		}
		move_uploaded_file($file['tmp_name'],$destination. DS .$file['name']);
		if($others && file_exists($destination. DS .$others)){
			unlink($destination. DS .$others);
		}
		return $file['name'];
	}

}
?>