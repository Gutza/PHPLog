<?php

  /**
  * Configuration parser class
  * Bogdan Stancescu <bogdan@moongate.ro>, November 2002
  * $Id: PL_Conf.php,v 1.9 2003/01/22 17:37:24 bogdan Exp $
  */
    /**
    *
    *  This is the configuration parser class.
    *
    */
  class PL_Conf
  {
    var $conf;
    var $verbs;
    var $config;

      /**
      * The constructor.
      * Sets up the various verbs, reading the extra reaction verbs
      * from $_PL_path/reactions.lst, if available
      */
    function PL_Conf()
    {
      $this->verbs["file"]=array(
        "from"
      );
      $this->verbs["global"]=array(
        "config", "group"
      );
      $this->verbs["action"]=array(
        "watchfor", "ignore", "default"
      );
      $this->verbs["reaction"]=array(
        "echo", "log", "mail", "beep", "exec"
      );

      global $_PL_path;
      if (is_file("$_PL_path/reactions.lst")) {
        $xtraReactions=file("$_PL_path/reactions.lst");
        for ($i=0;$i<count($xtraReactions);$i++) {
          $xtra=trim($xtraReactions[$i]);
          if ($xtra && (substr($xtra,0,1)!="#")) {
            $this->verbs["reaction"][]=$xtra;
            explain("Using extra reaction verb $xtra",3);
          }
        }
      }
    }

      /**
      * Actually parses a configuration file
      * @param string $file the file to parse
      */
    function parse($file)
    {
      explain("Starting to parse $file",1);
      $this->read($file);
      explain("$file preparsed ok",2);
      $this->brain();
      explain("Finished parsing $file",1);
    }

      /**
      * The brain performs extra interpretation of
      * the data read by {@link read}
      */
    function brain()
    {
      global $_PL_store;

      explain("Brain activated",5);
      $conf=&$this->conf;
      // Checking globals
      $globals=$conf[0];
      unset($conf[0]);
      while(list(,$global)=@each($globals)) {
        $verb=$global["verb"];
        $param=$global["param"];
        explain("Checking out global $verb => $param",5);
        $tmp=explode("=",$param);
        if (!preg_match("/^[a-z]{1}[a-z0-9]*/i",$tmp[0])) {
          $this->addError("Global key $tmp[0] is invalid. Valid global keys start with a letter and only contain strict alphanumeric characters.");
          exit;
        }
        $this->config[$verb][array_shift($tmp)]=implode("=",$tmp);
        // explain("Global $verb variable $tmp[0] set to $tmp[1]",4);
      }
      explain("All globals went through the brain",3);
      while(list($gid,$groupdata)=@each($this->config["group"])) {
        if (!strpos(" ".$groupdata,".")) {
          $this->addError("Group $gid malformed. The correct group format is [<userid>].[<groupid>]|[<permissions>]|[<path>]. There's no \".\" in your definition.");
          exit;
        }
        if (!strpos(" ".$groupdata,"|")) {
          $this->addError("Group $gid malformed. The correct group format is [<userid>].[<groupid>]|[<permissions>]|[<path>]. There's no \"|\" in your definition.");
          exit;
        }
        
        $own_perm_path=explode("|",$groupdata);
        $uid_gid=explode(".",$own_perm_path[0]);

        if ($own_perm_path[2]) {
          if (!is_dir($own_perm_path[2])) {
            $this->addError("Path $own_perm_path[2] specified in group $gid not found.");
            exit;
          }
          $fp=@fopen("$own_perm_path[2]/write_test","w+");
          if (!$fp) {
            $this->addError("Store directory $own_perm_path[2] for group $gid is not writable!");
            exit;
          }
          fclose($fp);
          unlink("$own_perm_path[2]/write_test");
        } else {
          $own_perm_path[2]=$_PL_store;
        }

        $myGroup=array(
          "owner"=>$uid_gid[0],
          "group"=>$uid_gid[1],
          "perm"=>$own_perm_path[1],
          "path"=>$own_perm_path[2]
        );
        $this->config["group"][$gid]=$myGroup;
      }
      // Checking actions for parameters
      for ($i=1;isset($conf[$i]);$i++) {
        explain("Brain checking for regex parameters in file ".$conf[$i]["file"],5);
        $actions=&$conf[$i]["actions"];
        reset($actions);
        while (list($aid,$action)=each($actions)) {
          if ($action["verb"]=="default") {
            $conf[$i]["default"]=$aid;
            $actions[$aid]["group"]=$action["regex"];
            continue;
          }
          if (substr($action["regex"],-1)!="/") {
            explain("Brain found either parameter or error for regexp #$aid ".$action["regex"],4);
            $paramPos=strrpos($action["regex"],"/");
            $regex=substr($action["regex"],0,$paramPos+1);
            $param=trim(substr($action["regex"],$paramPos+1));
            $actions[$aid]["regex"]=$regex;
            if (strpos(" ".$param,"|")) {
              $params=explode("|",$param);
              if ($params[0]) {
                $actions[$aid]["param"]=$param;
              }
              if ($params[1]) {
                if (!isset($this->config["group"][$params[1]])) {
                  $this->addError("Group $params[1] required by action {$action['verb']} {$action['regex']} in file {$conf[$i]['file']} not defined.");
                  exit;
                }
                $actions[$aid]["group"]=$params[1];
              }
            } else {
              $actions[$aid]["param"]=$param;
            }
            explain("Brain parsed $param for regexp $regex",4);
            $action=$actions[$aid];
          }
          if ((substr($action["regex"],-1)!="/") || (substr($action["regex"],0,1)!="/")) {
            $this->addError("Unable to parse regular expression/parameter combo for ".$action["regex"]);
            exit;
          }
          $regParam=$actions[$aid]["param"];
          if (strpos(" ".$regParam,"p")) {
            $actions[$aid]["func"]="preg_match";
            if (strpos(" ".$regParam,"i")) {
              $actions[$aid]["regex"].="i";
            }
          } else {
            $actions[$aid]["regex"]=substr($actions[$aid]["regex"],1,-1);
            if (strpos(" ".$regParam,"i")) {
              $actions[$aid]["func"]="eregi";
            } else {
              $actions[$aid]["func"]="ereg";
            }
          }
          explain("Brain parsed the following: function=".$actions[$aid]["func"]."(); regexp=\"".$actions[$aid]["regex"]."\"",5);
        }
        /*
        if (!isset($conf[$i]["default"])) {
          $conf[$i]["default"]=$aid+1;
        }
        */
      }
      explain("All regexes went through the brain (searched for parameters)",3);
      // Checking reactions
      for ($i=1;isset($conf[$i]);$i++) {
        explain("Checking file {$conf[$i]['file']} for reactions",5);
        for ($j=0;$j<count($conf[$i]["actions"]);$j++) {
          if (($conf[$i]['actions'][$j]['verb']=="watchfor") &&
          (!count($conf[$i]['actions'][$j]["reactions"]))) {
            $this->addError("Action #$j in file {$conf[$i]['file']} is a watchfor but has no reactions associated!\n[The pattern is /{$conf[$i]['actions'][$j]['regex']}/]");
            exit;
          }
        }
      }
      explain("All actions went through the brain (checked for reactions)",3);
      // Setting defaults
      if (!isset($this->config["config"]["ignore_duplicates"])) {
        $this->config["config"]["ignore_duplicates"]="on";
      }
      if (!isset($this->config["config"]["sleep"])) {
        $this->config["config"]["sleep"]=2;
      }
      // Various other stuff can be done here, such as checking
      // if ignore actions have reactions associated
      explain("Brain finished thinking",5);
    }

    function read($file)
    {
      if (!is_readable($file)) {
        $this->addError("Unable to read rules file $file! If this is your first install, please rename $file.example to $file\n");
        exit;
      }
      explain("File $file seems readable",4);
      $lines=file($file);
      explain("Read rule file $file - starting to parse",3);
      // Won't do (elegant) recursive parsing, because the
      // file format is pretty linear, so we can get away with
      // a simple loop
      $fileIdx=0;
      $actionIdx=-1;
      while(list($ln,$line)=each($lines)) {
        $ln++;
        explain("Starting to preparse line $ln: $line",5);
        $line=trim($line);
        if (($line=="") || (substr($line,0,1)=="#")) {
          continue;
        }
        explain("Preparsing line $ln ($line)",4);
        $verbEnd=strpos($line," ");
        if (!$verbEnd) {
          $verb=$line;
          $line="";
        } else {
          $verb=substr($line,0,$verbEnd);
          $line=substr($line,$verbEnd+1);
        }
        if (in_array($verb,$this->verbs["global"])) {
          $result=$this->addGlobal($verb, $line);
        } elseif (in_array($verb,$this->verbs["file"])) {
          if ($line==="") {
            $oldFileIdx=$fileIdx;
            $fileIdx="default";
          } elseif ($fileIdx==="default") {
            $fileIdx=$oldFileIdx+1;
          } else {
            $fileIdx++;
          }
          $actionIdx=-1;
          $result=$this->addFile($verb,$line,$fileIdx);
        } elseif (in_array($verb,$this->verbs["action"])) {
          $actionIdx++;
          $result=$this->addAction($verb,$line,$fileIdx,$actionIdx);
        } elseif (in_array($verb,$this->verbs["reaction"])) {
          $result=$this->addReaction($verb,$line,$fileIdx,$actionIdx);
        } else {
          $this->addError("Unknown verb: \"$verb\"");
          $result=false;
        }
        if (!$result) {
          $this->addError("The error(s) above occured while parsing file $file, ".
          "at line ".($ln+1).". Aborting.");
          exit;
        }
      }
    }

    function addGlobal($verb,$param)
    {
      $this->conf[0][]=array(
        "verb"=>$verb,
        "param"=>$param
      );
      explain("Added global $verb|$param",5);
      return(true);
    }

    function addFile($verb,$file,$fileIdx)
    {
      $this->conf[$fileIdx]=array(
        "verb"=>$verb,
        "file"=>$file
      );
      explain("Added file $verb|$file|file #$fileIdx",5);
      return(true);
    }

    function addAction($verb,$regex,$fileIdx,$actionIdx)
    {
      if (($fileIdx<1) && ($fileIdx!=="default")) {
        $this->addError("Action verb specified before file verb ($verb)");
        return(false);
      }
      $this->conf[$fileIdx]["actions"][$actionIdx]=array(
        "verb"=>$verb,
        "regex"=>$regex
      );
      explain("Added action $verb|$regex|file #$fileIdx|action #$actionIdx",5);
      return(true);
    }

    function addReaction($verb,$param,$fileIdx,$actionIdx)
    {
      if ($fileIdx<0) {
        $this->addError("Reaction verb specified before file verb ($verb)");
        return(false);
      }
      if ($actionIdx<0) {
        $this->addError("Reaction verb specified before action verb ($verb)");
        return(false);
      }
      $this->conf[$fileIdx]["actions"][$actionIdx]["reactions"][]=array(
        "verb"=>$verb,
        "param"=>$param
      );
      explain("Added reaction $verb|$param|file #$fileIdx|action #$actionIdx",5);
      return(true);
    }

    function addError($msg)
    {
      echo("ERROR: $msg\n");
    }
  }
?>
