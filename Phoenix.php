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
}
?>