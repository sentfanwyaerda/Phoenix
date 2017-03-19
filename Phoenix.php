<?php
if(file_exists(dirname(dirname(__FILE__)).'/Heracles/Heracles.php')){ require_once(dirname(dirname(__FILE__)).'/Heracles/Heracles.php'); }
if(!defined('PHOENIX_ARCHIVE')){ define('PHOENIX_ARCHIVE', dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR); }
if(!defined('PHOENIX_FRAMEWORK')){ define('PHOENIX_FRAMEWORK', FALSE); }

define('PHOENIX_COMPARE_ALL', 0x0FF);
define('PHOENIX_COMPARE_EXISTS', 0x001);
define('PHOENIX_COMPARE_DELETED', 0x010);
define('PHOENIX_COMPARE_SIZE', 0x002);
define('PHOENIX_COMPARE_MTIME', 0x004);
define('PHOENIX_COMPARE_MTIME_RENEW', 0x044);
define('PHOENIX_COMPARE_MD5', 0x008);
define('PHOENIX_COMPARE_SHA1', 0x080);
define('PHOENIX_COMPARE_HASH', (PHOENIX_COMPARE_MD5 + PHOENIX_COMPARE_SHA1) );

define('PHOENIX_HOLD', 0x000);
define('PHOENIX_UPGRADE', 0x100);
define('PHOENIX_CREATE', 0x101);
define('PHOENIX_DELETE', 0x310);
define('PHOENIX_ROLLBACK', 0x400);
define('PHOENIX_INSPECT', 0x800);

class Phoenix {
	var $name = NULL;
	var $src = NULL; /* http://www.github.com/ */
	var $root = FALSE; /* /www/module/ */
	var $settings = array();

	function Phoenix($root=NULL, $src=FALSE, $create=FALSE, $phoenix_file=NULL){
		/*notify*/ print '<!-- new Phoenix("'.$root.'", '.($src === FALSE ? 'FALSE' : '"'.$src.'"').', '.($create === FALSE ? 'FALSE' : 'TRUE').') -->'."\n";
		/**/if(substr(strtolower($root), (strlen(self::get_fileshort())*-1)) == self::get_fileshort()){ $phoenix_file = $root; $root = NULL; }
		/*fix*/if($root === NULL){ $root = dirname(__FILE__).'/'; }
		/*when $root is archive-name only*/ if(!preg_match('#[/]#i', $root) && strlen($root)>0){ $this->name = $root; $root = dirname(dirname(__FILE__)).'/'.$root.'/';}
		if(Phoenix::directory_exists($root)){
			$this->root = $root;
		}
		elseif($create !== FALSE){
			if(Phoenix::directory_exists(dirname($root)) && is_writeable(dirname($root)) ){
				if(!Phoenix::directory_exists($root) && Phoenix::is_authenticated()){
					mkdir($root, 0777 /*substr(sprintf('%o', fileperms(dirname($root))), -4)*/ );
				}
				$this->root = $root;
			}
		}
		$this->load_settings($phoenix_file, TRUE);
		/*fix*/ if(!is_array($this->settings) || count($this->settings) == 0){ $this->load_settings(NULL, FALSE); }
		if($src !== FALSE && strlen($src) >= 1 ){ $this->change_src( $src ); }
		/*fix*/ if($this->name === NULL){ $this->name = (is_dir($this->root) ? basename($this->root) : basename(dirname($this->root)) );}
	}
	function directory_exists($dir){ return (file_exists($dir) && is_dir($dir)) ; }
	function is_enabled(){
		if(!$this->get_src() || !isset($this->root) || strlen($this->root)<=1 || !Phoenix::directory_exists($this->root) ){ return FALSE; }
		return $this->is_authenticated();
	}
	function is_authenticated(){
		return (class_exists('Heracles') ? ( Heracles::is_authenticated() && Heracles::has_role('administrator') ) : TRUE);
	}

	function upgrade_available(){ return FALSE; }
	function git_enabled(){ return FALSE; }

	function get_framework_root($type=FALSE){
		if(!(PHOENIX_FRAMEWORK === FALSE)){
			if(class_exists(PHOENIX_FRAMEWORK) && method_exists(PHOENIX_FRAMEWORK, 'get_root')){
				$PF = new PHOENIX_FRAMEWORK;
				return $PF->get_root($type);
			}
		}
		return $this->root;
	}

	function get_root($flag=TRUE){ return ($flag === TRUE && isset($this) ? $this->root : PHOENIX_ARCHIVE); }
	function get_fileshort(){ return 'phoenix.json'; }

	function load_settings($file=NULL, $flag=TRUE){
		if(is_array($file)){ $this->settings = $file; }
		else{
			if($file === NULL){ $file = Phoenix::get_root($flag).Phoenix::get_fileshort(); }
			if(!file_exists($file) || substr(strtolower($file), (strlen(self::get_fileshort())*-1)) !== self::get_fileshort() ){ return FALSE; }
			$this->settings = json_decode(file_get_contents($file), TRUE);
		}

		/*fix*/ if($this->src === NULL && isset($this->settings[0]['src'])){ $this->change_src( $this->settings[0]['src'] ); }
	}
	function get_src(){
		if(isset($this->src)){ return $this->src; }
		#if(isset($this->settings['src'])){ return $this->settings['src']; }
		return FALSE;
	}
	function change_src($src){
		$this->src = $src;
	}
	function save_settings($file=NULL, $flag=TRUE){
		if($this->is_enabled()){
			if($file === NULL){ $file = Phoenix::get_root($flag).Phoenix::get_fileshort(); }
			return file_put_contens($file, json_encode($this->settings));
		}
		return FALSE;
	}
	function clear_settings(){
		$set = array();
		return $set;
	}
	function getIndexByName($archive){
		for($i=0;$i<count($this->settings);$i++){
			if(strtolower($this->settings[$i]['name']) == strtolower($archive)){ return $i; }
		}
		return FALSE;
	}
	function getMountByIndex($i){
		return (isset($this->settings[$i]['mount']) ? $this->settings[$i]['mount'] : (isset($this->settings[$i]['type']) ? $this->get_framework_root($this->settings[$i]['type']) :  NULL));
	}

	function get_backup($id=0){
		/*fix*/ if(is_int($id) && $id >= 0){ $id = $this->get_backup_id($id); }
		return $file;
	}
	function backup(){ return $backup; }
	function revert($to){ return self::restore($to); }
	function restore($id=0){
		/*fix*/ if(is_int($id) && $id >= 0){ $id = $this->get_backup_id($id); }
	}
	function cleanup($keep=0){ /*removes all backups except it keeps the last/most-recent $keep backups */ }
	function get_backup_id($keep=0){
		$list = scandir($this->root);
		$set = array();
		foreach($list as $i=>$f){
			if(preg_match("#[\[]([^\]]+)[\]][.](zip|tgz|tar.gz|bz)$#i", $f, $buffer)){
				$set[filemtime($this->root.$f)] = $f;
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

	function stall(){}

	function download($to=FALSE, $conf=array()){
		/*fix*/ if(is_array($to)){ $conf = $to; $to = FALSE; }
		if($to === FALSE){ $to = (
				is_array($conf) && isset($conf['repository'])
				? PHOENIX_ARCHIVE.$conf['repository'].(isset($conf['last-commit']['sha']) ? '-'.$conf['last-commit']['sha'] : NULL).'.zip'
				: PHOENIX_ARCHIVE.basename($this->get_src())
			); }
		$buffer = file_get_contents($this->get_src());
		file_put_contents($to, $buffer);
		chmod($to, 0777);
		return $to;
	}

	function update($save_settings=FALSE){ return self::upgrade(FALSE, $save_settings); }
	function git_pull($archive=NULL, $autocreate=FALSE, $save_settings=FALSE){
		if(self::git_enabled()){
			$index = $this->getIndexByName($archive);
			$mount = $this->getMountByIndex($index);
			//$mount = $this->settings[$index]['mount'];
			# return system('git -C '.$mount.' pull);
			//*alternate of:*/ return self::upgrade($archive, $autocreate, $save_settings);
		} else { return FALSE; }
	}
	function upgrade($archive=NULL, $autocreate=FALSE, $save_settings=FALSE){
		if($this->is_enabled()){
			if(self::git_enabled()){ self::git_pull($archive); }
			else{
				/* gets $this->src (download, unpack) and replaces $this->src */
				$this->install($this->download(), TRUE);
			}
			if($save_settings !== FALSE){ $this->save_settings(); }
		}
	}

	function git_clone($archive, $uninstall_first=FALSE){
		if(self::git_enabled()){
			$index = $this->getIndexByName($archive);
			$mount = $this->getMountByIndex($index);
			$data = self::get_github_data($src);
			# return system('git -C '.$mount.' clone '.$data['clone']);
		} else {
			return self::install($archive, $uninstall_first);
		}
	}
	function install($archive, $uninstall_first=FALSE){
		if(!file_exists($archive) && !preg_match("#[\.](zip)$#i", $archive)){ return FALSE; }
		if($this->is_enabled()){
			if($uninstall_first !== FALSE){ $this->uninstall($this->root, TRUE, TRUE); }
			if(self::git_enabled()){ return self::git_clone($archive); }
			else{
				$zip = new ZipArchive;
				$res = $zip->open($archive);
				if($res === TRUE){
					$zip->extractTo($this->root); //, $files
					$zip->close();
					$only = $this->_find_one_directory_only($this->root, TRUE);
					print '<!-- '.$only.' -->';
					if($only !== FALSE){ $this->_move_up_one_directory($this->root.$only.'/', TRUE); }
					return TRUE;
				}
			}
		}
		return FALSE;
	}
	private function _find_one_directory_only($dir, $ignore_archives=FALSE){
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
	private function _move_up_one_directory($dir, $remove=FALSE){
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

	function uninstall($dir=NULL, $recursive=TRUE, $keep_archives=TRUE){
		/*fix*/ if($dir === NULL){ $dir = $this->root; }
		if(!preg_match("#^(".$this->root.")#i", $dir)){ return FALSE; }
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


	function get_github_data($src=FALSE, $magic=TRUE/*array()*/){
		if(isset($this) && $src===FALSE){ $src = $this->src; }
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
			return $list;
		}
		else { return FALSE; }
	}
	/*private*/ function _get_value_of_span($str=NULL, $class=FALSE){
		if(preg_match('#[\<]span'.($class!==FALSE ? '(\sclass="'.$class.'")' : '(\s[^\>]+)?').'[\>]([^\<]+)[\<]/span[\>]#i', $str, $buffer)){
			return $buffer[2];
		}
		return $str;
	}
	/*private*/ function _get_github_tags($list, $tagstr='tags', $page_raw=FALSE){
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
	function fingerprint($dir, $root=FALSE){
		/*fix*/ if($root===FALSE){ $root = $dir; }
		if(!(substr($dir, 0, strlen($root)) == $root)){ return FALSE; }
		$prefix = substr($dir, strlen($root));
		/*fix*/ if(substr($dir, -1) != '/'){ $dir .= '/';}
		$db = array();
		if(preg_match('#\.zip#i', $root)){
			$z = explode('.zip', $root);
			$zz = md5($z[0]).'-'.sha1($z['0']);
			if(!file_exists('/tmp/'.$zz.'.zip')){
				file_put_contents('/tmp/'.$zz.'.zip', file_get_contents($z[0].'.zip'));
				$zcreated = TRUE;
			} else { $zcreated = FALSE; }

			/*fix*/ $cleanup = (preg_match('#^https\://github\.com/[^/]+/([^/]+)/archive/(.*)$#', $z['0'], $q) ? $q[1].'-'.$q[2].'/' : NULL);

			$db['/tmp/'.$zz.'.zip']['src'] = $z[0].'.zip';
			$db['/tmp/'.$zz.'.zip']['size'] = filesize('/tmp/'.$zz.'.zip');
			$db['/tmp/'.$zz.'.zip']['mtime'] = filemtime('/tmp/'.$zz.'.zip');
			$db['/tmp/'.$zz.'.zip']['mtime:iso8601'] = date('c', filemtime('/tmp/'.$zz.'.zip'));
			$db['/tmp/'.$zz.'.zip']['md5'] = @md5_file('/tmp/'.$zz.'.zip');
			$db['/tmp/'.$zz.'.zip']['sha1'] = @sha1_file('/tmp/'.$zz.'.zip');
			/*fix*/ $db['/tmp/'.$zz.'.zip']['clear'] = $cleanup;

			$zip = new ZipArchive;
			$zip->open('/tmp/'.$zz.'.zip');
			$db['/tmp/'.$zz.'.zip']['comment'] = $zip->getArchiveComment();

			for($i=0;$i<$zip->numFiles;$i++){
				$stat[$i] = $zip->statIndex($i);
				$raw = $zip->getFromIndex($i);
				$name = (substr($zip->getNameIndex($i), 0, strlen($cleanup)) == $cleanup ? substr($zip->getNameIndex($i), strlen($cleanup) ) : $zip->getNameIndex($i));
				/*fix*/ $name = (strlen($name) == 0 ? '/' : $name);
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
			//*debug*/ print_r($zip);
			if($zcreated === TRUE){ unlink('/tmp/'.$zz.'.zip'); }
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
	function fingerprint_compare($old=array(), $new=array(), $compare=0x0FF){ return self::fingerprint_diff($old, $new, $compare); }
	function fingerprint_diff($old=array(), $new=array(), $compare=0x0FF){
		/*fix*/ if(is_bool($compare)){ $compare = ($compare === TRUE ? 0x0FF : 0x000); }
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
				if(($compare & PHOENIX_COMPARE_MTIME_RENEW /*0x044*/ ) && count($diff[$i]['reason'])>=1){
					$diff[$i]['hint'] = ($nc['mtime'] > $oc['mtime'] ? 'upgrade' : 'rollback');
				} else {
					$diff[$i]['hint'] = 'inspect';
				}
				$diff[$i]['reason'][] = 'mtime';
			}
		}
		return $diff;
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
}
function Phoenix($pfile, $auto=FALSE, $save_settings=FALSE){
	$P = new Phoenix(NULL, FALSE, $auto, $pfile);
	return $P->upgrade($auto, $save_settings);
}
?>
