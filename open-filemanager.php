<?php
session_start();
// источник изображений
$basefolder='images/photo';
$upload_extensions=array('gif','jpeg','jpg','png'); // в нижнем регистре
$basehttp='http://'.$_SERVER['HTTP_HOST'].'/';
$replace_when_exists=false;
$lazy_load=true;
$translit=true;
$soft_check=0;
$modify_images=array(
	'aspect-ratio-modify'=>false, // resize,crop,false
	'aspect-ratio-crop-position'=>100,
	'aspect-ratio-prop'=>2/3,
	'max-width'=>848,
	'max-height'=>false,
	'quality'=>50,
	'format'=>false // jpg,png,gif,false==source
);

$include=array(
	array('type'=>'js','href'=>'//code.jquery.com/jquery-1.11.0.min.js'),
	array('type'=>'js','href'=>$basehttp.'/js/open-filemanager.js'),
	array('type'=>'css','href'=>$basehttp.'/css/open-filemanager.css'),
);
if ($lazy_load)$include[]=array('type'=>'js','href'=>$basehttp.'/js/lazyload.js');

if (!isset($rights))$rights=array();
// access
// flle - read,delete,rename,upload,choose
// folder - read,delete,rename,create
$config_file='open-filemanager-config.php';
if (isset($_GET['config']))$config_file=$_GET['config'].'.php';
$open_filemanager=true;
if (file_exists($config_file))require $config_file;
if (empty($rights['access']))die('Доступ запрещен');

/*********************code next*************************/
$scriptfolder=dirname($_SERVER['SCRIPT_FILENAME']);
$folder=$_GET['folder'];
$full_name=realpath($basefolder.$folder);
if (!$full_name)die('Базового каталога '.$basefolder.' не существует');
if (substr(substr(str_replace('\\','/',$full_name),0,strlen($scriptfolder.$basefolder)+1),$soft_check)!=substr($scriptfolder."/".$basefolder,$soft_check))die('Каталог '.$basefolder.' указан не верно');
$path=substr(str_replace('\\','/',$full_name),strlen($scriptfolder.$basefolder)+1);
$backpath=substr(str_replace('\\','/',realpath($basefolder.$folder.'/..')),strlen($scriptfolder.$basefolder)+1);
switch($_POST['act']){
	case 'add_folder':
		if (!$rights['folder']['create'])die('Добавление каталогов запрещено');
	    $folder=translit($_POST['folder']);
		if (is_dir($basefolder.'/'.($path?($path.'/'):'').$folder))die('Данный каталог уже существует');
		mkdir($basefolder.'/'.($path?($path.'/'):'').$folder);
		die('done');
	case 'delete':
	    $path=$basefolder.'/'.($path?($path.'/'):'').$_POST['name'];
		if (is_dir($path)){
			if (!$rights['folder']['delete'])die('Удаление каталогов запрещено');
		}else{
			if (!$rights['file']['delete'])die('Удаление файлов запрещено');
		}
		if (is_dir($path)){
			if (count(glob($path."/*")))die('Директория не пуста. Перед удалением - необходимо очистить директорию от файлов и вложеных файлов');
			rmdir($path);
		}
		if (is_file($path))unlink($path);
		die('done');
	case 'rename':
		$oldpath=$basefolder.'/'.($path?($path.'/'):'').$_POST['name'];
		if (is_dir($oldpath)){
			if (!$rights['folder']['rename'])die('Переименование каталогов запрещено');
		}else{
			if (!$rights['file']['rename'])die('Переименование файлов запрещено');
		}
		$newpath=$basefolder.'/'.($path?($path.'/'):'').translit($_POST['newname']);
		if (!file_exists($oldpath))die('Файла или каталога - не существует');
		rename($oldpath,$newpath);
		die('done');
	case 'upload':
	    $error=array();
		if (!$rights['file']['upload']){
	        $error[]="Ошибка: Заливка файлов запрещена";
    	    break;
	    }
	    if (empty($_FILES['file'])){
	        $error[]="Ошибка: Файлы не вложены";
    	    break;
	    }

		foreach ($_FILES['file']['tmp_name'] as $key=>$val){
            $tmpfiles[$key]=array('name'=>$_FILES['file']['name'][$key],'type'=>$_FILES['file']['type'][$key],'tmp_name'=>$_FILES['file']['tmp_name'][$key],'error'=>$_FILES['file']['error'][$key],'size'=>$_FILES['file']['size'][$key]);
        }
        $_FILES['file']=$tmpfiles;
		foreach ($_FILES['file'] as $file){
			// все возможные расширения (без учета регистра)
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
		        $error[]="Ошибка загрузки файла: ".$file['name']." Недопустимое расширение (".$extension.")";
	    	    break;
	        }
	        }else{
	            $error[]="Ошибка загрузки файла: ".$file['name']." не передан!</div>";
	            break;
	        }
			if ($modify_images['format'])$extension=$modify_images['format'];

	        // заливка свободным номером
	        $name=$basefolder.'/'.($path?($path.'/'):'').$filename;
			$i='';
			if (!$replace_when_exists){
				while (file_exists($name.$i.'.'.$extension)){
		            $i++;
		        }
			}
			$selected=$name.$i.'.'.$extension;
	        $prop=getimagesize($file['tmp_name']);
			if (empty($prop)){
				$error[]="Ошибка загрузки файла: ".$file['name']." не удается прочесть. Вероятно - он не содержит изображение";
				break;
			}
			if (!in_array($prop[2],array(IMAGETYPE_JPEG,IMAGETYPE_GIF,IMAGETYPE_PNG))){
				$error[]="Ошибка загрузки файла: ".$file['name']." не удается прочесть. Вероятно - он не содержит изображение";
				break;
			}
			$source_width=$prop[0];
			$source_height=$prop[1];

			$reduce=0.1;
			// расчет итогового размера
			if ($modify_images['max-width'])$reduce=max($reduce,$source_width/$modify_images['max-width']);
			if ($modify_images['max-height'])$reduce=max($reduce,$source_height/$modify_images['max-height']);

			if (($modify_images['aspect-ratio-modify'] or $reduce>1 or $modify_images['format']) and $modify_images['quality']){
				// преобразования. Требуется много памяти для открытия больших изображений
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

				// преобразования пропорций
				switch($modify_images['aspect-ratio-modify']){
				case 'resize':
					if (!$modify_images['aspect-ratio-prop'])break;
					// расширяем изображение до нужных пропорций, с целью сохранения качества
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
						// шире чем нужно
						$y=$source_height;
						$x=$source_width/($source_width/$source_height)*$modify_images['aspect-ratio-prop'];
						$top=0;
						$left=($source_width-$x)*$modify_images['aspect-ratio-crop-position']/100;
					}else{
						// выше чем нужно
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
					// только уменьшаем изображение, если требуется
					if ($reduce>1){
						$copy = imagecreatetruecolor($maxx, $maxy);
						imagesavealpha($copy,true);
						$transparent = imagecolorallocatealpha($copy,255,255,255,127);
						imagefill($copy, 0, 0, $transparent);
						imagecopyresampled($copy,$image,0,0,0,0,$maxx,$maxy,$source_width,$source_height);
					}
					break;
				default:
					$error[]='Указан несуществующий способ сжатия';
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
				// преобразования не требуются, просто копируем файл
				move_uploaded_file($file['tmp_name'],$selected);
			}
		}
}
function translit($str){
	global $translit;
	if (!$tranlit)return $str;
	return strtr($str,array(
	"?"=>'-quest-',"•"=>'-',"–"=>'-',">"=>'-',"<"=>'-',"%"=>'-percent-',"`"=>'-',"~"=>'-',"№"=>'-num-',";"=>'-',"#"=>'-',"*"=>'-',"@"=>'-at-',"]"=>'-',"["=>'-',"»"=>'-',"«"=>'-',":"=>'-',"\t"=>'',"\r"=>'',"\n"=>'',"\\"=>'-',"&"=>'-and-',"/"=>'-',"'"=>'-','"'=>'-'," "=>'-',
	"а"=>'a',"б"=>'b',"в"=>'v',"г"=>'g',"д"=>'d',"е"=>'e',"ё"=>'yo',"ж"=>'zh',"з"=>'z',"и"=>'i',"й"=>'j',"к"=>'k',"л"=>'l',"м"=>'m',"н"=>'n',"о"=>'o',"п"=>'p',"р"=>'r',"с"=>'s',"т"=>'t',"у"=>'u',"ф"=>'f',"х"=>'kh',"ц"=>'c',"ч"=>'ch',"ш"=>'sh',"щ"=>'sch',"ъ"=>'',"ы"=>'y',"ь"=>'',"э"=>'e',"ю"=>'yu',"я"=>'ja'
	));
}
?>
<title>Open-filemanager</title>
<?if (isset($_GET['config']) || isset($_GET['choose'])){?>
<script>
<?if (isset($_GET['config'])){?>var config_file='<?=htmlspecialchars($_GET['config'])?>';<?}?>
<?if (isset($_GET['choose'])){?>var choose_function='<?=htmlspecialchars($_GET['choose'])?>';<?}?>
</script>
<?}?>

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
<h1>Open-filemanager</h1><span>v 2.2</span>
<?if ($rights['file']['choose']){?><p>Дважды щелкните на файл, чтобы выбрать его</p><?}?>
<p><b>Open-filemanager</b> - простой бесплатный файл-менеджер с открытым исходным кодом. Вы можете использовать его как угодно, где угодно и когда угодно без каких-либо ограничений</p>
<p>Используйте продукт на свой страх и риск. Автор не несет ответственности за использование данного продукта</p>
<p>Сайт проекта: <a href="http://kosmom.ru/web/open-filemanager" target="_blank">http://kosmom.ru/web/open-filemanager</a></p>
</div>
</div>
<div class="panel">
    <?if ($rights['folder']['create']){?><a onclick="create_folder()" class="create_dialog">Создать папку</a><?}?>
	<?if ($rights['file']['upload']){?><form method="POST" enctype="multipart/form-data"><a class="upload">Залить файлы</a><input type="file" name="file[]" multiple name="upload" onchange="$(this).closest('form').submit();" /><input type="hidden" name="act" value="upload"></form><?}?>
	<?if ($rights['folder']['rename']||$rights['file']['rename']){?><a onclick="rename()" class="rename">Переименовать</a><?}?>
	<?if ($rights['folder']['delete']||$rights['file']['delete']){?><a onclick="delete_()" class="delete">Удалить</a><?}?>
	<a onclick="$('.dark').fadeIn(999);" class="right">?</a>
</div>
	<?if (isset($error))foreach ($error as $err){?><div class="error"><?=$err?></div><?}?>
<div class="folder">
<?

foreach (scandir($full_name) as $filename) {
if ($filename=='.')  continue;
if ($filename=='..'){
    if ($path=='')continue;
    if (!$rights['folder']['read'])continue;
    ?>
	<div class="folder open" onclick="select(this)" ondblclick="set_folder(this,'<?=$backpath?>')" folder='<?=$backpath?>/'><b>..</b></div>
<?
continue;
}
if (is_dir($full_name.'/'.$filename)){
if (!$rights['folder']['read'])continue;
?>
	<div class="folder closed" onclick="select(this)" ondblclick="set_folder(this)" folder='<?=$path?>/'><b title="<?=$filename?>"><?=$filename?></b></div>
<?}else{
	if (!$rights['file']['read'])continue;?>
	<div onclick="select(this)" <?if ($rights['file']['choose']){?>ondblclick="set_image(this)"<?}?> <?if ($selected==$filename){?>class="selected"<?}?> folder='<?=$basefolder?><?=$path?>/'><img <?if ($lazy_load){?>class="lazy-load" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-<?}?>src="<?=$basehttp?><?=$basefolder?><?=$path?>/<?=$filename?>"><b title="<?=$filename?>"><?=$filename?></b></div>
<?}?>
<?}?>
</div>
<div class="clear"></div>
</div>
