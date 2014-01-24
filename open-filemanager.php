<?php
session_start();
// источник изображений
$basefolder='images/userfiles';
$upload_extensions=array('gif','jpeg','jpg','png'); // в нижнем регистре
$rights=$_SESSION['user']?3:0;
// 1-read
// 2-upload
// 3-add-folder
// 4-rename+delete





if (empty($rights))die('Доступ запрещен');

$scriptfolder=dirname($_SERVER['SCRIPT_FILENAME']);
$folder=$_GET['folder'];
$full_name=realpath($basefolder.$folder);
if (!$full_name)die('Базовой директории '.$basefolder.' не существует');

if (substr(str_replace('\\','/',$full_name),0,strlen($scriptfolder.$basefolder)+1)!=$scriptfolder."/".$basefolder)die('<br>Директория указана не верно');
$path=substr(str_replace('\\','/',$full_name),strlen($scriptfolder.$basefolder)+1);
$backpath=substr(str_replace('\\','/',realpath($basefolder.$folder.'/..')),strlen($scriptfolder.$basefolder)+1);
switch($_POST['act']){
	case 'add_folder':
		if ($rights<3)die('Добавление папок запрещено');
	    $folder=translit($_POST['folder']);
		if (is_dir($basefolder.'/'.($path?($path.'/'):'').$folder))die('Данная папка уже существует');
		mkdir($basefolder.'/'.($path?($path.'/'):'').$folder);
		die('done');
	case 'delete':
	    if ($rights<4)die('Удаление запрещено');
		$path=$basefolder.'/'.($path?($path.'/'):'').$_POST['name'];
		if (is_dir($path)){
			if (count(glob($path."/*")))die('Директория не пуста. Перед удалением - необходимо очистить директорию от файлов и вложеных файлов');
			rmdir($path);
		}
		if (is_file($path))unlink($path);
		die('done');
	case 'rename':
		if ($rights<4)die('Переименование запрещено');
	    $oldpath=$basefolder.'/'.($path?($path.'/'):'').$_POST['name'];
		$newpath=$basefolder.'/'.($path?($path.'/'):'').translit($_POST['newname']);
		if (!file_exists($oldpath))die('Файла или директории - не существует');
		rename($oldpath,$newpath);
		die('done');
	case 'upload':
	    if ($rights<2){
	        echo "<div class='error'>Ошибка: Заливка файлов запрещена</div>";
    	    break;
	    }
	    if (empty($_FILES['file'])){
	        echo "<div class='error'>Ошибка: Файл не отправлен</div>";
    	    break;
	    }
	    $file=$_FILES['file'];
        // все возможные расширения (без учета регистра)
        if($file['error']==1){
	        echo "<div class='error'>Ошибка: Ошибка загрузки файла. Файл не был полностью загружен. Возможно, размер превышает допустимый</div>";
	        break;
	    }elseif($file['error']!=4){
            if($file['error']!=0){
	        echo "<div class='error'>Ошибка: Ошибка загрузки файла</div>";
	        break;
        }
        $pathinfo=pathinfo($file['name']);
	    $extension=strtolower($pathinfo['extension']);
	    $filename=translit($pathinfo['filename']);
        if(!in_array($extension,$upload_extensions)){
	        echo "<div class='error'>Ошибка: Недопустимое расширение файла (".$extension.")</div>";
    	    break;
        }
        }else{
            echo "<div class='error'>Ошибка: Файл не был вложен!</div>";
            break;
        }

        // заливка свободным номером
        $i='';
        $name=$basefolder.'/'.($path?($path.'/'):'').$filename;
        while (file_exists($name.$i.'.'.$extension)){
            $i++;
        }
        move_uploaded_file($file['tmp_name'],$name.$i.'.'.$extension);
        $selected=$filename.$i.'.'.$extension;
}
function translit($str){
	return strtr($str,array(
	"?"=>'-vopros-',"•"=>'-',"–"=>'-',">"=>'-',"<"=>'-',"%"=>'-procent-',"`"=>'-',"~"=>'-',"№"=>'-nomer-',";"=>'-',"#"=>'-',"*"=>'-',"@"=>'-at-',"]"=>'-',"["=>'-',"»"=>'-',"«"=>'-',":"=>'-',"\t"=>'',"\r"=>'',"\n"=>'',"\\"=>'-',"&"=>'-and-',"/"=>'-',"'"=>'-','"'=>'-'," "=>'-',
	"а"=>'a',"б"=>'b',"в"=>'v',"г"=>'g',"д"=>'d',"е"=>'e',"ё"=>'yo',"ж"=>'zh',"з"=>'z',"и"=>'i',"й"=>'j',"к"=>'k',"л"=>'l',"м"=>'m',"н"=>'n',"о"=>'o',"п"=>'p',"р"=>'r',"с"=>'s',"т"=>'t',"у"=>'u',"ф"=>'f',"х"=>'kh',"ц"=>'c',"ч"=>'ch',"ш"=>'sh',"щ"=>'sch',"ъ"=>'',"ы"=>'y',"ь"=>'',"э"=>'e',"ю"=>'yu',"я"=>'ja'
	));
}
?>
<title>Open-filemanager</title>
<script src="js/jquery.js"></script>
<script src="js/open-filemanager.js"></script>
<link href="css/open-filemanager.css" rel="stylesheet" type="text/css" />

<div class="dark">
<div>
<h1>Open-filemanager</h1>
<p>Дважды щелкните на файл, чтобы выбрать его</p>
<p><b>Open-filemanager</b> - простой бесплатный файл-менеджер с открытым исходным кодом. Вы можете использовать его как угодно, где угодно и когда угодно без каких-либо ограничений</p>
<p>Используйте продукт на свой страх и риск. Автор не несет ответственности за использование данного продукта</p>
<p>Сайт проекта: <a href="http://kosmom.ru/web/open-filemanager" target="_blank">http://kosmom.ru/web/open-filemanager</a></p>
</div>
</div>
<div class="panel">
    <?if ($rights>2){?><a onclick="create_folder()" class="create_dialog">Создать папку</a><?}?>
	<?if ($rights>1){?><form method="POST" enctype="multipart/form-data">
<a class="upload">Залить файл</a><input type="file" name="file" name="upload" onchange="$(this).closest('form').submit();" /><input type="hidden" name="act" value="upload">
	</form><?}?>
	<?if ($rights>3){?><a onclick="rename()" class="rename">Переименовать</a>
	<a onclick="delete_()" class="delete">Удалить</a>
	<?}?>
	<a onclick="$('.dark').fadeIn(1000);" class="right">?</a>
</div>
<div class="folder">
<?


foreach (scandir($full_name) as $filename) {
if ($filename=='.')  continue;
if ($filename=='..'){
    if ($path=='')continue;
    ?>
	<div onclick="select(this)" ondblclick="set_folder(this,'<?=$backpath?>')" folder='<?=$backpath?>/'><b>..</b></div>
<?
continue;
}
if (is_dir($full_name.'/'.$filename)){
?>
	<div onclick="select(this)" ondblclick="set_folder(this)" folder='<?=$path?>/'><b title="<?=$filename?>"><?=$filename?></b></div>
<?}else{?>
	<div onclick="select(this)" ondblclick="set_image(this)" <?if ($selected==$filename){?>class="selected"<?}?> folder='<?=$basefolder?><?=$path?>/'><img src="<?=$basefolder?><?=$path?>/<?=$filename?>"><b title="<?=$filename?>"><?=$filename?></b></div>
<?}?>
<?}?>
</div>
