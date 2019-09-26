<?php
namespace app\common\upgrade;

class U19{
	public static function up(){
	    if(!is_dir(ROOT_PATH.'public/static/libs/bui')) {
	        mkdir(ROOT_PATH.'public/static/libs/bui');
	    }
		copy('https://x1.php168.com/public/static/libs/bui/banner.jpg',
		     ROOT_PATH.'public/static/libs/bui/banner.jpg');
		copy('https://x1.php168.com/public/static/libs/bui/face.jpg',
		     ROOT_PATH.'public/static/libs/bui/face.jpg');
	}
}