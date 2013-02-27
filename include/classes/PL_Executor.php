<?php

  // PHPLib Executor class
  // This one performs the default actions other than echoes
  // Bogdan Stancescu <bogdan@moongate.ro>, December 2002
  // $Id: PL_Executor.php,v 1.4 2002/12/19 18:59:53 bogdan Exp $

  class _PL_Exec
  {
    function parseParams($param)
    {
      if (!is_string($param)) {
        return(false);
      }
      $result=array();
      $mode="PARAM";
      $escaped=false;
      $starting=false;
      $thisParam=$thisVal="";
      for ($i=0;$i<strlen($param);$i++) {
        $char=$param[$i];
        if ($mode=="PARAM") {
          if ($char=="=") {
            $mode="VAL";
            $starting=true;
          } elseif ($char!=" ") {
            $thisParam.=$char;
          }
        } else {
          if ($char=='"') {
            if ($starting) {
              $starting=false;
            } elseif($escaped) {
              $thisVal.='"';
            } else {
              $mode="PARAM";
              $result[$thisParam]=$thisVal;
              $thisParam="";
              $thisVal="";
            }
          } elseif ($char=="\\") {
            $escaped=true;
          } else {
            $thisVal.=$char;
          }
        }
      }
      return($result);
    }

    function _react($reaction, $line, $conf)
    {
      $myParams=$this->parseParams($reaction["param"]);
      if ($myParams===false) {
        return(false);
      }
      global $parser_data;
      $parser_data=array(
        "eol" => "\n",
        "line" => $line["line"],
        "logfile" => $conf->conf[$line["fileId"]]["file"],
        "date" => date("r",$line["date"]),
        "formatted_date" => date($conf->conf["conf"]["date_format"],$line["date"]));
      switch($reaction["verb"]) {
        case "mail":
          if (!($email=$myParams["email"])) {
            $email=$conf->conf["config"]["config"]["mail"];
          }
          if (!$email) {
            $email="root@localhost";
          }
          if (!($subject=$myParams["subject"])) {
            $subject="PHPLog: Log file event";
          }
          $subject=parse_format($subject);
          if (!($content=$myParams["content"])) {
            $content="The following line was encountered by PHPLog in file <%logfile%> at <%date%>:<%eol%><%line%>";
          }
          $content=parse_format($content);
          explain("Sending mail to $email",3);
          return(mail($email,$subject,$content));
        case "exec":
          if (!($command=$myParams["command"])) {
            echo("Execute... what?! Add a command!\n");
            return(false);
          }
          exec(parse_format($command));
          return(true);
        case "beep":
          echo chr(7);
          return true;
        default:
          return(false);
      }
    }
  }
?>
