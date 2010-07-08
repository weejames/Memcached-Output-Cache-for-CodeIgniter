<?php

class MY_Output extends CI_Output {
	
	/**
	 * Write a Cache File
	 *
	 * Stock CI method altered to write cache to a mongodb instance as specified in application/config/mongodb.php
	 * Additionally the $GET and $POST arrays are taken into account when caching a page.
	 *
	 * @access	public
	 * @return	void
	 */	
	function _write_cache($output)
	{
		$CI =& get_instance();	
		
		$CI->config->load('memcached');
		
		$m = new Memcache;
		
		$memcached_server =$CI->config->item('server', 'memcached');
		$memcached_port = $CI->config->item('port', 'memcached');
		
		try {
			$m = new Memcache;
			$m->connect($memcached_server, $memcached_port);
		} catch (Exception $e) {
			log_message('error', "Unable to connect to memcached server: ".$memcached_server.":".$memcached_port);
			return;
		}
		
		$cache_key = $CI->config->item('base_url').
					$CI->config->item('index_page').
					$CI->uri->uri_string().
					serialize($_POST).
					serialize($_GET);
		
		$expire = time() + ($this->cache_expiration * 60);

		try {
			$m->set( md5($cache_key), $output, null, $expire);
			log_message('debug', "Memcached cache written");			
		} catch (Exception $e) {
			log_message('debug', "Unable to write to memcached");
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update/serve a cached file
	 *
	 * @access	public
	 * @return	void
	 */	
	function _display_cache(&$CFG, &$URI)
	{
		$CFG->load('memcached');
		
		$memcached_server = $CFG->item('server', 'memcached');
		$memcached_port = $CFG->item('port', 'memcached');
		
		try {
			$m = new Memcache;
			$m->connect($memcached_server, $memcached_port);
		} catch (Exception $e) {
			log_message('error', "Unable to connect to memcached server: ".$memcached_server.":".$memcached_port);
			return;
		}

		$cache_key = $CFG->item('base_url').
					$CFG->item('index_page').
					$URI->uri_string().
					serialize($_POST).
					serialize($_GET);
		
		$cache_doc = $m->get( $cache_key );
		
		if (!$cache_doc) {
			log_message('debug', "No cache document found");
			return;
		}

		// Display the cache
		$this->_display($cache_doc);
		log_message('debug', "Cache document is current. Sending it to browser.");		
		return TRUE;
	}
	
}