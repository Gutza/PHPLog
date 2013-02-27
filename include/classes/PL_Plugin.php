<?php

  // PHPLog plugin file
  // Bogdan Stancescu <bogdan@moongate.ro>, December 2002
  // $Id: PL_Plugin.php,v 1.1 2002/12/19 18:59:53 bogdan Exp $
  
  /*
    Rewrite the method in this class to perform whatever reactions
    you wish. Make sure you list your reaction verbs in reactions.lst
    so they get recognized as such by the configuration parser.

    Also make sure you fall back to $this->react() if your plugin
    doesn't recognize the reaction verb.
  */

  class PL_Exec extends _PL_Exec
  {
    function react($reaction, $line, $conf)
    {
      return($this->_react($reaction, $line, $conf));
    }
  }

?>
