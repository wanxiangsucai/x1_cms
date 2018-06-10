<?php
function_exists('urls') || die('ERR');


$jscode = '';
if(fun('field@load_js',$field['type'])){

	$serverurl = urls('index/attachment/upload','dir=images&from=base64&module='.request()->dispatch()['module'][0]);
	$jscode = <<<EOT

<style type="text/css">
.uploadImg ol{
	line-height: 35px;
	font-size: 16px;
}
.uploadImg li{
	width:80px;
	border:#DDD dotted 1px;
	text-align: center;
	padding: 15px 0;
	background: #FFF;
	font-size:32px;
	cursor: pointer;
	color: #999;
}
.uploadImg li:hover{
	border: #F60 dotted 1px;
	color: #F60;
}

.ListImgs:after{
	content: '';
	display: block;
	clear: both;
}
.ListImgs div{
	width:80px;
	float: left;
	position: relative;
	margin: 10px 10px 0 0;
}
.ListImgs div span{
	display: block;
	position: relative;
	background: #FFF;
	box-shadow: 0px 0px 1px #BBB;
}
.ListImgs div span:before{
	content: '';
	display: block;
	padding-top: 100%;
}
.ListImgs div span img{
	position:absolute;
	max-width:100%;
	max-height: 100%;
	left:50%;
	top: 50%;
	border:0;
  -webkit-transform: translate3D(-50%, -50%, 0);
      -ms-transform: translate3D(-50%, -50%, 0);
          transform: translate3D(-50%, -50%, 0);
} 
.ListImgs div em{
	position: absolute;
	width:25px;
	height: 25px;
	text-align: center;
	line-height: 25px;
	background:rgba(120,120,120,0.6);
	color: #FFF;
	right: 0px;
	top:0px;
	cursor: pointer;
}
.ListImgs div em:hover{
	background:rgba(255,60,0,0.6);
}
</style>

<script type="text/javascript" src="__STATIC__/js/exif.js"></script>
<script type="text/javascript">
var severUrl = "$serverurl";

jQuery(document).ready(function() {
	$(".uploadImge_{$name}").each(function(){
		var pics = [];
		var that = $(this);

		that.find(".upbtn").click(function(e){
			that.find('input[type="file"]').click();
		});
		
		//删除图片事件
		var delpic = function(){
			that.find(".ListImgs em.del").click(function(e){
				//这里删除的图片没有真正从服务器删除
				$(this).parent().remove();
				check_value();
			});
		};
		
		//核对数据
		var check_value = function(){
			that.find(".ListImgs img").each(function(e){
				pics = [];	//重新设置值
				var obj = that.find(".ListImgs img");				
				obj.each(function(e){
					var img = $(this).attr("src");
					img = img.replace('/public/','');
					pics.push({'picurl':img,
						'title':that.find(".ListImgs input[data-i='title"+$(this).data("i")+"']").val(),
						'url':that.find(".ListImgs input[data-i='url"+$(this).data("i")+"']").val(),
						});					
				});				
				if(obj.length==0){
					that.find(".input_value").val('');
				}else{
					that.find(".input_value").val( JSON.stringify(pics) );
				}		
			});
		};
		
		//介绍或网址输入结束后的事件
		var mouseout = function(){
			that.find(".ListImgs input").blur(function(e){
				check_value();
			});
		}

		that.find('input[type="file"]').change(function(){
            uploadBtnChange($(this).get(0),pics,viewpics);
        });

		if(that.find(".input_value").val()!=''){
			pics = $.parseJSON( that.find(".input_value").val() );
		}
		
		var viewpics = function(pic_array,pic_url){
			var html = '';
			pic_array.forEach(function(rs,i){
				var sear=new RegExp('http');
			if(sear.test(rs.picurl)){
    　　		html += '<div><span><img src="'+rs.picurl+'" data-i="'+i+'"></span><em class="del"><i class="fa fa-remove"></i></em><input placeholder="介绍" style="width:80px;margin:5px 0 5px 0;border:1px solid #eee;" data-i="title'+i+'" value="'+rs.title+'"><br><input value="'+rs.url+'" data-i="url'+i+'" placeholder="网址" style="width:80px;border:1px solid #eee;" ></div>';
　　		}else{
				html += '<div><span><img src="/public/'+rs.picurl+'" data-i="'+i+'"></span><em class="del"><i class="fa fa-remove"></i></em><input placeholder="介绍" style="width:80px;margin:5px 0 5px 0;border:1px solid #eee;" data-i="title'+i+'" value="'+rs.title+'"><br><input value="'+rs.url+'" data-i="url'+i+'" placeholder="网址" style="width:80px;border:1px solid #eee;" ></div>';
			}
			 
			});
			that.find(".ListImgs").html(html);
			delpic();
			mouseout();
			check_value(); //新上传要用到
		};
		viewpics(pics);

		var uploadBtnChange = function (filefield,pics,callback){
            var scope = this;
			//var pics = [];
            if(window.File && window.FileReader && window.FileList && window.Blob){ 
                //获取上传file
                //var filefield = document.getElementById(fileName),
                file = filefield.files[0];
				var oj = filefield.files;
				for(var i=0;i<oj.length;i++){
					processfile(oj[i],pics,callback);
				}
            }else{
                alert("此浏览器不完全支持压缩上传图片");
            }
        };

        var processfile = function (file,pics,callback) {
			var Orientation = 0;
			var alltags = {};
			//获取图片的参数信息			
			EXIF.getData(file, function(){
				alltags = EXIF.pretty(this);
				//EXIF.getAllTags(this);
				Orientation = EXIF.getTag(this, 'Orientation');
			});			

            var reader = new FileReader();
            reader.onload = function (event) {
                var blob = new Blob([event.target.result]); 
                window.URL = window.URL || window.webkitURL;
                var blobURL = window.URL.createObjectURL(blob); 
                var image = new Image();
                image.src = blobURL;
                image.onload = function() {
                    var resized = resizeUpImages(image);					
					if(resized){
						// alert( alltags );
						$.post(severUrl, {'imgBase64':resized,'Orientation':Orientation,'tags':alltags}).done(function (res) {
							 if(res.code==1){
								 pics.push({"picurl":res.path,"title":"","url":""});	//组图
								 //pics[0] = res.path;	//单图
								 //textObj.val( pics.join(',') );
								 if(typeof callback == 'function'){
									callback(pics,res.url);
								 }
							 }
						}).fail(function () {
							alert('操作失败，请跟技术联系');
						});	
						
					}
                }
            };
            reader.readAsArrayBuffer(file);
        };

        var resizeUpImages = function (img) {
            //压缩的大小
            var max_width = 1920; 
            var max_height = 1080; 

            var canvas = document.createElement('canvas');
            var width = img.width;
            var height = img.height;
            if(width > height) {
                if(width > max_width) {
                    height = Math.round(height *= max_width / width);
                    width = max_width;
                }
            }else{
                if(height > max_height) {
                    width = Math.round(width *= max_height / height);
                    height = max_height;
                }
            }

            canvas.width = width;
            canvas.height = height;

            var ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0, width, height);
            //压缩率
            return canvas.toDataURL("image/jpeg",0.72); 
        };

	});
});

</script>

EOT;

}


return <<<EOT

$jscode
<div class="uploadImge_{$name}">
		<ul class="uploadImg">
			<div style="display:none;">
				<input type="file" accept="image/*" multiple/> 
				<textarea name="{$name}" class="input_value" rows="5" cols="100">{$info[$name]}</textarea>			
			</div>			 
			<li class="upbtn"><i class="si si-camera"></i></li>
		</ul>
		<div class="ListImgs"></div>
</div>

EOT;
;