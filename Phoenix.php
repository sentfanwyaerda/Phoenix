<?php
class Phoenix {
	var $settings = array();
	var $src; /* http://www.github.com/ */
	var $root; /* /www/module/ */

	function Phoenix($root=NULL, $src=FALSE){
		if($root === NULL){ $root = dirname(__FILE__).'/'; }
		$this->root = $root;
		$this->load_settings();
		if($src === FALSE && isset($this->settings['src'])){ $this->src = $this->settings['src']; } else { $this->src = $src; }
	}
	
	function get_root(){ return (isset($this) ? $this->root : NULL); }
	function get_fileshort(){ return 'phoenix.json'; }

	function load_settings($file=NULL){
		if($file === NULL){ $file = Phoenix::get_root().Phoenix::get_fileshort(); }
		if(!file_exists($file)){ return FALSE; }
		$this->settings = json_decode(file_get_contents($file), TRUE);
	}
	function save_settings($file=NULL){
		if($file === NULL){ $file = Phoenix::get_root().Phoenix::get_fileshort(); }
		file_put_contens($file, json_encode($this->settings));
	}
	
	function backup(){ return $backup; }
	function restore($backup){}
	function cleanup($keep=0){ /*removes all backups except it keeps the last/most-recent $keep backups */ }
	
	function update($save_settings=FALSE){
		/* gets $this->src (download, unpack) and replaces $this->src */
		if($save_settings !== FALSE){ $this->save_settings(); }
	}
}

//testing:
print '<pre>';
$Ph = new Phoenix();
print_r($Ph);
print '</pre>';
?>
