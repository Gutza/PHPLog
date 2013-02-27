<?php

  // Common PHPLog library
  // Bogdan Stancescu <bogdan@moongate.ro>, December 2002
  // $Id: PL_common.php,v 1.2 2002/12/19 15:45:54 bogdan Exp $
  
  function parse_format($format)
  {
    global $parser_data;
    if (!is_array($parser_data)) {
      return("");
    }
    reset($parser_data);
    while (list($parse_key,$parse_val)=each($parser_data)) {
      $format=str_replace("<%".$parse_key."%>",$parse_val,$format);
    }
    return($format);
  }

?>
