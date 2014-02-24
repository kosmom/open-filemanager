open-filemanager
================

free filemanager for tinymce 3-4

Бесплатный файлменеджер для управления файлами. Легкий вес, быстрая загрузка, легко управлять и исправлять. Поддержка транслита загружаемых изображений и каталогов, гибкая настройка прав, преобразования изображений, мультизагрузка

Требования
==========

- PHP 5.2+
- Наличие Jquery
- IE 8+ (или любой другой браузер)
- GD библиотека (для возможности преобразовывать изображения)

Настройка
=========
```php
$basefolder='images/userfiles'; // базовая директория для работы с изображениями
$upload_extensions=array('gif','jpeg','jpg','png');  // допустимые расширения файлов для загрузки
$basehttp='http://'.$_SERVER['HTTP_HOST'].'/'; // путь к начальной папке с сайтом
$replace_when_exists=true; // замена изображения при совпадении имен
$lazy_load=true; // ленивая загрузка изображений (включена по умолчанию)

$modify_images=array(
	'aspect-ratio-modify'=>'crop', // преобразование изображений к нужным пропорциям (crop,resize,false)
	'aspect-ratio-crop-position'=>100, // выбор части обрезаемого изобржения - при обрезании
	'aspect-ratio-prop'=>2/3, // пропорции итогового изображения
	'max-width'=>false, // максимальная ширина изображения
	'max-height'=>1000, // максимальня высота изображения
	'quality'=>50, // качество изображения (от 0 до 100)
	'format'=>false // исходный формат. (jpg,png,gif,false==source)
);

// права доступа. Установите нужный показатель, например переменную в сессии, например
if ($_SESSION['read-only']){
$rights=array('access'=>true,'file'=>array('read'=>true,'choose'=>true),'folder'=>array('read'=>true));
}

if (!isset($rights))$rights=array();
// access
// flle - read,delete,rename,upload,choose
// folder - read,delete,rename,create
```

```html
<script src="<?=$basehttp?>/js/jquery.js"></script> - исправьте на путь к скриптам
<?if ($lazy_load){?><script src="<?=$basehttp?>/js/lazyload.js"></script><?}?>
<script src="<?=$basehttp?>/js/open-filemanager.js"></script>
<link href="<?=$basehttp?>/css/open-filemanager.css" rel="stylesheet" type="text/css" />
```

Для отключения транслита- измените функцию translit на
```php
function translit($str){
	return $str;
}
```

Настройка для работы с tinymce 4. Пример шаблона
===============================================
```js
tinymce.init({
		...
	file_browser_callback: function(field_name, url, type, win) {
			tinyMCE.activeEditor.windowManager.open({
		        url: "open-filemanager.php",
		        width: 782,
		        height: 440,
		        close_previous: "no",
		        inline: "yes"
			}, {
			      window : win,
		        input: field_name
		    });
	    }

	});
```

Для встраивания менеджера внутрь своей формы и обрамления - используйте буферизацию в логическом файле
Пример
```php
$rights=array('access'=>true, 'file'=>array('read'=>true,'delete'=>true,'rename'=>true,'upload'=>true));
ob_start();
require 'open-filemanager.php';
$manager=ob_get_contents();
ob_clean();
```
и вывод в файле (месте) шаблонов
```html
<div class="wrapper" style="margin: 20px;border: 1px solid #333;">
<?=$manager?>
</div>
```


Планы
=====

- Работа под ckeditor
- Убрать зависимость от jquery
- скины
- локализация

Страница проекта
===============
http://kosmom.ru/web/open-filemanager
