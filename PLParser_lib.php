<?php
  /**
  * PHPMon library
  * Bogdan Stancescu <bogdan@moongate.ro>, November 2002
  * $Id: PLParser_lib.php,v 1.10 2003/01/23 00:04:10 bogdan Exp $
  */
  
  if ($_PL_verbosity) {
    function explain($msg, $level)
    {
      global $_PL_verbosity;
      if ($level<=$_PL_verbosity) {
        if (substr($msg,-1)!="\n") {
          $msg.="\n";
        }
        echo("MSG$level: $msg");
      }
    }
  } else {
    function explain($msg, $level) {}
  }

  function storeEntry($line, $fileId, $actionId)
  {
    global $entries;

    $entries[]=array(
      "line"=>$line,
      "fileId"=>$fileId,
      "actionId"=>$actionId,
      "date"=>time()
    );
  }

  function initSaveNo()
  {
    global $saveNo, $conf, $groupSaveNo;

    $saveNo=1;
    
    while (list($gid,$group)=@each($conf->config["group"])) {
      if ($myGroup["path"]) {
        $myPath=$myGroup["path"];
        $mySaveNo=&$groupSaveNo[$group];
      } else {
        $myPath=$_PL_store;
        $mySaveNo=&$saveNo;
      }
      $d=dir($myPath);
      while (false !== ($entry = $d->read())) {
        if (($entry==".") || ($entry=="..") ||
        (is_dir("$myPath/$entry")) || (!preg_match("/[0-9]+/",$entry))) {
          continue;
        }
        if ($entry>$saveNo) {
          $saveNo++;
        }
      }
    }
  }
  
  function saveEntries()
  {
    global $entries, $_PL_store, $saveNo, $_PL_parser_lock, $conf, $groupSaveNo;

    if(!count($entries)) {
      return(true);
    }

    for ($i=0;$i<count($entries);$i++) {
      $entry=$entries[$i];
      $entryGroup=$conf->conf[$entry["fileId"]]["actions"][$entry[actionId]]["group"];
      if (!$entryGroup) {
        $entries_noGroup[]=$entries[$i];
      } else {
        $entries_group[$entryGroup][]=$entries[$i];
      }
    }
    $entries=array();

    // Yes, this time the file lock is safe. That's because the parser
    // first writes the lock and then creates the file, whereas the monitors
    // first read the entries in the directory and then check for the lock.
    // That way, even if the parser happens to write new files after the monitors
    // checked for the lock, the monitors won't be aware of the new files.
    fclose(fopen($_PL_parser_lock,"w"));

    if (count($entries_noGroup)) {
      // Saving the noGroup entries first
      do $saveNo++; while (file_exists("$_PL_store/$saveNo"));
      $fname="$_PL_store/$saveNo";

      $str=serialize($entries_noGroup);
      $fp=fopen($fname,"w");
      if (!$fp) {
        return(false);
      }
      fputs($fp,$str,strlen($str));
      fclose($fp);
    }

    // And now the group entries
    while(list($group,$myEntries)=@each($entries_group)) {
      // echo("My entries for group $group:\n");
      // var_dump($myEntries);

      $myGroup=$conf->config["group"][$group];
      if ($myGroup["path"]) {
        $myPath=$myGroup["path"];
        $mySaveNo=&$groupSaveNo[$group];
      } else {
        $myPath=$_PL_store;
        $mySaveNo=&$saveNo;
      }
      do $mySaveNo++; while (file_exists("$myPath/$mySaveNo"));

      $str=serialize($myEntries);
      $fname="$myPath/$mySaveNo";
      $fp=fopen($fname,"w");
      if (!$fp) {
        return(false);
      }
      fputs($fp,$str,strlen($str));
      fclose($fp);

      if (($myGroup["owner"]) && (!@chown($fname,$myGroup["owner"]))) {
        echo("Unable to set owner [{$myGroup['owner']}], according to group [$group]\n");
      }
      if (($myGroup["group"]) && (!@chgrp($fname,$myGroup["group"]))) {
        echo("Unable to set group [{$myGroup['group']}], according to group [$group]\n");
      }
      if (($myGroup["perm"]) && (!@chmod($fname,octdec($myGroup["perm"])))) {
        echo("Unable to set permissions [{$myGroup['perm']}], according to group [$group]\n");
      }
    }

    unlink($_PL_parser_lock);
    return(true);
  }
  
  if ($_PL_timereport) {
    function startWatch($timer)
    {
      global $_PL_timer;
      list($usec, $sec) = explode(" ",microtime());
      $_PL_timer[$timer]=(float)$usec + (float)$sec;
      return(true);
    }

    function getWatch($comment, $timer)
    {
      global $_PL_timer;
      if (!isset($_PL_timer[$timer])) {
        echo("TIMER: Warning: timer $timer not initialized. Starting it now and ignoring request.\n");
        startWatch($timer);
        return(false);
      }
      list($usec, $sec) = explode(" ",microtime());
      echo("TIMER: $comment ".(((float)$usec + (float)$sec)-$_PL_timer[$timer])." sec\n");
    }
  } else {
    function startWatch($timer) {}
    function getWatch($comment, $timer) {}
  }
  
  function which_default($line)
  {
    global $default;
    if (!is_array($default)) {
      explain("WD: \$default not an array!",6);
      return(false);
    }
    reset($default);
    while(list($key,$val)=each($default)) {
      if ($val["line"]==$line) {
        explain("WD: default for key $key",6);
        return($key);
      }
      explain("WD: @key $key, {$val['line']}!=$line",6);
    }
    return(false);
  }
?>
