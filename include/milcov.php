<?php

  // The main Milcov Library
  // Bogdan Stancescu <bogdan@moongate.ro>, December 2002
  // $Id$

  /*

    Milcov stands for "Milcov Is a Library for Console Operation and Vizualization".
    Just kidding, actually it's a river in Romania.

    Type "man console_codes" in almost any *nix distro to see the codes and some docs.

  */
  // MUST use require_once() on this library
  // (or something else to the same effect)

  // If afraid you may end up including this library
  // several times, uncomment the code below
  // (minor performance hit):
  /*
  global $_MILCOV_library_loaded;
  if ($_MILCOV_library_loaded) {
    return(true);
  }
  $_MILCOV_library_loaded=true;
  */

  function MgetCode($op)
  {
    $esc=chr(27);
    switch($op) {
      // ECMA-48 CSI sequences
      // Missed: SM, RM, SGR, DSR
      case 'ICH'         : return($esc.'[%d@'); // Insert %d blank chars
      case 'CUU'         : return($esc.'[%dA'); // Move up %d rows
      case 'CUD'         : return($esc.'[%dB'); // Move down %d rows
      case 'CUF'         : return($esc.'[%dC'); // Move right %d cols
      case 'CUB'         : return($esc.'[%dD'); // Move left %d cols
      case 'CNL'         : return($esc.'[%dE'); // Move down %d rows, col 1
      case 'CPL'         : return($esc.'[%dF'); // Move up %d rows, col 1
      case 'CHA'         : return($esc.'[%dG'); // Move to col %d, this row
      case 'CUP'         : return($esc.'[%d,%dH'); // Move to row %d, col %d
      case 'EDF'         : return($esc.'[J'); // Del *From cursor* to end of display
      case 'EDT'         : return($esc.'[1J'); // Del from start *To cursor*
      case 'EDA'         : return($esc.'[2J'); // Del all display
      case 'ED'          : return($esc.'[%sJ'); // Any of the three above
      case 'ELF'         : return($esc.'[K'); // Del *From cursor* to end of line
      case 'ELT'         : return($esc.'[1K'); // Del from start *To cursor*
      case 'ELA'         : return($esc.'[2K'); // Del all line
      case 'EL'          : return($esc.'[%sK'); // Any of the three above
      case 'IL'          : return($esc.'[%dL'); // Insert %d lines
      case 'DL'          : return($esc.'[%dM'); // Delete %d lines
      case 'DCH'         : return($esc.'[%dP'); // Delete %d chars on line
      case 'ECH'         : return($esc.'[%dX'); //  Erase %d chars on line
      case 'HPR'         : return($esc.'[%da'); // Move right %d cols
      case 'DA'          : return($esc.'[c'); // You a VT102?
      case 'VPA'         : return($esc.'[%dd'); // Move to row %d, this col
      case 'VPR'         : return($esc.'[%de'); // Move down %d rows
      case 'HVP'         : return($esc.'[%d,%df'); // Move to row %d, col %d
      case 'TBC'         : return($esc.'[%sg'); // Clear tab stops - see doc
      case 'DECLL'       : return($esc.'[%dq'); // Set keyboard LEDs - see doc
      case 'DECSTBM'     : return($esc.'[%d,%dr'); // Set scrolling between row %d and %d
      case 'SVCL'        : return($esc.'[s'); // Save cursor location
      case 'LDCL'        : return($esc.'[u'); // Restore (load) cursor location
      case 'HPA'         : return($esc.'[%d`'); // Move to col %d, this row
      // ECMA-48 Set Graphics Rendition
      // Missed: 10, 11, 12, 21, 38, 39
      case 'DEFAULT'     : return($esc.'[0m');
      case 'BOLD'        : return($esc.'[1m');
      case 'HALF'        : return($esc.'[2m');
      case 'UNDERLINE'   : return($esc.'[4m');
      case 'BLINK'       : return($esc.'[5m');
      case 'REVERSE'     : return($esc.'[7m');
      case 'NORMAL'      : return($esc.'[22m');
      case 'NOUNDERLINE' : return($esc.'[24m');
      case 'NOBLINK'     : return($esc.'[25m');
      case 'NOREVERSE'   : return($esc.'[27m');
      case 'BLACK'       : return($esc.'[30m');
      case 'RED'         : return($esc.'[31m');
      case 'GREEN'       : return($esc.'[32m');
      case 'BROWN'       :
      case 'YELLOW'      : return($esc.'[33m');
      case 'BLUE'        : return($esc.'[34m');
      case 'MAGENTA'     : return($esc.'[35m');
      case 'CYAN'        : return($esc.'[36m');
      case 'WHITE'       : return($esc.'[37m');
      case 'DEFAULT_FGU' : return($esc.'[38m');
      case 'DEFAULT_FG'  : return($esc.'[39m');
      case 'ON_BLACK'    : return($esc.'[40m');
      case 'ON_RED'      : return($esc.'[41m');
      case 'ON_GREEN'    : return($esc.'[42m');
      case 'ON_BROWN'    :
      case 'ON_YELLOW'   : return($esc.'[43m');
      case 'ON_BLUE'     : return($esc.'[44m');
      case 'ON_MAGENTA'  : return($esc.'[45m');
      case 'ON_CYAN'     : return($esc.'[46m');
      case 'ON_WHITE'    : return($esc.'[47m');
      case 'DEFAULT_BG'  : return($esc.'[48m');
      default            : return(false);
    }
  }

  function _Mecho($msg, $style, $lf=false)
  {
    $style=strtoupper($style);
    $astyle=explode(";",$style);
    $styleSet=false;
    while (list(,$myStyle)=each($astyle)) {
      $myStyle=trim($myStyle);
      if ($code=MgetCode($myStyle)) {
        echo($code);
        $styleSet=true;
      }
    }
    echo($msg);
    if ($styleSet) {
      echo(mgetcode("DEFAULT"));
    }
    if ($lf) {
      echo("\n");
    }
  }

  function Mecho($msg, $style, $lf=false)
  {
    echo(_Mecho($msg, $style, $lf));
  }
?>
