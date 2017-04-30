<?php
define(VIDEO_PATH,"/opt/CPS/video/");

/**
 * @param string $filename
 * @return boolean Whether the string is a valid Windows filename.
 */
function isValidWindowsFilename($filename) {
    $regex = <<<'EOREGEX'
~                               # start of regular expression
^                               # Anchor to start of string.
(?!                             # Assert filename is not: CON, PRN, AUX, NUL, COM1, COM2, COM3, COM4, COM5, COM6, COM7, COM8, COM9, LPT1, LPT2, LPT3, LPT4, LPT5, LPT6, LPT7, LPT8, and LPT9.
    (CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])
    (\.[^.]*)?                  # followed by optional extension
    $                           # and end of string
)                               # End negative lookahead assertion.
[^<>:"/\\|?*\x00-\x1F]*         # Zero or more valid filename chars.
[^<>:"/\\|?*\x00-\x1F\ .]       # Last char is not a space or dot.
$                               # Anchor to end of string.
                                #
                                # tilde = end of regular expression.
                                # i = pattern modifier PCRE_CASELESS. Make the match case insensitive.
                                # x = pattern modifier PCRE_EXTENDED. Allows these comments inside the regex.
                                # D = pattern modifier PCRE_DOLLAR_ENDONLY. A dollar should not match a newline if it is the final character.
~ixD
EOREGEX;
$status=0;

    $status=preg_match($regex, $filename) === 1;
if($status=="")
{$status=0;
}
return $status;
}

function convert_to_filename ($string) {
 
//	$string = strtolower($string);
 
	$string = str_replace ("ø", "oe", $string);
	$string = str_replace ("å", "aa", $string);
	$string = str_replace ("æ", "ae", $string);
        $string = str_replace ("…", "...", $string);
	$string = str_replace (":", "", $string);
	$string = str_replace ("..", ".", $string);
        $string = str_replace ("'", " ", $string);
        $string = str_replace ("?", "", $string); 
        $string = str_replace ("!", "", $string);
	$string = str_replace ("*", "", $string);
	$string = str_replace ("|", "", $string);
	$string = str_replace ("\\", "", $string);
        $string = str_replace ("/", "", $string);
        $string = str_replace ("\"", "", $string);
	preg_replace ("/[^0-9^a-z^_^.]/", "", $string);
	$string=trim($string);
	return $string;
}


function sanitizeFilename($f) {
 // a combination of various methods
 // we don't want to convert html entities, or do any url encoding
 // we want to retain the "essence" of the original file name, if possible
 // char replace table found at:
 // http://www.php.net/manual/en/function.strtr.php#98669
 $replace_chars = array(
     'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
     'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
     'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
     'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
     'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
     'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
     'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f', '.'=>''
 );
 $f = strtr($f, $replace_chars);
 // convert & to "and", @ to "at", and # to "number"
 $f = preg_replace(array('/[\&]/', '/[\@]/', '/[\#]/'), array('-and-', '-at-', '-number-'), $f);
 $f = preg_replace('/[^(\x20-\x7F)]*/','', $f); // removes any special chars we missed
 $f = str_replace(' ', '_ ', $f); // convert space to hyphen 
 $f = str_replace('\'', '_', $f); // removes apostrophes
 $f = preg_replace('/[^\w\-\.]+/', '', $f); // remove non-word chars (leaving hyphens and periods)
 $f = preg_replace('/[\-]+/', '-', $f); // converts groups of hyphens into one
 return strtolower($f);
}


$db = new SQLite3('cestpassorcier.sqlite');

// Check filename, do a sanity check and update the db
$results = $db->query('SELECT "id","Titre" FROM cestpassorcier');
while ($row = $results->fetchArray()) {
    //echo "\n".$row["Titre"]."\n";
    //$newfilename=sanitizeFilename($row["Titre"]);
    $newfilename=$row["Titre"];
    $newfilename=convert_to_filename($newfilename);

    $validity = isValidWindowsFilename($newfilename);
    
    echo $row["id"]." : ".$newfilename." - ".$row["Titre"]."\n";
    $SQL="UPDATE \"cestpassorcier\" SET \"Nom_fichier\"=\"".$newfilename."\" WHERE id=".$row["id"];
    echo $SQL."\n";
    $SQL=SQLite3::escapeString($SQL);
    $results2 = $db->query($SQL);
}

//check theme name, do a sanity check and update the db
$results_theme = $db->query('SELECT "id","Theme" FROM cestpassorcier');
while ($row = $results_theme->fetchArray()) {
    //echo "\n".$row["Theme"]."\n";
    //$newfilename=sanitizeFilename($row["Theme"]);
    $newTheme=$row["Theme"];
    $newTheme=convert_to_filename($newTheme);

    $validity = isValidWindowsFilename($newTheme);

    echo $row["id"]." : ".$newTheme." - ".$row["Theme"]."\n";
    $SQL="UPDATE \"cestpassorcier\" SET \"Nom_répertoire\"=\"".$newTheme."\" WHERE id=".$row["id"];
    echo $SQL."\n";
    $SQL=SQLite3::escapeString($SQL);
    $results3 = $db->query($SQL);
    $directory=VIDEO_PATH.$newTheme;
    if(file_exists($directory))
      {
      //nothing to do
      }
    else
      {
      //creat directory
      echo "creation du repertoire : ".$directory."\n";
      mkdir($directory);
      }
}

?>
