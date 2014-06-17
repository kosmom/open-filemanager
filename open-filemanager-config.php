<?php
$basefolder='pics';
$soft_check=13;

// default
$modify_images['max-width']=1280;
$modify_images['max-hwight']=1024;
$modify_images['quality']=70;
if ($_SESSION['user']){
	$csrf_secret='mysecretphrase'.$_SESSION['user']['id'];
	$rights=array('access'=>true,'file'=>array('read'=>false,'delete'=>false,'rename'=>false,'upload'=>false,'choose'=>false),'folder'=>array('read'=>true,'delete'=>false,'rename'=>false,'create'=>false));
	
	// show in base directory only public and current user folder
	$show_files['public']=true;
	$show_files[$_SESSION['user']['id']]=true;
}
	
if (!$_GET['folder'])return true;
$links=explode('/',substr($_GET['folder'],1));
foreach ($links as $item){
$folder[]=$item;
switch (implode('/',$folder)){
	case $_SESSION['user']['id']:
		// options for user folder
		$rights=array('access'=>true,'file'=>array('read'=>true,'delete'=>true,'rename'=>true,'upload'=>true,'choose'=>true),'folder'=>array('read'=>true,'delete'=>true,'rename'=>true,'create'=>true));
		$show_files=array();
		
		// adding modules sample
		$modules['text-generator']=array(
			'header'=>'Создать надпись',
			'link'=>'gettext.html'
);

		break;
	case 'public':
	// options for public folder
		$rights=array('access'=>true,'file'=>array('read'=>true,'delete'=>false,'rename'=>false,'upload'=>false,'choose'=>true),'folder'=>array('read'=>true,'delete'=>false,'rename'=>false,'create'=>false));
		$show_files=array();
		break;
}
}
