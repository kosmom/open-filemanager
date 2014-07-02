Open-filemanager
================

free filemanager for tinymce 3-4, ckeditor 4 or standalone version

Бесплатный файлменеджер для управления файлами. Легкий, гибкий, быстрая загрузка, легко настраивать и расширять. Поддержка транслита, преобразование изображений, гибкая настройка прав на каждый каталог в отдельности, мультизагрузка, CSRF защита, поддержка модулей

Требования
==========

- PHP 5.2+
- Наличие Jquery
- IE 8+ (или любой другой браузер)
- GD библиотека (для возможности преобразовывать изображения)

Настройка
=========
Вся настройка параметров указана в самом начале файла. Для удобства обновления - вы можете настроить файл open-fileserver-config.php в аналогичной папке с изменением настроек по умолчанию, или использовать свой файл с конфигурацией, указав его в GET['config'] параметре.
Если параметр передан - файл с настройками должен иметь путь с open-filemanager-config-{GET['config']}.php

Пример файла конфигурации для файл-менеджера

```php
if (!$open_filemanager)die('sorry');

$basefolder='images/userfiles'; // базовая директория для работы с изображениями
$upload_extensions=array('gif','jpeg','jpg','png');  // допустимые расширения файлов для загрузки
$csrf_secret='mysecretphrase'; // ваш защитный ключ для обеспечения CSRF защиты
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

// скрытие и отображение только конкретных файлов в каталоге. Имена файлов должны идти в качестве ключей массивов
$show_files=array(); // key as filename/foldername
$hide_files=array(); // key as filename/foldername

// права доступа. Установите нужный показатель, например переменную в сессии, например
if ($_SESSION['read-only']){
$rights=array('access'=>true,'file'=>array('read'=>true,'choose'=>true),'folder'=>array('read'=>true));
// access
// flle - read,delete,rename,upload,choose
// folder - read,delete,rename,create

}
```

Настройка для работы с tinymce 4. Пример подключения
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

Настройка для работы с ckeditor 4. Пример подключения
===============================================
```js
$('textarea').ckeditor({
	...
	filebrowserBrowseUrl :'open-filemanager.php',
	filebrowserImageBrowseUrl : 'open-filemanager.php',
	...
});
```

Вызов файл-менеджера в качестве автономного решения
==================================================

В примере указывается подключение отдельного файла с конфигурацией open-filemanager-config-content.php в аналогичном каталоге и передается функция обратного вызова set_pp. На случай обращения из множества полей на одной странице. Не забудьте предоставить права на выбор файла $rights['file']['choose']=true; в конфигурации

```js
window.open("open-filemanager.php?config=content&choose=set_pp", "get_image", "width=800,height=800,status=no,toolbar=no,menubar=no,scrollbars=yes");
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

Пример файла конфигурации для определения настроек в зависимости от каталога
============================================================================

Структура приведенная ниже позволяет задать для каждой папки свою группу любых настроек, от прав, до правил обработки изображений. Все настройки накладываются на предыдущие, если они указаны в списке
```php
// права по умолчанию
$rights=array('access'=>true,'file'=>array('read'=>false,'delete'=>false,'rename'=>false,'upload'=>false,'choose'=>false),'folder'=>array('read'=>true,'delete'=>false,'rename'=>false,'create'=>false));

// for each folder
if (!$_GET['folder'])return true;
$links=explode('/',substr($_GET['folder'],1));
foreach ($links as $item){
$folder[]=$item;
switch (implode('/',$folder)){
	case 'myimages':
		// категория пользователя. В ней у пользователя есть все права
		$rights=array('access'=>true,'file'=>array('read'=>true,'delete'=>true,'rename'=>true,'upload'=>true,'choose'=>true),'folder'=>array('read'=>true,'delete'=>true,'rename'=>true,'create'=>true));
		
		break;
	case 'myimages/blog':
		// категория блогов для пользователя. В ней у пользователя действует ограниченый список прав и существуют свои правила для заливки изображений
		$rights['folder']=array('read'=>false,'delete'=>false,'rename'=>false,'create'=>false);
		$modify_images=array(
			'aspect-ratio-modify'=>'crop',
			'aspect-ratio-crop-position'=>100,
			'aspect-ratio-prop'=>2/3,
			'max-width'=>false,
			'max-height'=>1000,
			'quality'=>50,
			'format'=>'jpg'
		);
	case 'public':
		// публичная категория. В ней у пользователя есть права только на просмотр
		$rights=array('access'=>true,'file'=>array('read'=>true,'delete'=>false,'rename'=>false,'upload'=>false,'choose'=>true),'folder'=>array('read'=>true,'delete'=>false,'rename'=>false,'create'=>false));
		break;
}
}
```

Подключение собственных модулей
===============================

У вас есть возможность встроить и вызывать свои модули для работы с open-filemanager. Будь то генератор текстов, изображений, заливальщик, преобразователь и т.д.
Чтобы зарегистрировать модуль - нужно указать его в архиве $modules в виде структуры
```php
$modules['text-generator']=array(
	'header'=>'Создать надпись', // заголовок в меню
	'link'=>'gettext.html' // ссылка на файл модуля
);
```

Модуль откроется в отдельном окне. Для получения модулем текущего каталога open-filemanager - используйте код
```js
var folder=opener.folder;
```

Для получения текущего выделенного объекта - можете использовать конструкцию вида
```js
var selected=opener.$('.selected').find('b').text();
```

Примечание: Проверка прав на каталог осуществляется в рамках модуля. Open-filemanager лишь вызывает его с указанием директории и позволяет обратиться и произвести какие-либо манипуляции через объект opener.

Краткая история версий
======================
2.4
- Поддержка CKEditor 4

2.3
- CSRF защита
- Формат изображений для загрузки - по умолчанию выбран изображения
- Добавлена иконка для директорий
- Директории располагаются в самом начале, а не в перемешку с файлами
- Увеличена область показа миниатюрки
- Добавлена поддержка собственных модулей
- Добавлена возможность настроить отдельные настройки на каждый каталог в отдельности
- Добавлена возможность скрыть конкретные файлы, или отобразить только указаные файлы из всего каталога
- Небольшие исправления с транскрипцией

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

- локализация
- Drag&Drop upload
- Поиск файлов
- Отображение хлебных крошек
- Перестройка работы в качестве класса

Страница проекта
===============
http://kosmom.ru/web/open-filemanager
