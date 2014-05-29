Open-filemanager
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
Вся настройка параметров указана в самом начале файла. Для удобства обновления - вы можете настроить файл open-fileserver-config.php в аналогичной папке с изменением настроек по умолчанию, или использовать свой файл с конфигурацией, указав его в GET['config'] параметре.

Пример файла конфигурации для файл-менеджера

```php
if (!$open_filemanager)die('sorry');

$basefolder='images/userfiles'; // базовая директория для работы с изображениями
$upload_extensions=array('gif','jpeg','jpg','png');  // допустимые расширения файлов для загрузки
$basehttp='http://'.$_SERVER['HTTP_HOST'].'/'; // путь к начальной папке с сайтом
$replace_when_exists=true; // замена изображения при совпадении имен
$lazy_load=true; // ленивая загрузка изображений (включена по умолчанию)
$translit=true; // включен ли транслит (рекомендуется)
$soft_check=0; // более мягкая проверка путей. Проверяться будут на схожесть последние soft_check символов
$modify_images=array(
	'aspect-ratio-modify'=>'crop', // преобразование изображений к нужным пропорциям (crop,resize,false)
	'aspect-ratio-crop-position'=>100, // выбор части обрезаемого изобржения - при обрезании
	'aspect-ratio-prop'=>2/3, // пропорции итогового изображения
	'max-width'=>false, // максимальная ширина изображения
	'max-height'=>1000, // максимальня высота изображения
	'quality'=>50, // качество изображения (от 0 до 100)
	'format'=>false // исходный формат. (jpg,png,gif,false==source)
);

// подгружаемые скрипты. Если вы захотите расположить их где-то в другом месте - можете изменить их положение легко
$include=array( 
	array('type'=>'js','href'=>'//code.jquery.com/jquery-1.11.0.min.js'),
	array('type'=>'js','href'=>$basehttp.'/js/open-filemanager.js'),
	array('type'=>'css','href'=>$basehttp.'/css/open-filemanager.css'),
);
if ($lazy_load)$include[]=array('type'=>'js','href'=>$basehttp.'/js/lazyload.js');


// права доступа. Установите нужный показатель, например переменную в сессии, например
if ($_SESSION['read-only']){
$rights=array('access'=>true,'file'=>array('read'=>true,'choose'=>true),'folder'=>array('read'=>true));
// access
// flle - read,delete,rename,upload,choose
// folder - read,delete,rename,create

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

Вызов файл-менеджера в качестве автономного решения
==================================================

В примере указывается подключение отдельного файла с конфигурацией open-filemanager-config-content.php в аналогичном каталоге и передается функция обратного вызова set_pp. На случай обращения из множества полей на одной странице. Не забудьте предоставить права на выбор файла $rights['file']['choose']=true; в конфигурации

```js
window.open("open-filemanager.php?config=open-filemanager-config-content&choose=set_pp", "get_image", "width=800,height=800,status=no,toolbar=no,menubar=no,scrollbars=yes");
function set_pp(image){
	alert(image);
}
```

Встраивание менеджера inline внутри страницы
=====================================

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

Краткая история версий
======================

2.2
- Возможность указания нечеткой проверки путей soft_check
- Вызов в качестве автономного пикера файлов
- Возможность вызова файл-менеджера с разными конфигурациями (для разнообразного использования в рамках одного сайта)

2.1
- Все настройки перечислены до кодов для удобной конфигурации

2.0
- Мультизаливка файлов
- Гибкая настройка прав
- Преобразование изображений
- Замена изображений при совпадении имен
- Встраивание менеджера внутрь страницы
- Возможность указать собственные настройки в отдельном файле для удобства обновления

1.0
- Базовый функционал
- Настройка прав
- Транслит
- Заливка файлов
- Выбор файлов для TinyMCE 3 и TinyMCE 4

Планы
=====

- Работа под ckeditor
- Убрать зависимость от jquery
- скины
- локализация

Страница проекта
===============
http://kosmom.ru/web/open-filemanager
