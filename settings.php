<?php
  // Global PHPLog settings
  // Bogdan Stancescu <bogdan@moongate.ro>, November 2002
  // $Id: settings.php,v 1.3 2002/12/16 22:28:03 bogdan Exp $

  unset($_PL);
  $_PL["globals"]["path"]["path"]=dirname(__FILE__);
  $_PL_path=&$_PL["globals"]["path"]["path"];
  $_PL["globals"]["path"]["inc"]="$_PL_path/include";
  $_PL_inc=&$_PL["globals"]["path"]["inc"];
  $_PL["globals"]["paths"]["classes"]="$_PL_inc/classes";
  $_PL_classes=&$_PL["globals"]["paths"]["classes"];
  $_PL["globals"]["paths"]["store"]="$_PL_path/store";
  $_PL_store=&$_PL["globals"]["paths"]["store"];
  $_PL["globals"]["paths"]["parserLock"]="$_PL_store/parser.lock";
  $_PL_parser_lock=&$_PL["globals"]["paths"]["parserLock"];
  $_PL["globals"]["paths"]["echoMonLock"]="$_PL_store/echoMon.lock";
  $_PL_echo_lock=&$_PL["globals"]["paths"]["echoMonLock"];
  $_PL["globals"]["paths"]["milcov_lib"]="$_PL_path/include/milcov.php";
  $_PL_milcov_lib=&$_PL["globals"]["paths"]["milcov_lib"];
?>
