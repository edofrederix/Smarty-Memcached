<?php
  /*
  Smarty Memcached - a Wrapper to Smarty to allow proper memcache-based
  caching
  
  Example code, part of Smarty Memcached

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
  
	//Start Smarty Memcached class
  $smarty = New Smarty_Memcached();  

  //get a cached template
  $key = "cached-template";
  
  /* The Memcached_getCache function will try to fetch the data specified by
  $key from Memcached. If it exists, its content will be added to
  $smarty->output. If it doesn't, it will return false, and we know that we
  have to (re)generate the cache file. */
  if(!$smarty->Memcached_getCache($key)) {
    
    /* Here we place the code to generate the template. This can be fancy
    MySQL queries and difficult computaions. We only need to perform all of
    this once, because its result will get cached. */
    $data = do_fancy_stuff();
    $smarty->assign('data', $data); 
    
    /* Now that we have computed everything, we can fetch the template. We
    specify the Memcached key, the template to fetch, and for how long we want
    to cache it. */
    $smarty->Memcached_fetch($key, './tpl/template.tpl', 'long');
  
  }
  
  /* Either Memcached_getCache or Memcached_fetch has assigned our template to
  $smarty-output. We can now echo it. */
  echo $smarty->output;
?>