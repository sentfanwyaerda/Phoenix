<?php 
require_once(dirname(__FILE__).'/Phoenix.php');
if(file_exists(dirname(dirname(__FILE__)).'/Heracles/Heracles.php')){
	require_once(dirname(dirname(__FILE__)).'/Heracles/Heracles.php');
	Heracles::try_to_authenticate();
}

if(Phoenix::is_authenticated()){
	/*fix*/ if(!isset($_POST["mount"])){ $_POST["mount"] = dirname(__FILE__).'/'; }
	/*fix*/ if(!isset($_POST["src"])){ $_POST["src"] = NULL; }
	/*fix*/ if(!isset($_POST["action"])){ $_POST["action"] = 'new'; }
	/*fix*/ $short = $result = $rc = NULL;
	
	/*action=test*/ $mount_test = (Phoenix::directory_exists($_POST["mount"]));
	
	if(is_array($_POST) && count($_POST) > 0 && isset($_POST['Phoenix']) && strtolower($_POST['Phoenix']) == 'manager'){
		//*debug*/ print '<pre>'; print_r($_POST); print '</pre>';
		$Ph = new Phoenix($_POST["mount"], $_POST["src"], (isset($_POST["create"]) && $_POST["create"] == 'true' ? TRUE : FALSE));
		
		$short = ($Ph->get_project() ? $Ph->get_project() : preg_replace("#^(.*)[/]([^/]+)[/]?$#i", "\\2", $_POST["mount"]));
	}
	
	//*debug*/ $result .= '<li><pre>'.print_r($Ph, TRUE).'</pre></li>';
		
	/*fix*/ if(strlen($_POST["src"])<=1 && isset($Ph) && is_object($Ph)){ $_POST["src"] = $Ph->get_src(); }
	
	if(in_array(strtolower($_POST['action']), array('download','install','update')) && isset($Ph) && is_object($Ph) ){ /*case 'download':*/
		$start = microtime(TRUE);
		$archive_file = $Ph->download();
		$end = microtime(TRUE);
		$result .= '<li><code>'.$archive_file.'</code> <span>downloaded in '.number_format(($end-$start),2).' seconds</span>, from: <a href="'.$_POST['src'].'"><code>'.$_POST['src'].'</code></a></li>';
	}
	if(in_array(strtolower($_POST['action']), array('install')) && isset($Ph) && is_object($Ph) ){ /*case 'install':*/
		$Ph->install($archive_file);
	}
	if(in_array(strtolower($_POST['action']), array('uninstall')) && isset($Ph) && is_object($Ph) ){ /*case 'uninstall':*/
		$Ph->uninstall(NULL, TRUE, FALSE);
	}
	if(in_array(strtolower($_POST['action']), array('update')) && isset($Ph) && is_object($Ph) ){ /*case 'uninstall':*/
		$Ph->update();
	}
	if(in_array(strtolower($_POST['action']), array('test','download','install','update','uninstall')) ){ /*case 'test':*/
		$result .= '<li>Mount <span '.($mount_test ? (Phoenix::directory_exists($_POST['mount']) ? 'class="green light">exists' : 'class="red light">was deleted') : (Phoenix::directory_exists($_POST['mount']) ? 'class="green">was created' : 'class="red">does not exist') ).'</span>: <span><code>'.$_POST['mount'].'</code></span></li>';
	}
	switch(strtolower($_POST['action'])){ /*generate status rapport*/
		case 'new':
			$result = "Welcome to the <strong>Phoenix Manager</strong>. Here you can initialize the rebirth of a fenix."; $rc = 'normal new fa fa-notice';
			break;
		case 'test':
			$rc = 'normal test fa fa-test';
			break;
		case 'download':
			$rc = 'download fa fa-download';
			//break;
		case 'install': case 'update':
			break;
		case 'uninstall':
			break;
		default: 
			$result = 'ignores everything'; $rc = 'normal';
	}
	print str_replace(array('{mount}','{short}','{src}','{create:checked}','{result}','{result:class}'), array($_POST['mount'], $short, $_POST['src'], (isset($_POST["create"]) && $_POST["create"] == 'true' ? ' checked="true"' : NULL), $result, $rc), file_get_contents(dirname(__FILE__).'/manager.html'));
}
else {
	if(class_exists('Heracles')){ print Heracles::html_authenticate(); }
	else{ print '<span class="error anonymous">You need to be authenticated and been granted the administrator privilegdes.</span>'; }
}
?>