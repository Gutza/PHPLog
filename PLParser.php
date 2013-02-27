<?php
  /**
  * PHP Log Parser - the daemon
  * Bogdan Stancescu <bogdan@moongate.ro>, November 2002
  * $Id: PLParser.php,v 1.17 2003/01/23 00:04:10 bogdan Exp $
  */

  /*

    This should run in the background and parse the logs.
    It's as lightweight as possible, and only performs very basic, fast
    parsing of the log files based on the settings in phplog.conf.

    Please note this file doesn't do anything itself! It just parses
    logs continuously and dumps the data in preformatted files. Upon
    finding matching logs, it saves the lines matching a rule along
    with the rule and file id in a temp file. Some monitor must take
    it from there.

    The advantage of this architecture is flexibility and modularity.



    For developers:

    The only tricky things below are the $config->config array structure
    and the duplicates management. For the former, uncomment the print_r()
    and exit() lines just below $conf->parse. The latter is commented below.

    We have to perform duplicate checking in two stages. First, we go through
    all of the log file/log file rule pairs and don't use defaults at all.
    Instead, we store lines matching default rules - if any default rules are
    present - in the $default array. If we find any rule matching a previously
    known default, we apply the first rule we encounter and mark the default
    as rendered. The second stage is applying all known, not rendered defaults.
  */

  error_reporting(E_ERROR | E_WARNING | E_PARSE);

  $_PL_path=dirname(__FILE__);
  set_time_limit(0);
  $_PL_verbosity=0;
  $_PL_timereport=0;

  if (!$fp=@fopen("/var/run/PLParser.pid","w")) {
    echo "No permissions to write pid file";
    exit;
  }
  fputs($fp,getmypid());
  fclose($fp);

  // read library
  include("$_PL_path/PLParser_lib.php");

  startWatch("PLParser");

  // read settings
  include("$_PL_path/settings.php");

  // read config
  include("$_PL_classes/PL_Conf.php");

  $conf=new PL_Conf;
  $conf->parse("$_PL_path/phplog.rules");
  // print_r($conf);
  // exit;

  $_PL_default_group=$conf->config["config"]["group"];
  if ($_PL_default_group) {
    // echo("setting \$_PL_store to {$conf->config['group'][$_PL_default_group]['path']}\n");
    $_PL_store=$conf->config["group"][$_PL_default_group]["path"];
  }
  if (!is_dir($_PL_store)) {
    $conf->addError("Default store directory $_PL_store not found!");
    exit;
  }

  $fp=@fopen("$_PL_store/write_test","w+");
  if (!$fp) {
    $conf->addError("Default store directory $_PL_store is not writable!");
    exit;
  }
  fclose($fp);
  unlink("$_PL_store/write_test");

  initSaveNo();

  $fp=@fopen($_PL_parser_lock,"w");
  if (!$fp) {
    $conf->addError("Unable to write to monitor lock file $_PL_parser_lock!");
    exit;
  }
  fclose($fp);
  unlink("$_PL_parser_lock");

  for ($i=1;isset($conf->conf[$i]);$i++) {
    $fileDef=&$conf->conf[$i];
    if (!is_readable($fileDef["file"])) {
      if (file_exists($fileDef["file"])) {
        $conf->addError("Unable to read from log file ".$fileDef["file"]);
      } else {
        $conf->addError("File ".$fileDef["file"]." doesn't exist, or can't read in directory!");
      }
      exit;
    }
    $fileDef["pos"]=filesize($fileDef["file"]);
    $fileDef["stamp"]=filemtime($fileDef["file"]);
    explain("Log file ".$fileDef["file"]." ok, size ".$fileDef["pos"].", timestamp ".$fileDef["stamp"],3);
  }

  $sleep=$conf->config["config"]["sleep"];
  if (!$sleep) {
    $conf->addError("Sleep set neither by rules nor by brain - something's wrong here!");
    exit;
  }
  
  getWatch("Initialization","PLParser");

  while (true) { // MAIN LOOP - START
    sleep($sleep);
    clearstatcache();
    for ($i=1;isset($conf->conf[$i]);$i++) { // FILES - START
      $fileDef=&$conf->conf[$i];
      $stamp=filemtime($fileDef["file"]);
      if ($stamp==$fileDef["stamp"]) {
        explain("Log file ".$fileDef["file"]." unchanged (old timestamp=".$fileDef["stamp"]."; new timestamp=$stamp)",6);
      } else {
        startWatch("loop");
        explain("Log file ".$fileDef["file"]." changed!",1);
        $fileDef["stamp"]=$stamp;
        $size=filesize($fileDef["file"]);
        $size_left=$size-$fileDef["pos"]+1;
        if ($size<$fileDef["pos"]) {
          explain("Log file ".$fileDef["file"]." smaller than previously - resetting pointer!",1);
          $fileDef["pos"]=0;
          $size_left=$size;
        }
        $fp=fopen($fileDef["file"],"r");
        fseek($fp,$fileDef["pos"]);
        while ($line=fgets($fp,$size_left)) { // LINES - START
          if (substr($line,-1)!="\n") {
            explain("Heh! Line [$line] not finished!",1);
            $fileDef["pos"]=ftell($fp)-strlen($line);
            continue 2;
          }
          $size_left-=strlen($line);
          $line=substr($line,0,-1);
          explain("Read line $line from file {$fileDef['file']}",5);
          reset($fileDef["actions"]);
          $acknowledged=false;
          $defaultId=$fileDef["default"];
          while(list($aid,$action)=each($fileDef["actions"])) { // ACTIONS - START
            if ($aid===$defaultId) {
              explain("Default ($defaultId) - skipping",5);
              continue;
            }
            $func=$action["func"];
            $regex=$action["regex"];
            explain("Checking with $func against $regex",6);
            if ($func($regex,$line,$result)) {
              $acknowledged=1;
              explain("It's a match for action #$aid - we'll {$action['verb']}!",1);
              if ($action["verb"]!="ignore") {
                $default[]=array("line"=>$line,"fileId"=>$i,"actionId"=>$aid,"rendered"=>true);
                storeEntry($line, $i, $aid);
                $defId=which_default($line);
                if ($defId!==false) {
                  explain("Also removing duplicate",5);
                  $default[$defId]["rendered"]=true;
                }
              }
              continue 2;
            }
          } // ACTIONS - END
          // print_r($default);
          if (
              (!$acknowledged) &&
              (isset($defaultId) || isset($conf->conf["default"])) &&
              (
                (($conf->config["config"]["ignore_duplicates"]=="on") && (which_default($line)===false)) ||
                ($conf->config["config"]["ignore_duplicates"]!="on")
              )
          ) {
            if (isset($conf->conf[$i]["actions"][$defaultId])) {
              explain("Storing as local default (stage 1) [$line] in [{$fileDef['file']}]",5);
              $default[]=array("line"=>$line, "fileId"=>$i, "actionId"=>$defaultId);
              continue;
            } elseif(isset($conf->conf["default"])) {
              // echo("\$i=$i; \$defaultId=$defaultId\n");
              explain("Storing as GLOBAL default (stage 1) [$line] in [{$fileDef['file']}]",5);
              $default[]=array("line"=>$line, "fileId"=>$i, "actionId"=>-1);
              continue;
            }
            explain("No default for this line",6);
          }
        } // LINES - END
        $fileDef["pos"]=ftell($fp);
        fclose($fp);
        getWatch("Changed file parse","loop");
        explain("----",5);
      }
    } // FILES - END
    // print_r($default2);
    @reset($default);
    while (list(,$def)=@each($default)) {
      if (($def["rendered"]) || (!$def["old"])) {
        explain("Skipping youngster: rendered={$def['rendered']}; old={$def['old']}",5);
        continue;
      }
      explain("Giving up (stage 2) on last loop's line {$def['line']} and using the default",5);
      storeEntry($def["line"],$def["fileId"],$def["actionId"]);
    }
    // print_r($entries);
    saveEntries();
    unset($default2);
    @reset($default);
    while (list(,$def)=@each($default)) {
      if (($def["rendered"]) || ($def["old"])) {
        explain("Skipping oldie: rendered={$def['rendered']}; old={$def['old']}",5);
        continue;
      }
      explain("Aging",5);
      $def["old"]=true;
      $default2[]=$def;
    }
    $default=$default2;
  } // MAIN LOOP - END
?>
