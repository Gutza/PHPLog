<?php
  /**
  * PHP Log Console Monitor
  * Bogdan Stancescu <bogdan@moongate.ro>, December 2002
  * $Id: PLConsoleMonitor.php,v 1.19 2003/01/23 00:04:10 bogdan Exp $
  */

  /*

    This is the Console Monitor. Run it in a console to use
    the temp files created by the parser and echo the appropriate
    entries in a console.
    
    Please note this also performs the other actions in PHPLib by
    using include/classes/PL_Executor.php via include/classes/PL_Plugin.php

  */

  error_reporting(E_ERROR | E_WARNING | E_PARSE);

  $_PL_path=dirname(__FILE__);
  set_time_limit(0);
  $_PL_verbosity=0;
  $_PL_timereport=0;

  $_PL_argv=$argv;
  $_PL_command=array_shift($_PL_argv);
  if (in_array("-w",$_PL_argv)) {
    $_PL_rawdata=true;
  } else {
    $_PL_rawdata=false;
  }

  // read library
  require("$_PL_path/PLParser_lib.php");

  startWatch("PLConsoleMonitor");

  // read settings
  require("$_PL_path/settings.php");

  // read config
  require("$_PL_classes/PL_Conf.php");

  // include Milcov library
  require("$_PL_milcov_lib");
  require("$_PL_inc/PL_common.php");
  require("$_PL_classes/PL_Executor.php");
  require("$_PL_classes/PL_Plugin.php");
  $PLE=new PL_Exec();

  $conf=new PL_Conf;
  $conf->parse("$_PL_path/phplog.rules");
  // print_r($conf);
  // echo("-----------\n");
  // exit;

  /*
  $fp=@fopen($_PL_echo_lock,"w");
  if (!$fp) {
    $conf->addError("Unable to write to parser lock file $_PL_echo_lock!");
    exit;
  }
  fclose($fp);
  unlink("$_PL_echo_lock");
  */

  $sleep=$conf->config["config"]["sleep"];
  if (!$sleep) {
    $conf->addError("Sleep set neither by rules nor by brain - something's wrong here!");
    exit;
  }

  getWatch("Initialization","PLConsoleMonitor");

  @reset($conf->config["group"]);
  $ok=false;
  while (list($gid,$group)=@each($conf->config["group"])) {
    if($group["path"]==$_PL_store) {
      $ok=true;
    }
  }
  if (!$ok) {
    @reset($conf->config["group"]);
    $ok=true;
    $idx="";
    do {
      while (list($gid,$group)=@each($conf->config["group"])) {
        if ($gid=="fakeGroup$idx") {
          $ok=false;
        }
      }
      $idx++;
    } while (!$ok);
    $conf->config["group"]["fakeGroup$idx"]=array("path"=>$_PL_store);
  }

  while (true) {
    clearstatcache();
    reset($conf->config["group"]);
    while (list($gid,$group)=each($conf->config["group"])) {
      $path=$group['path'];
      unset($dir);
      if (!is_readable($path)) {
        continue;
      }
      $dir=dir($path);
      $entries=array();
      while ($entry=$dir->read()) {
        if (($entry==".") || ($entry=="..") || (is_dir("$path/$entry"))) {
          continue;
        }
        $entries[]=$entry;
      }
      asort($entries);
      foreach($entries as $entry) {
        explain("Checking temp file $entry",5);
        if (!is_readable("$path/$entry")) {
          continue;
        }
        $data=file_get_contents("$path/$entry");
        $log_lines=unserialize($data);
        if (!is_array($log_lines)) {
          echo "Data invalid:\n";
          var_dump($log_lines);
          echo "\nOriginal data: \n$data\n";
          echo "File name: $path/$entry\n";
          //@unlink("$path/$entry");
          continue;
        }
        while (list($log_key,$log_line)=each($log_lines)) {
          explain("Checking log key #$log_key: fileId={$log_line['fileId']}; actionId={$log_line['actionId']}",3);
          if ($log_line["actionId"]==-1) { // Global default
            // echo("DEFAULT: cc={$conf->conf['default']}; conf=");
            // print_r($conf->conf);
            $reactions=$conf->conf["default"]["actions"][0]["reactions"];
          } else {
            $reactions=$conf->conf[$log_line["fileId"]]["actions"][$log_line["actionId"]]["reactions"];
          }
          // echo("-----------\n");
          // echo("Log line: "); print_r($log_line);
          // echo("Reactions: "); print_r($reactions);
          $rendered=false;
          while (list($key,$reaction)=each($reactions)) {
            explain("Checking reaction #$key: verb={$reaction['verb']}; param: {$reaction['param']}; rendered: $rendered",5);
            if ($reaction["verb"]=="echo") {
              if (!$rendered) {
                explain("Will echo this",5);
                if (!$_PL_rawdata) {
                  $styles=explode(";",$reaction["param"]);
                  while (list(,$style)=@each($styles)) {
                    $style=trim($style);
                    if (!$style) {
                      continue;
                    }
                    $mCode=MgetCode(strtoupper($style));
                    if ($mCode===false) {
                      continue;
                    }
                    echo($mCode);
                  }
                }
                echo($conf->conf[$log_line["fileId"]]["file"].":".$log_line["line"]);
                if (!$_PL_rawdata) {
                  echo((MgetCode("DEFAULT")));
                }
                echo("\n");
                $rendered=true;
              }
            } else {
              explain("Performing other reaction",3);
              if (!$PLE->react($reaction, $log_line, $conf)) {
                $conf->addError("Unable to perform reaction \"{$reaction['verb']}\"");
              }
            }
          }
        }
        @unlink("$path/$entry");
      }
    }
    sleep($sleep);
  }
?>
