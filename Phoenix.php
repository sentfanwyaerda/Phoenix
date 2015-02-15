<?php
class Phoenix {
	var $src = NULL; /* http://www.github.com/ */
	var $root = FALSE; /* /www/module/ */
	var $settings = array();

	function Phoenix($root=NULL, $src=FALSE, $create=FALSE){
		if($root === NULL){ $root = dirname(__FILE__).'/'; }
		if($create === FALSE){
			if(Phoenix::directory_exists($root)){
				$this->root = $root;
			}
		}
		else{
			if(Phoenix::directory_exists(dirname($root))){
				if(!Phoenix::directory_exists($root) && Phoenix::is_authenticated()){
					mkdir($root, substr(sprintf('%o', fileperms(dirname($root))), -4) );
				}
				$this->root = $root;
			}
		}
		$this->load_settings();
		if($src !== FALSE ){ $this->change_src( $src ); }
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
	
	function update($save_settings=FALSE){
		if($this->is_enabled()){
			/* gets $this->src (download, unpack) and replaces $this->src */
			if($save_settings !== FALSE){ $this->save_settings(); }
		}
	}
}

//testing:
print '<pre>';
$Ph = new Phoenix();
print 'Phoenix status is <strong>'.($Ph->is_enabled() ? 'enabled' : 'disabled')."</strong>\n";
print_r($Ph);
$Ph->change_src('https://github.com/sentfanwyaerda/Phoenix/archive/98024c1655673b193a34e191d1cdaf4f85fb566d.zip');
print_r($Ph);
print '</pre>';
?>
