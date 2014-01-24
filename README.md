open-filemanager
================

free filemanager for tinymce 3-4

Бесплатный файлменеджер для управления файлами. Легкий вес, быстрая загрузка, легко управлять и исправлять. Поддержка транслита загружаемых изображений и каталогов

Требования
==========

- PHP 5.2+
- Наличие Jquery
- IE 8+ (или любой другой браузер)

Настройка
=========
```js
$basefolder='images/userfiles'; // базовая директория для работы с изображениями
$upload_extensions=array('gif','jpeg','jpg','png'); // допустимые расширения файлов для загрузки
$rights=$_SESSION['user']?3:0; // права доступа. Установите нужный показатель, например переменную в сессии
/*
- 0 - доступ запрещен
- 1 - доступ на чтение
- 2 - доступ на заливку
- 3 - заливка и создание папок
- 4 - заливка, создание папок, переименование, удаление
*/
```

```html
<script src="js/jquery.js"></script> - исправьте на путь к jquery
<script src="js/open-filemanager.js"></script>
<link href="css/open-filemanager.css" rel="stylesheet" type="text/css" /> - пути к подгружаемым файлам.
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

Планы
=====

- Работа под ckeditor
- Убрать зависимость от jquery
- скины
- lazy-load загрузка миниатюр.
- Преобразование загружаемых файлов до нужного расширения

Страница проекта
===============
http://kosmom.ru/web/open-filemanager
