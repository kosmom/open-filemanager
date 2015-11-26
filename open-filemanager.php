<?php
session_start();
error_reporting(1);
$basefolder='images/photo'; // base dir
$csrf_secret='defaultsecretphrase';
$basehttp='http://'.$_SERVER['HTTP_HOST'].'/';
$lazy_load=true;
$translit=true;
$include=array(
	array('type'=>'js','href'=>'//code.jquery.com/jquery-1.11.0.min.js'),
	array('type'=>'js','href'=>$basehttp.'/js/open-filemanager.js'),
	array('type'=>'css','href'=>$basehttp.'/css/open-filemanager.css'),
);
if ($lazy_load)$include[]=array('type'=>'js','href'=>$basehttp.'/js/lazyload.js');
$soft_check=0;
// default basedir config
$tinypng_key=false;
$tinypng_repeat=1;
$upload_extensions=array('gif','jpeg','jpg','png','zip','txt'); // lower register
$replace_when_exists=false;
$modules=array();
$modify_images=array(
	'aspect-ratio-modify'=>false, // resize,crop,false
	'aspect-ratio-crop-position'=>100,
	'aspect-ratio-prop'=>2/3,
	'max-width'=>848,
	'max-height'=>false,
	'quality'=>50,
	'format'=>false // jpg,png,gif,false==source
);
$show_files=array(); // key as filename/foldername
$hide_files=array(); // key as filename/foldername
if (!isset($rights))$rights=array();
// access
// flle - read,delete,rename,upload,choose
// folder - read,delete,rename,create
$config_file='open-filemanager-config.php';
if (isset($_GET['config']))$config_file='open-filemanager-config-'.$_GET['config'].'.php';
$open_filemanager=true;
if (file_exists($config_file))require $config_file;
// определяем права для текущего каталога путем наследования
if (empty($rights['access']))die('Access dinided');
/*********************code next*************************/
$folder=$_GET['folder'];
$scriptfolder=dirname($_SERVER['SCRIPT_FILENAME']);
$full_name=realpath($basefolder.$folder);
if (!$full_name)die('Base folder '.$basefolder.' not exist');
if (substr(substr(str_replace('\\','/',$full_name),0,strlen($scriptfolder.$basefolder)+1),$soft_check)!=substr($scriptfolder."/".$basefolder,$soft_check))die('Каталог '.$basefolder.' указан не верно');
$path=substr(str_replace('\\','/',$full_name),strlen($scriptfolder.$basefolder)+1);
$backpath=substr(str_replace('\\','/',realpath($basefolder.$folder.'/..')),strlen($scriptfolder.$basefolder)+1);
$csrf=sha1(session_id().$csrf_secret.$_SERVER['SERVER_NAME']);
if ($_POST['act'] and $_POST['csrf']!=$csrf)die('CSRF error. Refresh pare please');
switch($_POST['act']){
	case 'add_folder':
		if (!$rights['folder']['create'])die('Add folder access denided');
	    $folder=translit($_POST['folder']);
		if (is_dir($basefolder.'/'.($path?($path.'/'):'').$folder))die('Folder already exist');
		mkdir($basefolder.'/'.($path?($path.'/'):'').$folder);
		die('done');
	case 'delete':
	    $path=$basefolder.'/'.($path?($path.'/'):'').$_POST['name'];
		if (is_dir($path)){
			if (!$rights['folder']['delete'])die('Folder remove access denided');
		}else{
			if (!$rights['file']['delete'])die('File remove access fineded');
		}
		if (is_dir($path)){
			if (glob($path."/*"))die('Folder is not empty. You can remove only empty folders');
			rmdir($path);
		}
		if (is_file($path))unlink($path);
		die('done');
	case 'rename':
		$oldpath=$basefolder.'/'.($path?($path.'/'):'').$_POST['name'];
		if (is_dir($oldpath)){
			if (!$rights['folder']['rename'])die('Folder rename access denided');
		}else{
			if (!$rights['file']['rename'])die('File rename access denided');
		}
		$newpath=$basefolder.'/'.($path?($path.'/'):'').translit($_POST['newname']);
		if (!file_exists($oldpath))die('File or folder not exist');
		rename($oldpath,$newpath);
		die('done');
	case 'upload':
	    $error=array();
		if (!$rights['file']['upload']){
	        $error[]="File upload access denided";
    	    break;
	    }
	    if (empty($_FILES['file'])){
	        $error[]="File upload not included";
    	    break;
	    }
		foreach ($_FILES['file']['tmp_name'] as $key=>$val){
            $tmpfiles[$key]=array('name'=>$_FILES['file']['name'][$key],'type'=>$_FILES['file']['type'][$key],'tmp_name'=>$_FILES['file']['tmp_name'][$key],'error'=>$_FILES['file']['error'][$key],'size'=>$_FILES['file']['size'][$key]);
        }
        $_FILES['file']=$tmpfiles;
		foreach ($_FILES['file'] as $file){
			// all possible extensions
			if($file['error']==1){
		        $error[]="Ошибка загрузки файла. ".$file['name']." не был полностью загружен. Возможно, размер превышает допустимый";
		        break;
		    }elseif($file['error']!=4){
	            if($file['error']!=0){
		        $error[]="Ошибка загрузки файла ".$file['name'];
		        break;
	        }
	        $pathinfo=pathinfo($file['name']);
		    $extension=strtolower($pathinfo['extension']);
		    $filename=translit($pathinfo['filename']);
	        if(!in_array($extension,$upload_extensions)){
		        $error[]="Uploading error: ".$file['name']." wrong extension (".$extension.")";
	    	    break;
	        }
	        }else{
	            $error[]="Uploading error: ".$file['name']." not transmited!</div>";
	            break;
	        }
			if ($modify_images['format'] and is_image($extension))$extension=$modify_images['format'];
	        // find next free filename
	        $name=$basefolder.'/'.($path?($path.'/'):'').$filename;
			$i='';
			if (!$replace_when_exists){
				while (file_exists($name.$i.'.'.$extension))$i++;
			}
			$selected=$name.$i.'.'.$extension;
	        if (is_image($extension)){
			$prop=getimagesize($file['tmp_name']);
			if (empty($prop) or !in_array($prop[2],array(IMAGETYPE_JPEG,IMAGETYPE_GIF,IMAGETYPE_PNG))){
				$error[]="Uploading error: ".$file['name']." not readeble. Possible - is not image format";
				break;
			}
			$source_width=$prop[0];
			$source_height=$prop[1];
			$reduce=0.1;
			// total size calc
			if ($modify_images['max-width'])$reduce=max($reduce,$source_width/$modify_images['max-width']);
			if ($modify_images['max-height'])$reduce=max($reduce,$source_height/$modify_images['max-height']);
			if (($modify_images['aspect-ratio-modify'] or $reduce>1 or $modify_images['format']) and $modify_images['quality']){
				switch($prop[2]){
				case IMAGETYPE_JPEG:
					$image = imagecreatefromjpeg($file['tmp_name']);
					break;
				case IMAGETYPE_GIF:
					$image = imagecreatefromgif($file['tmp_name']);
					break;
				case IMAGETYPE_PNG:
					$image =imagecreatefrompng($file['tmp_name']);
					break;
				}
				$maxx=$source_width/$reduce;
				$maxy=$source_height/$reduce;
				switch($modify_images['aspect-ratio-modify']){
				case 'resize':
					if (!$modify_images['aspect-ratio-prop'])break;
					// maximize picture to save quality
					if (($source_width/$source_height)>$modify_images['aspect-ratio-prop']){
						$x=$source_width;
						$y=$source_height*($source_width/$source_height)/$modify_images['aspect-ratio-prop'];
					}else{
						$y=$source_height;
						$x=$source_width*($source_width/$source_height)/$modify_images['aspect-ratio-prop'];
					}
					$reduce=1;
					if ($modify_images['max-width'] and $x>$maxx)$reduce=$x/$maxx;
					if ($modify_images['max-height'] and $y>$maxy)$reduce=max($reduce,$y/$maxy);
					$x=$x/$reduce;
					$y=$y/$reduce;
					$copy = imagecreatetruecolor($x, $y);
					imagesavealpha($copy,true);
					$transparent = imagecolorallocatealpha($copy,255,255,255,127);
					imagefill($copy, 0, 0, $transparent);
					imagecopyresampled($copy,$image,0,0,0,0,$x,$y,$source_width,$source_height);
					break;
				case 'crop':
					if (!$modify_images['aspect-ratio-prop'])break;
					if (($source_width/$source_height)>$modify_images['aspect-ratio-prop']){
						// more wide
						$y=$source_height;
						$x=$source_width/($source_width/$source_height)*$modify_images['aspect-ratio-prop'];
						$top=0;
						$left=($source_width-$x)*$modify_images['aspect-ratio-crop-position']/100;
					}else{
						// more hight
						$x=$source_width;
						$y=$source_height/($source_width/$source_height)*$modify_images['aspect-ratio-prop'];
						$left=0;
						$top=($source_height-$y)*$modify_images['aspect-ratio-crop-position']/100;
					}
					$reduce=1;
					if ($modify_images['max-width'])$reduce=max($reduce,$x/$modify_images['max-width']);
					if ($modify_images['max-height'])$reduce=max($reduce,$y/$modify_images['max-height']);
					$result_x=$x/$reduce;
					$result_y=$y/$reduce;
					$copy = imagecreatetruecolor($result_x, $result_y);
					imagesavealpha($copy,true);
					$transparent = imagecolorallocatealpha($copy,255,255,255,127);
					imagefill($copy, 0, 0, $transparent);
					imagecopyresampled($copy,$image,0,0,$left,$top,$result_x,$result_y,$x,$y);
					break;
				case false:
					// only small size if need
					if ($reduce>1){
						$copy = imagecreatetruecolor($maxx, $maxy);
						imagesavealpha($copy,true);
						$transparent = imagecolorallocatealpha($copy,255,255,255,127);
						imagefill($copy, 0, 0, $transparent);
						imagecopyresampled($copy,$image,0,0,0,0,$maxx,$maxy,$source_width,$source_height);
					}
					break;
				default:
					$error[]='Reduce method not exist';
					imagedestroy($image);
					continue;
				}
				if ($copy){
					imagedestroy($image);
					$image=$copy;
				}
				switch($extension){
				case 'jpg':
				case 'jpeg':
					imagejpeg($image,$selected,$modify_images['quality']);
					break;
				case 'gif':
					imagegif($image,$selected);
					break;
				case 'png':
					imagepng($image,$selected,10-floor($modify_images['quality']/10));
					break;
				}
				imagedestroy($image);
			}else{
				// not modify, only copy original file
				move_uploaded_file($file['tmp_name'],$selected);
			}
			//tinypng module
			if ($tinypng_key && in_array($extension,array('jpg','png','jpeg')) && empty($tinypng_critical_error)){
				if (!$tiny_init){
					$tiny_init=true;
					if (empty(\Tinify\VERSION)){
						$error[]='TinyPng class must be required';
						$tinypng_critical_error=true;
						continue;
					}
					try {
						\Tinify\setKey($tinypng_key);
						\Tinify\validate();
					} catch(\Tinify\Exception $e) {
							$error[]='TinyPng Validation of API key failed.';
							$tinypng_critical_error=true;
							continue;
					}
				}
				try{
					for ($i=1;$i<$tinypng_repeat;$i++)\Tinify\fromFile($selected)->toFile($selected);
				}catch(Exception $exc){
					$error[]='TinyPng error '.$exc->getMessage();
				}
			}


	        }else{
	            // not image, only copy original file
				move_uploaded_file($file['tmp_name'],$selected);
	        }
		}
}
function is_image($extension){
    switch ($extension){
        case 'jpg':
        case 'jpeg':
        case 'gif':
        case 'png':
            return true;
    }
    return false;
}
function onlyimages($extensions){
    foreach ($extensions as $extension) {
        if (!is_image($extension))return false;
    }
    return true;
}
function translit($str){
	global $translit;
	if (!$translit)return $str;
	$str=mb_strtolower($str,'utf-8');
	return strtr($str,array(
	"?"=>'-quest-',"•"=>'-',"–"=>'-',">"=>'-',"<"=>'-',"%"=>'-percent-',"`"=>'-',"~"=>'-',"№"=>'-num-',";"=>'-',"#"=>'-',"*"=>'-',"@"=>'-at-',"]"=>'-',"["=>'-',"»"=>'-',"«"=>'-',":"=>'-',"\t"=>'',"\r"=>'',"\n"=>'',"\\"=>'-',"&"=>'-and-',"/"=>'-',"'"=>'-','"'=>'-'," "=>'-',
	"а"=>'a',"б"=>'b',"в"=>'v',"г"=>'g',"д"=>'d',"е"=>'e',"ё"=>'yo',"ж"=>'zh',"з"=>'z',"и"=>'i',"й"=>'j',"к"=>'k',"л"=>'l',"м"=>'m',"н"=>'n',"о"=>'o',"п"=>'p',"р"=>'r',"с"=>'s',"т"=>'t',"у"=>'u',"ф"=>'f',"х"=>'kh',"ц"=>'c',"ч"=>'ch',"ш"=>'sh',"щ"=>'sch',"ъ"=>'',"ы"=>'y',"ь"=>'',"э"=>'e',"ю"=>'yu',"я"=>'ja'
	));
}
header('Content-Type: text/html; charset=utf-8');
?>
<meta name="viewport" content="width=device-width,user-scalable=no" />
<meta charset="UTF-8">
<title>Open-filemanager</title>
<script>
var csrf='<?=$csrf?>';
var folder='<?=$folder?>';
<?if (isset($_GET['config'])){?>var config_file='<?=htmlspecialchars($_GET['config'])?>';<?}?>
<?if (isset($_GET['choose'])){?>var choose_function='<?=htmlspecialchars($_GET['choose'])?>';<?}?>
</script>

<?foreach ($include as $item){
switch ($item['type']){
	case 'js':
		?><script src="<?=$item['href']?>"></script><?
		break;
	case 'css':
		?><link href="<?=$item['href']?>" rel="stylesheet" type="text/css" /><?
		break;
}
}?>
<div class="open-filemanager">
<div class="dark">
<div>
<h1>Open-filemanager</h1><span>v 2.8b</span>
<?if ($rights['file']['choose']){?><p>Doubleclick to choose file</p><?}?>
<p><b>Open-filemanager</b> - free simple opensource web-filemanager. You may use it for free with no frames</p>
<p>Use product on own risk. Author not have responsibility for product usage</p>
<p>Project site: <a href="http://kosmom.ru/web/open-filemanager" target="_blank">http://kosmom.ru/web/open-filemanager</a></p>
</div>
</div>
<div class="panel">
    <?if ($rights['folder']['create']){?><a onclick="create_folder()" class="create_dialog">Создать папку</a><?}?>
	<?if ($rights['file']['upload']){?><form method="POST" enctype="multipart/form-data"><a class="upload">Залить файлы</a><input type="hidden" name="csrf" value="<?=$csrf?>"> <input <?=onlyimages($upload_extensions)?'accept="image/*"':''?> type="file" name="file[]" multiple name="upload" onchange="$(this).closest('form').submit();" /><input type="hidden" name="act" value="upload"></form><?}?>
	<?if ($rights['folder']['rename']||$rights['file']['rename']){?><a onclick="rename()" class="rename">Переименовать</a><?}?>
	<?if ($rights['folder']['delete']||$rights['file']['delete']){?><a onclick="delete_()" class="delete">Удалить</a><?}?>
	<?foreach ($modules as $key=>$item){?>
	<a id="plugin-<?=$key?>" onclick="open_module('<?=$item['link']?>')" class="module"><?=$item['header']?></a>
	<script>
		register_plugin('<?=$key?>',<?=$item['for']?$item['for']:true?>);
	</script>
	<?}?>
	<a onclick="$('.dark').fadeIn(999);" class="right">?</a>
</div>
	<?if (isset($error))foreach ($error as $err){?><div class="error"><?=$err?></div><?}?>
<div class="main-folder">
<?
if ($show_files){
	foreach ($show_files as $item=>$val){
		if (is_dir($full_name.'/'.$item)){
			if (!$rights['folder']['read'])continue;
		    $folders[$item]=$path;
		}elseif (is_file($full_name.'/'.$item)){
			if (!$rights['file']['read'])continue;
		    $files[$item]=$path;
		}
	}
}else{
	foreach (scandir($full_name) as $filename) {
		if (isset($hide_files[$filename]))continue;
		if ($filename=='.')continue;
		if ($filename=='..')continue;
		if (is_dir($full_name.'/'.$filename)){
		    if (!$rights['folder']['read'])continue;
		    $folders[$filename]=$path;
		}else{
                    if (!$rights['file']['read'])continue;
		    $files[$filename]=$path;
		}
	}
}
if ($path!=''){?><div class="folder open" onclick="select(this,'<?=$backpath?>')" data-folder='<?=$backpath?>/'><b>..</b></div><?}
if ($folders)foreach ($folders as $filename=>$path){?>
<div class="folder closed" onclick="select(this)" data-folder='<?=$path?>/'><b title="<?=$filename?>"><?=$filename?></b></div>
<?}?>
<?if ($files)foreach ($files as $filename=>$path){?>
<?if (is_image(strtolower(substr(strrchr($filename, '.'), 1)))){?>
<div onclick="select(this)" <?if ($rights['file']['choose']){?>data-choose="y"<?}?> class="image<?if ($selected==$filename){?> selected<?}?>" data-folder='<?=$basefolder?><?=$path?>/'><img <?if ($lazy_load){?>class="lazy-load" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-<?}?>src="<?=$basehttp?><?=$basefolder?><?=$path?>/<?=$filename?>"><b title="<?=$filename?>"><?=$filename?></b></div>
<?}else{?>
<div onclick="select(this)" <?if ($rights['file']['choose']){?>data-choose="y"<?}?> class="file<?if ($selected==$filename){?> selected<?}?>" data-folder='<?=$basefolder?><?=$path?>/'><b title="<?=$filename?>"><?=$filename?></b></div>
<?}?>
<?}?>

</div>
</div>
