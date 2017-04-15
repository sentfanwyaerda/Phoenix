<?php
if(file_exists(dirname(dirname(__FILE__)).'/Heracles/Heracles.php')){ require_once(dirname(dirname(__FILE__)).'/Heracles/Heracles.php'); }
if(file_exists(dirname(dirname(__FILE__)).'/JSONplus/JSONplus.php')){ require_once(dirname(dirname(__FILE__)).'/JSONplus/JSONplus.php'); }
if(!defined('PHOENIX_ARCHIVE')){ define('PHOENIX_ARCHIVE', dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR); }
if(!defined('PHOENIX_FRAMEWORK')){ define('PHOENIX_FRAMEWORK', FALSE); }

define('PHOENIX_COMPARE_EXISTS', 0x001);
define('PHOENIX_COMPARE_DELETED', 0x010);
define('PHOENIX_COMPARE_SIZE', 0x002);
define('PHOENIX_COMPARE_MTIME', 0x004);
define('PHOENIX_COMPARE_MTIME_RENEW', (PHOENIX_COMPARE_MTIME + 0x040) );
define('PHOENIX_COMPARE_MD5', 0x008);
define('PHOENIX_COMPARE_SHA1', 0x080);
define('PHOENIX_COMPARE_HASH', (PHOENIX_COMPARE_MD5 + PHOENIX_COMPARE_SHA1) );
define('PHOENIX_COMPARE_ALL', (PHOENIX_COMPARE_EXISTS + PHOENIX_COMPARE_DELETED + PHOENIX_COMPARE_SIZE + PHOENIX_COMPARE_MTIME_RENEW + PHOENIX_COMPARE_HASH) );

define('PHOENIX_HOLD', 0x000);
define('PHOENIX_UPGRADE', 0x100);
define('PHOENIX_CREATE', 0x101);
define('PHOENIX_DELETE', 0x310);
define('PHOENIX_ROLLBACK', 0x400);
define('PHOENIX_INSPECT', 0x800);

if(!defined('PHOENIX_GITHUB_LIFESPAN')){ define('PHOENIX_GITHUB_LIFESPAN', 3600 /*seconds*/ ); }

/*todo: adapt Phoenix::get_github_data() to also proces GitLab*/
if(!defined('PHOENIX_GITLAB_DOMAIN')){ define('PHOENIX_GITLAB_DOMAIN', FALSE); }

if(!defined('PHOENIX_CHMOD')){ define('PHOENIX_CHMOD', 0777 ); }

class Phoenix {
	private $cursor = FALSE;
	private $settings = array();
	private $buffer = array();

	/* allowed are new Phoenix($phoenix_file); new Phoenix($archive); new Phoenix($archive, FALSE, $create, $phoenix_file); and new Phoenix($mount, $src, $create, $phoenix_file); */
	function Phoenix($root=NULL, $src=FALSE, $create=FALSE, $phoenix_file=FALSE){
		$this->buffer['_start_'] = microtime(TRUE);
		//*notify*/ print '<!-- new Phoenix("'.$root.'", '.($src === FALSE ? 'FALSE' : '"'.$src.'"').', '.($create === FALSE ? 'FALSE' : 'TRUE').') -->'."\n";
		/*if $root is $phoenix_file*/ if(substr(strtolower($root), (strlen(self::get_fileshort())*-1)) == self::get_fileshort()){ $phoenix_file = $root; $root = NULL; }
		/*fix*/ if($root === NULL){ $root = dirname(__FILE__).'/'; }
		if(substr(strtolower($phoenix_file), (strlen(self::get_fileshort())*-1)) == self::get_fileshort()){ $this->load_settings($phoenix_file, TRUE); }
		/*fix*/ if($this->length() === FALSE){ $this->load_settings(FALSE, FALSE); }
		$nid = $this->length();
		/*when $root is archive-name only*/ if(!preg_match('#[/]#i', $root) && strlen($root)>0){ $this->settings[$nid]['name'] = $root; $root = dirname(dirname(__FILE__)).'/'.$root.'/';}
		if(Phoenix::directory_exists($root)){
			$this->settings[$nid]['mount'] = $root;
		}
		elseif($create !== FALSE){
			if(Phoenix::directory_exists(dirname($root)) && is_writeable(dirname($root)) ){
				if(!Phoenix::directory_exists($root) && Phoenix::is_authenticated()){
					//mkdir($root, /*0777*/ substr(sprintf('%o', fileperms(dirname($root))), -4) );
					$this->_mkdir($root, PHOENIX_CHMOD);
				}
				$this->settings[$nid]['mount'] = $root;
			}
		}
		if($src !== FALSE && strlen($src) >= 1 ){ $this->settings[$nid]['src'] = $src; }
		/*fix*/ if((isset($this->settings[$nid]) && !isset($this->settings[$nid]['name']) ) || $this->settings[$nid]['name'] === NULL){ $this->settings[$nid]['name'] = (is_dir($this->settings[$nid]['mount']) ? basename($this->settings[$nid]['mount']) : basename(dirname($this->settings[$nid]['mount'])) );}
		/*fix*/ if(isset($this->settings[$nid])){ $this->set_cursor($nid); }
	}

	function set_cursor($i=NULL, $d=0){
		if(!is_int($i)){ $i = (is_int($this->cursor) ? $this->cursor : 0); }
		if(!is_int($d)){ $d = 0; }
		$this->cursor = (int) ($i + $d);
		//*debug*/ print "\ncursor: ".$this->cursor."\n";
		/*recursive fix*/ if($this->cursor < 0){ self::set_cursor( $this->length() + $this->cursor ); }
		/*recursive fix*/ if($this->cursor > 0 && $this->cursor >= $this->length()){ self::set_cursor($this->cursor - $this->length()); }
		return $this->cursor;
	}
	function next(){ return self::set_cursor(NULL, +1); }
	function prev(){ return self::set_cursor(NULL, -1); }
	function reset(){ return self::set_cursor(0); }
	function end(){ return self::set_cursor($this->length() - 1); }
	function length(){ return (isset($this->settings) && is_array($this->settings) ? count($this->settings) : FALSE); }
	function current(){ return $this->cursor; }
	function doAll(){ $this->cursor = TRUE; }

	/*
	function example(){
		if(is_bool($this->current())){
			$c = $this->current(); $res = array();
			$this->reset();
			for($i=0;$i<$this->length();$i++){
				$res[$i] = $this->example();
				$this->next();
			}
			$this->doAll($c);
			return $res;
		}
		else{
			#code of Phoenix::example
		}
	}
	*/

	function directory_exists($dir){ return (file_exists($dir) && is_dir($dir)) ; }
	function is_enabled(){ /* /!\ experimental: could operate in an other fashion then specified */
		if($this->get_src() === FALSE || !Phoenix::directory_exists($this->getMountByIndex($this->current())) ){ return FALSE; }
		return $this->is_authenticated();
	}
	function is_authenticated(){
		return (class_exists('Heracles') ? ( Heracles::is_authenticated() && Heracles::has_role('administrator') ) : TRUE);
	}

	function upgrade_available(){ /* /!\ dummy: no code provided, yet */
		return FALSE;
	}
	function git_enabled(){ /* /!\ dummy: no code provided, yet */
		return FALSE;
	}

	function get_framework_root($type=FALSE){
		if(!(PHOENIX_FRAMEWORK === FALSE)){
			if(class_exists(PHOENIX_FRAMEWORK) && method_exists(PHOENIX_FRAMEWORK, 'get_root')){
				$PF = new PHOENIX_FRAMEWORK;
				return $PF->get_root($type);
			}
		}
		return $this->getVariableByIndex($this->current(), 'mount');
	}

	function get_root($flag=TRUE){ return ($flag === TRUE && isset($this) ? $this->getMountByIndex($this->current()) : PHOENIX_ARCHIVE); }
	function get_fileshort(){ return 'phoenix.json'; }

	function load_settings($file=FALSE, $flag=FALSE){
		if(is_array($file)){ $this->settings = $file; return TRUE; }
		else{
			if($file === FALSE){ $file = Phoenix::get_root($flag).Phoenix::get_fileshort(); }
			if(!file_exists($file) || substr(strtolower($file), (strlen(self::get_fileshort())*-1)) !== self::get_fileshort() ){ return FALSE; }
			//$this->settings = json_decode(file_get_contents($file), TRUE);
			return $this->load_settings((class_exists('JSONplus') ? JSONplus::decode(file_get_contents($file)) : json_decode(file_get_contents($file), TRUE) ), $flag);
		}
		return FALSE;
	}
	function merge_settings($file, $overwrite=FALSE){
		if(is_array($file)){ $this->settings = array_merge($this->settings, $file); return TRUE; }
		else{
			if(file_exists($file) && substr(strtolower($file), (strlen(self::get_fileshort())*-1)) !== self::get_fileshort() ){
				return $this->merge_settings((class_exists('JSONplus') ? JSONplus::decode(file_get_contents($file)) : json_decode(file_get_contents($file), TRUE) ), $overwrite);
			}
		}
		return FALSE;
	}
	function get_src($parm=0x00000){
		$src = $this->getVariableByIndex($this->current(), 'src');
		if($parm & 0x01000){ /*check if an alias exists*/
			/*fix*/ if(isset($this->buffer[md5($src)])){ $src = $this->buffer[md5($src)]; }
		}
		if($parm & 0x02000){ /*proces download-link from github*/ 
			if(preg_match('#^http[s]?\:\/\/(www\.)?github\.com\/#', $src)){
				$ggd = $this->get_github_data($src);
				$src = $ggd['download'];
			}
			/*again*/
			if($parm & 0x01000){ /*check if an alias exists*/
				/*fix*/ if(isset($this->buffer[md5($src)])){ $src = $this->buffer[md5($src)]; }
			}
		}
		if($parm & 0x04000){ /*removes optional #/path/ */
			if(preg_match('#\.zip#', $src)){
				$z = explode('.zip', $src);
				$src = $z[0].'.zip';
			}
			/*again*/
			if($parm & 0x01000){ /*check if an alias exists*/
				/*fix*/ if(isset($this->buffer[md5($src)])){ $src = $this->buffer[md5($src)]; }
			}
		}
		return $src;
	}
	function save_settings($file=FALSE, $flag=FALSE){
		if($this->is_enabled()){
			if($file === FALSE){ $file = Phoenix::get_root($flag).Phoenix::get_fileshort(); }
			return file_put_contents($file, (class_exists('JSONplus') ? JSONplus::encode($this->settings) : json_encode($this->settings) ) );
		}
		return FALSE;
	}
	function clear_settings(){ /* /!\ dummy: no code provided, yet */
		$set = array();
		return $set;
	}
	function getIndexByName($archive, $var='name'){
		/*fix*/ if(!in_array($var, array('name', 'src', 'mount'))){ $var = 'name'; }
		for($i=0;$i<$this->length();$i++){
			if(isset($this->settings[$i][$var]) && strtolower($this->settings[$i][$var]) == strtolower($archive)){ return $i; }
		}
		return FALSE;
	}
	function getMountByIndex($i){
		return (isset($this->settings[$i]['mount']) ? $this->settings[$i]['mount'] : (isset($this->settings[$i]['type']) ? $this->get_framework_root($this->settings[$i]['type']) :  NULL));
	}
	function getVariableByIndex($i, $var=array()){
		if(is_array($var) || is_bool($var)){ return (is_int($i) && isset($this->settings[$i]) ? $this->settings[$i] : FALSE); }
		else{ return (is_int($i) && isset($this->settings[$i]) && isset($this->settings[$i][$var]) ? $this->settings[$i][$var] : FALSE); }
	}

	function get_backup($id=0){ /* /!\ dummy: no code provided, yet */
		/*fix*/ if(is_int($id) && $id >= 0){ $id = $this->get_backup_id($id); }
		return $file;
	}
	function backup(){ /* /!\ dummy: no code provided, yet */
		return $backup;
	}
	function revert($to){ return self::restore($to); }
	function restore($id=0){  /* /!\ dummy: no code provided, yet */
		/*fix*/ if(is_int($id) && $id >= 0){ $id = $this->get_backup_id($id); }
	}
	function cleanup($keep=0){ /* /!\ dummy: no code provided, yet */
		/*removes all backups except it keeps the last/most-recent $keep backups */
	}
	function get_backup_id($keep=0, $flag=FALSE){ /* /!\ experimental: could operate in an other fashion then specified */
		$list = scandir($this->get_root($flag));
		$set = array();
		foreach($list as $i=>$f){
			if(preg_match("#[\[]([^\]]+)[\]][.](zip|tgz|tar.gz|bz)$#i", $f, $buffer)){
				$set[filemtime($this->get_root().$f)] = $f;
			}
		}
		ksort($set);
		$i = 0;
		foreach($set as $a=>$f){
			if($i == $keep){ return $f; }
			$i++;
		}
		return ( $keep <= 0 ? /*first*/ reset($set) : /*last*/ end($set) );
	}

	function stall(){ /* /!\ dummy: no code provided, yet */
	}

	function download($to=FALSE, $conf=array()){ /* &check; useable! */
		if(is_bool($this->current())){
			$c = $this->current(); $res = array();
			$this->reset();
			for($i=0;$i<$this->length();$i++){
				$res[$i] = $this->download($to, $conf);
				$this->next();
			}
			$this->doAll($c);
			return $res;
		}
		else{
			/*fix*/ if(is_array($to)){ $conf = $to; $to = FALSE; }
			if($to === FALSE && $conf == array() && preg_match('#^http[s]?\:\/\/(www\.)?github\.com#', $this->get_src())){ $conf = $this->get_github_data($this->get_src()); }
			$src = (is_array($conf) && isset($conf['download']) ? $conf['download'] : $this->get_src() );
			if($to === FALSE){ $to = (
					is_array($conf) && isset($conf['repository'])
					? PHOENIX_ARCHIVE.$conf['repository'].(isset($conf['last-commit']['sha']) ? '-'.$conf['last-commit']['sha'] : NULL).'.zip'
					: PHOENIX_ARCHIVE.basename($src)
				); }
			//*debug*/ print '<!-- '.$this->get_src().' | '.$src.' === '.$to.' -->';
			if(!file_exists($to)){
				$buffer = file_get_contents($src);
				file_put_contents($to, $buffer);
				chmod($to, 0777);
			}
			/*fix*/ if(isset($conf['directory'])){ $to = $to.'#/'.$conf['directory']; }
			if(!isset($this->buffer[md5($this->get_src())]) || $this->buffer[md5($this->get_src())] != $to){ $this->buffer['_created_'][md5($this->get_src())] = microtime(TRUE); $this->buffer[md5($this->get_src())] = $to; }
			return $to;
		}
	}

	function update($save_settings=FALSE){ return self::upgrade(FALSE, FALSE, $save_settings); }
	function git_pull($archive=NULL, $autocreate=FALSE, $save_settings=FALSE){ /* /!\ dummy: no code provided, yet */
		if(self::git_enabled()){
			$index = $this->getIndexByName($archive);
			$mount = $this->getMountByIndex($index);
			//$mount = $this->settings[$index]['mount'];
			# return system('git -C '.$mount.' pull);
			//*alternate of:*/ return self::upgrade($archive, $autocreate, $save_settings);
		} else { return FALSE; }
	}
	function upgrade($archive=NULL, $autocreate=FALSE, $save_settings=FALSE){ /* /!\ experimental: could operate in an other fashion then specified */
		if($this->is_enabled()){
			if(self::git_enabled()){ self::git_pull($archive); }
			else{
				/* gets $this->get_src() (download, unpack) and replaces $this->get_src() */
				$this->install($this->download(), TRUE);
			}
			if($save_settings !== FALSE){ $this->save_settings(); }
		}
	}

	function git_clone($archive, $uninstall_first=FALSE){ /* /!\ dummy: no code provided, yet */
		if(self::git_enabled()){
			$index = $this->getIndexByName($archive);
			$mount = $this->getMountByIndex($index);
			$data = self::get_github_data($src);
			# return system('git -C '.$mount.' clone '.$data['clone']);
		} else {
			return self::install($archive, $uninstall_first);
		}
	}
	function install($archive=NULL, $uninstall_first=FALSE){ /* /!\ experimental: could operate in an other fashion then specified */
		/* 0. process variables */
		//*debug*/ print "\n< ! -- INSTALL: ".$archive.'  --  >';
		/*fix*/ if(is_bool($archive)){ $uninstall_first = $archive; $archive = NULL; }
		if($archive !== NULL){
			if($this->getIndexByName($archive)){ $this->set_cursor($this->getIndexByName($archive)); }
			elseif($this->getIndexByName($archive, 'src')){ $this->set_cursor($this->getIndexByName($archive, 'src')); }
			else{ return FALSE; }
		}
		$archive = $this->get_src(0x07000);
		//*debug*/ print " &rarr; ".$archive;
		//*debug*/ print " &rArr; ".$this->current();

		/* 0b. process all! */
		if(is_bool($this->current())){
			$c = $this->current(); $res = array();
			$this->reset();
			for($i=0;$i<$this->length();$i++){
				$res[$i] = $this->install(NULL, $uninstall_first);
				$this->next();
			}
			$this->doAll($c);
			return $res;
		} #else{}

		//*experimental*/ umask(0);
		//*debug*/ print " &lArr; "; print_r($this->getMountByIndex($this->current())); print ' '; print_r(umask());

		if($this->is_enabled()){
			/* 1a. remove $archive if it already exists; to do a clean install*/
			#if($uninstall_first !== FALSE){ $this->uninstall($this->getMountByIndex($this->current()), TRUE, TRUE); }
			/* 1b. check if directory_exists; create directory */
			if(self::directory_exists($this->getMountByIndex($this->current()))){ /*make sure $archive is not yet installed*/
				/*debug*/ print "\n".$this->getMountByIndex($this->current())." already exists!\n";
			#	return FALSE;
			} else {
				/*debug*/ print "\ncurrent mount = ".$this->getMountByIndex($this->current())."\n";
				$this->_mkdir($this->getMountByIndex($this->current()), PHOENIX_CHMOD);
			}
			/* 2. get archive; download */
			if(!file_exists($archive) || preg_match('#^(http[s]?|ftp)\:\/\/#', $archive)){ $this->download(); $archive = $this->get_src(0x07000); }

			//*debug*/ print " &larr; ".$archive;

			/* 3. extract archive */
			if(self::git_enabled()){ return self::git_clone($archive); }
			else{
				$zip = new ZipArchive;
				$res = $zip->open($archive);
				/*debug*/ print "\n".$archive.' ('.print_r($res, TRUE).') = '; print_r($zip);
				if($res === TRUE){
					//*fix*/ if(!self::directory_exists($this->getMountByIndex($this->current()))){ mkdir($this->getMountByIndex($this->current())); chmod($this->getMountByIndex($this->current()), 0777); }
					//*debug*/ return $archive.' = '.$this->getMountByIndex($this->current());
					$zip->extractTo($this->getMountByIndex($this->current())); //, $files
					$zip->close();
					$only = $this->_find_one_directory_only($this->getMountByIndex($this->current()), TRUE);
					//print '<!-- '.$only.' -->';
					if($only !== FALSE){ $this->_move_up_one_directory($this->getMountByIndex($this->current()).$only.'/', TRUE); }
					return TRUE;
				}
			}
			/* 4. update phoenix.json database */
		}
		return FALSE;
	}
	private function _find_one_directory_only($dir, $ignore_archives=FALSE){ /* /!\ experimental: could operate in an other fashion then specified */
		/*fix*/ if(!(is_dir($dir) && preg_match("#[/]$#i", $dir) )){ return FALSE; }
		$list = scandir($dir);
		$set = array();
		foreach($list as $i=>$f){
			if(!preg_match("#^[\.]{1,2}$#i", $f) && (!$ignore_archives || !preg_match("#[\.](zip|tgz|tar.gz|bz)$#i", $f))){
				if(file_exists($dir.$f)){
					//if(!is_dir($dir.$f)){ return FALSE; }
					$set[] = $f;
				}
			}
		}
		if(count($set) != 1){
			/*notify*/ print '<!-- _find_one_directory_only: '.print_r($set, TRUE).' -->'."\n";
			return FALSE;
		}
		return end($set);
	}
	private function _move_up_one_directory($dir, $remove=FALSE){ /* /!\ experimental: could operate in an other fashion then specified */
		/*fix*/ if(!(is_dir($dir) && preg_match("#[/]$#i", $dir) )){ return FALSE; }
		$list = scandir($dir);
		foreach($list as $i=>$f){
			if(!preg_match("#^[\.]{1,2}$#i", $f)){
				rename($dir.$f, dirname($dir).'/'.$f);
				/*fix*/ chmod(dirname($dir).'/'.$f, 0777);
			}
		}
		if($remove !== FALSE){ rmdir($dir); }
		return TRUE;
	}

	function uninstall($dir=NULL, $recursive=TRUE, $keep_archives=TRUE){ /* /!\ experimental: could operate in an other fashion then specified */
		/*fix*/ if($dir === NULL){ $dir = $this->get_root(); }
		if(!preg_match("#^(".$this->get_root().")#i", $dir)){ return FALSE; }
		if($this->is_enabled()){
			$list = scandir($dir);
			foreach($list as $i=>$f){
				if(!preg_match("#^[\.]{1,2}$#i", $f)){
					if(is_dir($dir.$f) && $recursive === TRUE){ $this->uninstall($dir.$f.'/'); }
					elseif(file_exists($dir.$f)){
						if($keep_archives !== TRUE || !preg_match("#[\.](zip|tgz|tar.gz|bz)$#i", $f)){
							unlink($dir.$f);
						}
					}
				}
			}
			@rmdir($dir);
			return TRUE;
		}
		return FALSE;
	}


	function get_github_data($src=FALSE, $magic=TRUE/*array()*/){ /* /!\ unstable: when GitHub changes their website, this script can break! */
		if(isset($this) && $src===FALSE){ $src = $this->get_src(); }
		$hash = md5($src.print_r($magic, TRUE)); if(isset($this) && isset($this->buffer[$hash]) && $this->buffer['_created_'][$hash] >= (microtime(TRUE)-PHOENIX_GITHUB_LIFESPAN) ){ return $this->buffer[$hash]; }
		/*fix*/if(!($magic === TRUE) && !is_array($magic)){ $magic = array($magic); }
		$end = '€'; $end2 = '¤';

		if(preg_match('#^(http[s]?|git)[\:]//(www[\.])?(github[\.]com)/([^/]+)/([^/]+)(.*)$#i', $src, $buffer)){
			$list = array(); $list['original'] = $src;
			list($list['original'], $list['protocol'], $list['webprefix'], $list['domain'], $list['author'], $list['repository'], $list['deeplink']) = $buffer;
			if(preg_match('#^/archive/(.*)\.(zip|tar\.gz)$#', $list['deeplink'], $b)){ $list['branch'] = $b[1]; $list['archive'] = $b[2]; }
			$list['page'] = $list['protocol'].'://'.$list['webprefix'].$list['domain'].'/'.$list['author'].'/'.$list['repository'].'/';
			$list['clone'] = $list['protocol'].'://'.$list['webprefix'].$list['domain'].'/'.$list['author'].'/'.$list['repository'].'.git';
			$list['clone-ssh'] = 'git@'.$list['domain'].':'.$list['author'].'/'.$list['repository'].'.git';
			$list['download'] = $list['protocol'].'://'.$list['webprefix'].$list['domain'].'/'.$list['author'].'/'.$list['repository'].'/archive/'.(isset($list['branch']) ? $list['branch'] : 'master').'.'.(isset($list['archive']) ? $list['archive'] : 'zip');
			$list['releases'] = $list['protocol'].'://'.$list['webprefix'].$list['domain'].'/'.$list['author'].'/'.$list['repository'].'/releases';
			$list['tags'] = $list['protocol'].'://'.$list['webprefix'].$list['domain'].'/'.$list['author'].'/'.$list['repository'].'/tags';
			$list['commits'] = $list['protocol'].'://'.$list['webprefix'].$list['domain'].'/'.$list['author'].'/'.$list['repository'].'/commits/'.(isset($list['branch']) ? $list['branch'] : 'master');
			$list['issues'] = $list['protocol'].'://'.$list['webprefix'].$list['domain'].'/'.$list['author'].'/'.$list['repository'].'/issues';
			$list['contributors'] = $list['protocol'].'://'.$list['webprefix'].$list['domain'].'/'.$list['author'].'/'.$list['repository'].'/graphs/contributors';
			$list['wiki'] = $list['protocol'].'://'.$list['webprefix'].$list['domain'].'/'.$list['author'].'/'.$list['repository'].'/wiki';
			$list['directory'] = $list['repository'].'-'.(isset($list['branch']) ? $list['branch'] : 'master').'/';

			if($magic === TRUE || in_array('stats', $magic)){
				$list['stats'] = array();
				$page_raw = file_get_contents($list['page']); $page_raw = str_replace('</a>', $end, $page_raw);
				if(preg_match('#[\<]a(\s+class="[^"]+")?\s+href="/'.$list['author'].'/'.$list['repository'].'/watchers"[^\>]*[\>]([^'.$end.']+)'.$end.'#i', $page_raw, $a)){ $list['stats']['watchers'] = trim(self::_get_value_of_span($a[2])); }
				if(preg_match('#[\<]a(\s+class="[^"]+")?\s+href="/'.$list['author'].'/'.$list['repository'].'/stargazers"[^\>]*[\>]([^'.$end.']+)'.$end.'#i', $page_raw, $a)){ $list['stats']['stargazers'] = trim(self::_get_value_of_span($a[2])); }
				if(preg_match('#[\<]a(\s+class="[^"]+")?\s+href="/'.$list['author'].'/'.$list['repository'].'/network"[^\>]*[\>]([^'.$end.']+)'.$end.'#i', $page_raw, $a)){ $list['stats']['forks'] = trim(self::_get_value_of_span($a[2])); }
				if(preg_match('#[\<]a(\s+class="[^"]+"|\sdata-pjax)?\s+href="/'.$list['author'].'/'.$list['repository'].'/commits/master"[^\>]*[\>]([^'.$end.']+)'.$end.'#i', $page_raw, $a)){ $list['stats']['commits'] = trim(self::_get_value_of_span($a[2])); }
				if(preg_match('#[\<]a(\s+class="[^"]+"|\sdata-pjax)?\s+href="/'.$list['author'].'/'.$list['repository'].'/branches"[^\>]*[\>]([^'.$end.']+)'.$end.'#i', $page_raw, $a)){ $list['stats']['branches'] = trim(self::_get_value_of_span($a[2], 'num text-emphasized')); }
				if(preg_match('#[\<]a(\s+class="[^"]+"|\sdata-pjax)?\s+href="/'.$list['author'].'/'.$list['repository'].'/releases"[^\>]*[\>]([^'.$end.']+)'.$end.'#i', $page_raw, $a)){ $list['stats']['releases'] = trim(self::_get_value_of_span($a[2], 'num text-emphasized')); }
				if(preg_match('#[\<]a(\s+class="[^"]+")?\s+href="/'.$list['author'].'/'.$list['repository'].'/graphs/contributors"[^\>]*[\>]([^'.$end.']+)'.$end.'#i', $page_raw, $a)){ $list['stats']['contributors'] = trim(self::_get_value_of_span($a[2])); }
				if(preg_match('#[\<]a(\s+class="[^"]+")?\s+href="/'.$list['author'].'/'.$list['repository'].'/issues"[^\>]*[\>]([^'.$end.']+)'.$end.'#i', $page_raw, $a)){ $list['stats']['issues'] = trim(self::_get_value_of_span($a[2], 'counter')); }
				if(preg_match('#[\<]a(\s+class="[^"]+")?\s+href="/'.$list['author'].'/'.$list['repository'].'/pulls"[^\>]*[\>]([^'.$end.']+)'.$end.'#i', $page_raw, $a)){ $list['stats']['pullrequests'] = trim(self::_get_value_of_span($a[2], 'counter')); }
				if(preg_match('#[\<]a(\s+class="[^"]+")?\s+href="/'.$list['author'].'/'.$list['repository'].'/projects"[^\>]*[\>]([^'.$end.']+)'.$end.'#i', $page_raw, $a)){ $list['stats']['projects'] = trim(self::_get_value_of_span($a[2])); }
				foreach($list['stats'] as $n=>$v){ if(!(is_int($v) || preg_match('#^\d+$#', $v))){ unset($list['stats'][$n]); } }
			}
			if($magic === TRUE || in_array('last-commit', $magic)){
				/*fix*/ if(!isset($page_raw)){ $page_raw = file_get_contents($list['page']); $page_raw = str_replace('</a>', $end, $page_raw); }
				if(preg_match('#[\<]a(\s+class="[^"]+")?\s+href="/'.$list['author'].'/'.$list['repository'].'/tree/([^"]+)"[^\>]*[\>]Permalink'.$end.'#i', $page_raw, $a)){
					$list['last-commit'] = array();
					$list['last-commit']['sha'] = trim($a[2]);
					$commit_raw = file_get_contents($list['page'].'commit/'.$list['last-commit']['sha']);
					if(preg_match('#[\<]relative\-time datetime="([^"]+)"[\>]([^\<]+)[\<]/relative\-time[\>]#i', $commit_raw, $a)){ $list['last-commit']['timestamp'] = $a[1]; }
					if(preg_match('#[\<]p class="commit\-title"[\>]([^\<]+)[\<]/p[\>]#i', $commit_raw, $a)){ $list['last-commit']['description'] = trim($a[1]); }
					if(preg_match('#[\<]img alt="[\@]?([^"]+)" class="avatar" height="24" src="([^"]+)"[^\>]+[\>]#i', $commit_raw, $a)){ $list['last-commit']['author'] = $a[1]; $list['last-commit']['avatar'] = $a[2]; }
				}
			}
			//if(in_array('contributor', $magic)){ $list['contributor'] = array(); }
			if($magic === TRUE || in_array('versions', $magic)){
				$list['versions'] = self::_get_github_tags($list, 'tags', $page_raw);
			}
			if($magic === TRUE || in_array('branches', $magic)){
				$list['branches'] = self::_get_github_tags($list, 'branches', $page_raw);
			}
			/*create buffer*/ if(isset($this)){ $this->buffer['_created_'][$hash] = microtime(TRUE); $this->buffer[$hash] = $list; }
			return $list;
		}
		else { return FALSE; }
	}
	private function _get_value_of_span($str=NULL, $class=FALSE){
		if(preg_match('#[\<]span'.($class!==FALSE ? '(\sclass="'.$class.'")' : '(\s[^\>]+)?').'[\>]([^\<]+)[\<]/span[\>]#i', $str, $buffer)){
			return $buffer[2];
		}
		return $str;
	}
	private function _get_github_tags($list, $tagstr='tags', $page_raw=FALSE){
		$res = array();
		$end = '€'; $end2 = '¤';
		if($page_raw === FALSE || strlen($page_raw) < 1){ $page_raw = file_get_contents($list['page']); $page_raw = str_replace('</a>', $end, $page_raw); }
		$page_raw = str_replace('</div>', $end2, $page_raw);
		if(preg_match('#[\<]div class="[^"]+" data-tab-filter="'.($tagstr ? $tagstr : 'tags').'"[^\>]*[\>]\s*[\<]div[^\>]+[\>]([^'.$end2.']+)'.$end2.'#i', $page_raw, $buffer)){ $data_tab = $buffer[1]; }
		else{ $data_tab = NULL; /*$page_raw*/; }
		preg_match_all('#[\<]a(\s+class="[^"]+")?\s+href="/'.$list['author'].'/'.$list['repository'].'/tree/([^"]+)"[^\>]*[\>]([^'.$end.']+)'.$end.'#', $data_tab, $buffer);
		foreach($buffer[2] as $i=>$v){
			$res[] = trim(self::_get_value_of_span($buffer[3][$i]));
		}
		return $res;
	}
	function fingerprint($dir, $root=FALSE){ /* /!\ experimental: could operate in an other fashion then specified */
		if(is_array($dir) /*is valid fingerprint result*/ ){ return $dir; }
		/*fix*/ if($root===FALSE){ $root = $dir; }
		if(!(substr($dir, 0, strlen($root)) == $root)){ return FALSE; }
		$prefix = substr($dir, strlen($root));
		/*fix*/ if(substr($dir, -1) != '/'){ $dir .= '/';}
		$db = array();
		if(preg_match('#\.zip#i', $root)){
			/*fix*/ if(isset($this->buffer[md5($root)])){ $root = $this->buffer[md5($root)]; }
			$z = explode('.zip', $root);
			$zz = md5($z[0]).'-'.sha1($z['0']);
			$tmpfile = '/tmp/'.$zz.'.zip';
			if(preg_match('#^(http[s]?|ftp)\:\/\/#', $z[0].'.zip') && !file_exists($tmpfile)){
				file_put_contents($tmpfile, file_get_contents($z[0].'.zip'));
				$zcreated = TRUE;
			} else { $zcreated = FALSE; $tmpfile = $z[0].'.zip'; }

			/*fix*/ $cleanup = (preg_match('#^https\://github\.com/[^/]+/([^/]+)/archive/(.*)$#', $z['0'], $q) ? $q[1].'-'.$q[2].'/' : NULL);

			/*fix*/ $z[1] = preg_replace('#^[\#]?[\/]?#', '', $z[1]);

			$db[$tmpfile]['src'] = $tmpfile;
			$db[$tmpfile]['size'] = filesize($tmpfile);
			$db[$tmpfile]['mtime'] = filemtime($tmpfile);
			$db[$tmpfile]['mtime:iso8601'] = date('c', filemtime($tmpfile));
			$db[$tmpfile]['md5'] = @md5_file($tmpfile);
			$db[$tmpfile]['sha1'] = @sha1_file($tmpfile);
			/*fix*/ $db[$tmpfile]['clear'] = $cleanup;

			$zip = new ZipArchive;
			$zip->open($tmpfile);
			$db[$tmpfile]['comment'] = $zip->getArchiveComment();

			for($i=0;$i<$zip->numFiles;$i++){
				$stat[$i] = $zip->statIndex($i);
				$name = (substr($zip->getNameIndex($i), 0, strlen($cleanup)) == $cleanup ? substr($zip->getNameIndex($i), strlen($cleanup) ) : $zip->getNameIndex($i));
				/*fix*/ $name = (strlen($name) == 0 ? '/' : $name);
				if(!isset($z[1]) || (preg_match('#^'.$z[1].'(.*)$#i', $name, $bx))){
					if(isset($bx[1])){ $name = $bx[1]; }
					$raw = $zip->getFromIndex($i);
					$db[] = array(
						'file'=>$name,
						'size'=>$stat[$i]['size'],
						'mtime'=>$stat[$i]['mtime'],
						'mtime:iso8601'=>date('c',$stat[$i]['mtime']),
						'md5'=> @md5($raw),
						'sha1'=> @sha1($raw),
						'comment'=>$zip->getCommentIndex($i)
					);
				}
			}
			//*debug*/ print_r($zip);
			if($zcreated === TRUE){ unlink($tmpfile); }
		}
		else{ # $dir is a directory
			$list = scandir($dir);
			foreach($list as $i=>$f){
				if(!preg_match('#^([\.]{1,2}|\.git)$#', $f)){
					if(file_exists($dir.$f) && !is_dir($dir.$f)){
						$db[] = array(
							'file'=>$prefix.$f,
							'size'=>filesize($dir.$f),
							'mtime'=>filemtime($dir.$f),
							'mtime:iso8601'=>date('c',filemtime($dir.$f)),
							'md5'=> @md5_file($dir.$f),
							'sha1'=> @sha1_file($dir.$f)
						);
					} else {
						$db[] = array(
							'file'=>$prefix.$f.'/',
							'mtime'=>filemtime($dir.$f.'/'),
							'mtime:iso8601'=>date('c',filemtime($dir.$f.'/'))
						);
						$db = array_merge($db, self::fingerprint($dir.$f.'/', $root));
					}
				}
			}
		}
		return $db;
	}
	function fingerprint_compare($old=array(), $new=array(), $compare=PHOENIX_COMPARE_ALL){ return self::fingerprint_diff($old, $new, $compare); }
	function fingerprint_diff($old=array(), $new=array(), $compare=PHOENIX_COMPARE_ALL){ /* /!\ experimental: could operate in an other fashion then specified */
		/*fix*/ if(is_bool($compare)){ $compare = ($compare === TRUE ? PHOENIX_COMPARE_ALL : 0x000); }
		$diff = array();
		$list = array_unique(array_merge(self::_get_file_s($old, TRUE), self::_get_file_s($new, TRUE)));
		foreach($list as $i=>$f){
			$diff[$i] = array('file' => $f, 'hint' => 'hold');
			$oc = self::_get_file_entry($f, $old);
			$nc = self::_get_file_entry($f, $new);
			/*debug*/ $diff[$i]['old'] = $oc; $diff[$i]['new'] = $nc;
			if(($compare & PHOENIX_COMPARE_EXISTS /*0x001*/ ) && !isset($oc['size']) ){ $diff[$i]['hint'] = 'create'; $diff[$i]['reason'][] = 'existence'; }
			if(($compare & PHOENIX_COMPARE_DELETED /*0x010*/ ) && !isset($nc['size']) ){ $diff[$i]['hint'] = 'delete'; $diff[$i]['reason'][] = 'existence'; }
			if(($compare & PHOENIX_COMPARE_SIZE /*0x002*/ ) && self::_has_variable_both('size', $oc, $nc) && ($nc['size'] != $oc['size']) ){ $diff[$i]['hint'] = 'inspect'; $diff[$i]['reason'][] = 'size'; }
			if(($compare & PHOENIX_COMPARE_MD5 /*0x008*/ ) && self::_has_variable_both('md5', $oc, $nc) && ($nc['md5'] != $oc['md5']) ){ $diff[$i]['hint'] = 'inspect'; $diff[$i]['reason'][] = 'md5'; }
			if(($compare & PHOENIX_COMPARE_SHA1 /*0x080*/ ) && self::_has_variable_both('sha1', $oc, $nc) && ($nc['sha1'] != $oc['sha1']) ){ $diff[$i]['hint'] = 'inspect'; $diff[$i]['reason'][] = 'sha1'; }
			/* ($oc['mtime'] <=> $nc['mtime'])!=0 *//* !($oc['mtime']==$nc['mtime']) *//* ($nc['mtime'] < $oc['mtime'] || $nc['mtime'] > $oc['mtime']) */
			if(($compare & PHOENIX_COMPARE_MTIME /*0x004*/ ) && self::_has_variable_both('mtime', $oc, $nc) && !($oc['mtime']==$nc['mtime']) ){
				if(($compare & PHOENIX_COMPARE_MTIME_RENEW /*0x044*/ )){
					$diff[$i]['hint'] = (!is_array($diff[$i]['reason']) || (is_array($diff[$i]['reason']) && count($diff[$i]['reason']) == 0) ? ($nc['mtime'] > $oc['mtime'] ? $diff[$i]['hint'] : 'mtime_rollback') : ($nc['mtime'] > $oc['mtime'] ? 'upgrade' : 'rollback'));
				} else {
					$diff[$i]['hint'] = 'inspect';
				}
				$diff[$i]['reason'][] = 'mtime';
			}
		}
		return $diff;
	}
	/*bool*/ function fingerprint_is_identical($old=array(), $new=array(), $compare=PHOENIX_COMPARE_ALL){
		$d = self::fingerprint_diff($old, $new, $compare);
		$bool = TRUE;
		foreach($d as $i=>$f){
			$bool = (($f['hint'] == 'hold' || $f['old']['md5'] == $f['new']['md5'] || $f['old']['md5'] == $f['sha1']['sha1']) ? $bool : FALSE);
		}
		return $bool;
	}
	private /*bool*/ function _has_variable_both($var=NULL, $first=array(), $second=array()){
		return (isset($first[$var]) && isset($second[$var]));
	}
	private function _get_file_entry($file=NULL, $db=array()){
		foreach($db as $i=>$f){
			if($f['file'] == $file){
				return $f;
			}
		}
		return array('file' => $file);
	}
	private function _get_file_s($first=array(), $ignore=FALSE){
		$list = array();
		foreach($first as $i=>$f){
			if(!in_array($f['file'], $list)){
				if( ($ignore === FALSE) || !(strlen($f['file']) == 0 || $f['file'] == '/') ){
					$list[] = $f['file'];
				}
			}
		}
		return $list;
	}	
	function mtime_rollback($mount=NULL, $src=FALSE){
		if($src === FALSE && $mount !== NULL && !is_bool($this->current())){ $file_only = $mount; $mount = NULL; } else { $file_only = NULL; }
		if($mount === NULL && $src === FALSE){
			if(is_bool($this->current())){
				$c = $this->current(); $res = array();
				$this->reset();
				for($i=0;$i<$this->length();$i++){
					$res[$i] = $this->mtime_rollback($file_only);
					$this->next();
				}
				$this->doAll($c);
				return $res;
			}
			else{
				$mount = $this->getMountByIndex($this->current());
				$src = $this->getVariableByIndex($this->current(), 'src');
			}
		}
		//if(!self::directory_exists($mount) || !(file_exists($src) || self::directory_exists($src)) ){ return FALSE; }
		$db = self::fingerprint_compare(self::fingerprint($mount), self::fingerprint($src));
		foreach($db as $i=>$f){
			if($f['hint'] == 'mtime_rollback' && ($file_only === NULL || $file_only == $f['file']) ){
			//if(is_array($f['reason']) && in_array('mtime', $f['reason']) && $f['new']['size'] == $f['old']['size'] && $f['new']['md5'] == $f['old']['md5'] && $f['new']['sha1'] == $f['old']['sha1']){
				$db[$i]['mtime_rollback'] = touch($mount.$f['file'], ( $f['new']['mtime'] < $f['old']['mtime'] ? $f['new']['mtime'] : $f['old']['mtime'] ));
			}
		}
		return $db;
	}
	private function _mkdir($root=FALSE, $chmod=NULL){
		if($root === FALSE){ $root = $this->getMountByIndex($this->current()); }
		if($chmod === NULL){ $chmod = PHOENIX_CHMOD; }
		mkdir($root, $chmod);
		chmod($root, $chmod);
	}
}
function Phoenix($pfile, $auto=FALSE, $save_settings=FALSE){ /* /!\ experimental: could operate in an other fashion then specified */
	$P = new Phoenix(NULL, FALSE, $auto, $pfile);
	return $P->upgrade($auto, $save_settings);
}
?>