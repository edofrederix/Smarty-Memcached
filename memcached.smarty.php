<?php

  /*
  Smarty Memcached - a Wrapper to Smarty to allow proper memcache-based
  caching

  Class code, part of Smarty Memcached

  Written by:

  Edo Frederix
  edofrederix@gmail.com
  http://edoxy.net, https://github.com/edofrederix


  This file is part of Smarty Memcached.

  Smarty Memcached is free software: you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published by the Free
  Software Foundation, either version 3 of the License, or (at your option)
  any later version.

  Smarty Memcached is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
  or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
  more details.

  You should have received a copy of the GNU General Public License along
  with Smarty Memcached. If not, see <http://www.gnu.org/licenses/>.
  */
  
  //Load Smarty library
  define('SMARTY_DIR', '/usr/lib/php5/Smarty/');
  require(SMARTY_DIR . 'Smarty.class.php');
  
  //Extend the smarty class
  class Smarty_Memcached extends Smarty {
  
    public $Memcached_debug = true;
    
    public $output;
    
    public $mc;
    public $mcHost = "127.0.0.1";
    public $mcPort = 11211;
    public $ttl = array('short' => 600, 'medium' => 1800, 'long' => 7200);
  
    function __construct() {
  
      parent::__construct();
  
      $this->template_dir = SMARTY_DIR . 'templates/';
      $this->compile_dir  = SMARTY_DIR . 'templates_c/';
      $this->config_dir   = SMARTY_DIR . 'configs/';
      $this->cache_dir    = SMARTY_DIR . 'cache/';
  
      $this->assign('app_name', 'Memcached');
      
      //Let's not use Smarty's caching, but our own
      $this->caching = false;
      $this->Memcached_connectMemcache();
      
    }
    
    function Memcached_connectMemcache(){
    
      $this->mc = memcache_pconnect($this->mcHost, $this->mcPort);
      if(!$this->mc){
        //if we cannot connect to memcache, we cannot collect cache or check if cache is being generated.
        //the page will then run live, and this is dangerous, so exit with error.
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        echo "500 - Internal Server Error (#901), please refresh page.";
        exit;
        
      } else {
        return true;
        
      }

    }   
    
    function Memcached_getCache($key, $method='append'){
    
      if(!preg_match("/^(?:append)|(?:return)$/", $method)){
        $method = 'append';
      }    
    
      //if debug, return false to run a live request
      if($this->Memcached_debug){
        return false;
      }

      if($data = memcache_get($this->mc, $key)){
      
        $data = "<!-- from cache -->" . $data;
        if($method == 'append'){
          $this->output .= $data;
          return true;
        } else {
          return $data;
        }
      
      } else {

        if($lock = memcache_get($this->mc, "lock_" . $key)){
        
          //wait for another process to fill cache        
          for($i=1; $i<=10; $i++){
          
            if($data = memcache_get($this->mc, $key)){
              if($method == 'append'){
                $this->output .= $data;
                return true;
              } else {
                return $data;
              }
              
            } else {
              //wait for 100ms
              usleep(100000);
            }           
          }
                  
          //quit this effort, but don't run live
          if($method == 'append'){
            $this->output .= "Could not load data";
            return true;
          } else {
            return "Could not load data";
          }
          
        } else {
        
          //create lock, generate cache
		 	    memcache_set($this->mc, "lock_" . $key, 1, 0, 10);
		 	    return false;
		 	    
		 	  }
      }
    }
        
    function Memcached_fetch($key, $tpl, $ttl='medium', $method='append'){
    
      if(!preg_match("/^(?:append)|(?:return)$/", $method)){
        $method = 'append';
      }
    
      //fetch template
      $content = $this->fetch($tpl);
    
      //if debugging, clear cache and return
      if($this->Memcached_debug){
        memcache_delete($this->mc, $key, 0);
        memcache_delete($this->mc, "lock_" . $key, 0);
        
        if($method == 'append'){
          $this->output .= $content;
          return true;
        } else {
          return $content;
        }
      }
      
      if(preg_match("/^(?:short)|(?:medium)|(?:long)$/", $ttl)){
        $ttl = $this->ttl[$ttl];   
      } else {
        $ttl = $this->ttl['medium'];
      }
    
      //distribute TTL between ttl/2 and ttl*3/2
      $ttl = round($ttl/2 + $ttl*mt_rand(0,1024)/1024);

      
      //set cache
      if(!memcache_set($this->mc, $key, $content, 0, $ttl)) {  
      
        if($method == 'append'){         
          $this->output .= "Could not save data.";
          return false;
        } else {
          return "Could not save data.";
        }
        
      } else {
      
        //delete lock
        memcache_delete($this->mc, "lock_" . $key, 0);     
      
        if($method == 'append'){   
          $this->output .= $content;
          return true;
        } else {
          return $content;
        }
      }
    }
    
    function Memcached_replaceTag($tag, $content){

      if($this->output = preg_replace("/\[\*[ ]?" . $tag . "[ ]?\*\]/is", $content, $this->output)){
        return true;
      } else {
        false;
      }
    
    }

  }

?>