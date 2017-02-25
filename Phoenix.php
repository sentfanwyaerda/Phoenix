<?php
class Phoenix {
	var $src = NULL; /* http://www.github.com/ */
	var $root = FALSE; /* /www/module/ */
	var $settings = array();

	function Phoenix($root=NULL, $src=FALSE, $create=FALSE){
		/*notify*/ print '<!-- new Phoenix("'.$root.'", '.($src === FALSE ? 'FALSE' : '"'.$src.'"').', '.($create === FALSE ? 'FALSE' : 'TRUE').') -->'."\n";
		if($root === NULL){ $root = dirname(__FILE__).'/'; }
		if(Phoenix::directory_exists($root)){
			$this->root = $root;
		}
		elseif($create !== FALSE){
			if(Phoenix::directory_exists(dirname($root))){
				if(!Phoenix::directory_exists($root) && Phoenix::is_authenticated()){
					mkdir($root, 0777 /*substr(sprintf('%o', fileperms(dirname($root))), -4)*/ );
				}
				$this->root = $root;
			}
		}
		$this->load_settings();
		if($src !== FALSE && strlen($src) >= 1 ){ $this->change_src( $src ); }
	}
	function directory_exists($dir){ return (file_exists($dir) && is_dir($dir)) ; }
	function is_enabled(){
		if(!$this->get_src() || !isset($this->root) || strlen($this->root)<=1 || !Phoenix::directory_exists($this->root) ){ return FALSE; }
		return $this->is_authenticated();
	}
	function is_authenticated(){
		return (class_exists('Heracles') ? ( Heracles::is_authenticated() && Heracles::has_role('administrator') ) : TRUE);
	}

	function get_root(){ return (isset($this) ? $this->root : NULL); }
	function get_fileshort(){ return 'phoenix.json'; }

	function load_settings($file=NULL){
		if(is_array($file)){ $this->settings = $file; }
		else{
			if($file === NULL){ $file = Phoenix::get_root().Phoenix::get_fileshort(); }
			if(!file_exists($file)){ return FALSE; }
			$this->settings = json_decode(file_get_contents($file), TRUE);
		}

		/*fix*/ if($this->src === NULL && isset($this->settings['src'])){ $this->change_src( $this->settings['src'] ); }
	}
	function get_src(){
		if(isset($this->src)){ return $this->src; }
		#if(isset($this->settings['src'])){ return $this->settings['src']; }
		return FALSE;
	}
	function get_project(){
		return (isset($this->settings['name']) ? $this->settings['name'] : FALSE);
	}
	function change_src($src){
		$this->src = $src;
	}
	function save_settings($file=NULL){
		if($this->is_enabled()){
			if($file === NULL){ $file = Phoenix::get_root().Phoenix::get_fileshort(); }
			return file_put_contens($file, json_encode($this->settings));
		}
		return FALSE;
	}

	function get_backup($id=0){
		/*fix*/ if(is_int($id) && $id >= 0){ $id = $this->get_backup_id($id); }
		return $file;
	}
	function backup(){ return $backup; }
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

	function download($to=FALSE){
		if($to === FALSE){ $to = $this->root.basename($this->get_src()); }
		$buffer = file_get_contents($this->get_src());
		file_put_contents($to, $buffer);
		chmod($to, 0777);
		return $to;
	}

	function update($save_settings=FALSE){
		if($this->is_enabled()){
			/* gets $this->src (download, unpack) and replaces $this->src */
			$this->install($this->download(), TRUE);
			if($save_settings !== FALSE){ $this->save_settings(); }
		}
	}

	function install($archive, $uninstall_first=FALSE){
		if(!file_exists($archive) && !preg_match("#[\.](zip)$#i", $archive)){ return FALSE; }
		if($this->is_enabled()){
			if($uninstall_first !== FALSE){ $this->uninstall($this->root, TRUE, TRUE); }
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
	function fingerprint($dir, $root=FALSE){
		/*fix*/ if($root===FALSE){ $root = $dir; }
		if(!(substr($dir, 0, strlen($root)) == $root)){ return FALSE; }
		$prefix = substr($dir, strlen($root));
		/*fix*/ if(substr($dir, -1) != '/'){ $dir .= '/';}
		$db = array();
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
				} else { $db = array_merge($db, self::fingerprint($dir.$f.'/', $root)); }
			}
		}
		return $db;
	}
}
?>
