<?php
//prerequis d'installation
// composer require norkunas/youtube-dl-php
// composer require mhor/php-mediainfo
// apt-get install mp4v2-utils
// apt-get install youtube-dl
// apt-get install ffmpeg
// apt-get install mediainfo
// mp4art --add "Les espions - C'est pas sorcier-uyRXwTe5ye4.jpg" "Les espions - C'est pas sorcier-uyRXwTe5ye4.mp4"
// youtube-dl --write-thumbnail https://www.youtube.com/watch?v=uyRXwTe5ye4
// youtube-dl  --verbose "https://www.youtube.com/watch?v=uyRXwTe5ye4" -o "Les espions - C'est pas sorcier.mp4"

require __DIR__ . '/vendor/autoload.php';

define(VIDEO_PATH,"/opt/CPS/video/");
define(THUMBNAIL_PATH,"/opt/CPS/thumbnaili/");

use YoutubeDl\YoutubeDl;
use YoutubeDl\Exception\CopyrightException;
use YoutubeDl\Exception\NotFoundException;
use YoutubeDl\Exception\PrivateVideoException;
use Mhor\MediaInfo\MediaInfo;


function get_youtube($url,$filename,$path)
{

$dl = new YoutubeDl([
    'continue' => true, // force resume of partially downloaded files. By default, youtube-dl will resume downloads if possible.
    'format' => 'bestvideo[ext=mp4]+bestaudio[ext=m4a]',
    'output' => $filename,
    'write-description' => True,
    'write-thumbnail' => True 
]);
// For more options go to https://github.com/rg3/youtube-dl#user-content-options

$dl->setDownloadPath($path); //'/home/user/downloads');
// Enable debugging
/*$dl->debug(function ($type, $buffer) {
    if (\Symfony\Component\Process\Process::ERR === $type) {
        echo 'ERR > ' . $buffer;
    } else {
        echo 'OUT > ' . $buffer;
    }
});*/
$error=0;
$status=new stdClass();
try {
    $video = $dl->download($url); //'https://www.youtube.com/watch?v=oDAw7vW7H0c');
    echo $video->getTitle(); // Will return Phonebloks
    $status->title=$video->getTitle();
    $status->filename=$filename; // \SplFileInfo instance of downloaded file
    $status->path=$path;
//    $status->description=$video->getDescription();
} catch (NotFoundException $e) {
    $error=1;// Video not found
} catch (PrivateVideoException $e) {
    $error=2;// Video is private
} catch (CopyrightException $e) {
    $error=3;// The YouTube account associated with this video has been terminated due to multiple third-party notifications of copyright infringement
} catch (\Exception $e) {
    $error=5;// Failed to download
}
$status->error=$error;
unset($dl);
return($status);
}

function extract_metadata($path,$filename)
{
$metadata=new stdClass();
$mediaInfo = new MediaInfo();
$mediaInfoContainer = $mediaInfo->getInfo($path.$filename);
$general = $mediaInfoContainer->getGeneral();
$matrice=$general->get();
var_dump($matrice);
var_dump($matrice["overall_bit_rate"]);
$metadata->codec_video=$general->get("codecs_video");
$metadata->codec_audio=$general->get("audio_codecs");
$metadata->file_extension=$general->get("file_extension");
$metadata->file_size=$matrice["file_size"]->getBit();
$metadata->file_duration=$matrice["duration"]->getMilliseconds();
$metadata->bitrate=$matrice["overall_bit_rate"]->getShortName();
$metadata->framerate=$matrice["frame_rate"]->getAbsoluteValue();
var_dump($general);
echo "-----------------------";
$videos = $mediaInfoContainer->getVideos();

foreach ($videos as $video) {
//    var_dump($video);
$video_info=$video->get();
$metadata->width=$video_info["width"]->getAbsoluteValue();
$metadata->height=$video_info["height"]->getAbsoluteValue();
}
echo "-----------------------";
var_dump($metadata);
unset($mediaInfo);
return($metadata);
/*
La chaine officielle de l'émission de France 3.

C'est pas sorcier, le magazine de la découverte et de la science.

*/
}

// Connect to the DB
$db = new SQLite3('cestpassorcier.sqlite');
$i=0;
//Download all the Youtube URL video;
$result = $db->query('SELECT "id","Nom_fichier","Nom_répertoire","URL_Youtube" FROM cestpassorcier');
while (($row = $result->fetchArray())||($i<2)) {
    if($row["URL_Youtube"]!="")
      {
      // URL OK, we try to download the A/V file from youtube
      echo "File to download: ".$row["Nom_fichier"]." : ".$row["Nom_répertoire"]." : ".$row["URL_Youtube"]."\n";
      $status=get_youtube($row["URL_Youtube"],$row["Nom_fichier"].".mp4",VIDEO_PATH.$row["Nom_répertoire"]."/");
      var_dump($status);
      $metadata_info=extract_metadata($status->path,$status->filename);
      var_dump($metadata_info);
     /*
      echo $row["id"]." : ".$newTheme." - ".$row["Theme"]."\n";
      $SQL="UPDATE \"cestpassorcier\" SET \"\"=\"".$newTheme."\" WHERE id=".$row["id"];
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
      */
      $i++;
      }
}

$status=get_youtube("https://www.youtube.com/watch?v=uyRXwTe5ye4","Les espions - C'est pas sorcier.mp4","/opt/CPS/test/");
var_dump($status);
$metadata_info=extract_metadata($status->path,$status->filename);

?>
