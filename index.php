<?php

/**
 * `https://windows.php.net/download/`
 * unpack to `c:\php`
 * mv `php.ini-develop` to `php.ini`
 * uncomment `extension=curl`
 */

const PATH_ADDONS = 'C:\Program Files (x86)\World of Warcraft\_retail_\Interface\AddOns';

const URL_ROOT = 'https://api.curseforge.com';
const URL_GAMES = URL_ROOT . '/v1/games';
const URL_MOD = URL_ROOT . '/v1/mods/'; // + modId

const HEADER_TITLE = '## Title: ';
const HEADER_ID = '## X-Curse-Project-ID: ';

const SKIP_IF_HEADER = [
  // '## RequiredDeps: ',
  '## Dependencies: ',
  '## LoadOnDemand: 1',
];

const SKIP_IF_NAME = [
  'TInterface',
];

const SKIP_IF_NO_HEADER = [
  '## Version: ',
];

$KEY = file_get_contents('key.txt');
$GAME_ID_WOW = null;

function get($url) {
  global $KEY;
  
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  // curl_setopt($ch, CURLOPT_POST, 1);
  // curl_setopt($ch, CURLOPT_POSTFIELDS, $vars); // Post Fields
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

  $headers = [
      'User-Agent: Php wow addon updater 0.0.1',
      'Accept: application/json',
      'x-api-key: ' . $KEY,
  ];

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $server_output = curl_exec($ch);
  
  if (!$server_output) {
    die('Curl error: ' . curl_error($ch) . " on {$url}\n");
  }

  curl_close($ch);

  return $server_output;
}

$games = get(URL_GAMES);
$games = json_decode($games, true);
// echo json_encode($games, JSON_PRETTY_PRINT);

foreach ($games['data'] ?? [] as $game) {
  if (($game['name'] ?? '') == 'World of Warcraft') {
    $GAME_ID_WOW = $game['id'] ?? null;
    echo "GAME_ID_WOW = {$GAME_ID_WOW}\n";
    
    break;
  }
}

if (!$GAME_ID_WOW) {
  die("GAME_ID_WOW not found\n");
}

function getAddons() {
  $names = [];

  $folders = scandir(PATH_ADDONS);
  foreach ($folders as $folder) {
    // echo "{$folder}\n";
    $name = null;
  
    if (substr($folder, 0, 1) == '.') continue;
    $fullPath = PATH_ADDONS . "\\" . $folder;
    if (!is_dir($fullPath)) continue;
    
    // $skipFolder = false;

    $files = scandir($fullPath);
    foreach ($files as $file) {
      // echo "  {$file}\n";

      if (substr($file, 0, 1) == '.') continue;
      $fullPathFile = PATH_ADDONS . "\\" . $folder . "\\" . $file;
      if (is_dir($fullPathFile)) continue;
      
      $ext = pathinfo($fullPathFile, PATHINFO_EXTENSION);
      // echo "    {$ext}\n";
      
      if ($ext != "toc") continue;
      echo "  {$file}\n";
      
      $skipNoHeader = true;
      
      $lines = file($fullPathFile);
      foreach ($lines as $line) {
        if (strpos($line, HEADER_TITLE) !== false) {
          $name = trim(str_replace(HEADER_TITLE, '', $line));
          echo "    name={$name}\n";
        }
        
        foreach (SKIP_IF_HEADER as $badHeader) {
          if (strpos($line, $badHeader) !== false) {
            echo "      badHeader {$badHeader}\n";
            break 3;
          }
        }
        
        foreach (SKIP_IF_NO_HEADER as $goodHeader) {
          if (strpos($line, $goodHeader) !== false) {
            echo "      goodHeader {$goodHeader}\n";
            $skipNoHeader = false;
          }
        }
      } // lines
      
      
      if ($skipNoHeader) {
        echo "      skipNoHeader\n";
        $name = null;
        continue;
      }
      
      if ($name) {
        foreach (SKIP_IF_NAME as $badName) {
          if (strpos($name, $badName) !== false) {
            echo "      badName {$badName}\n";
            $name = null;
            break;
          }
        }

        if ($name) {
          $names[] = $name;
          break;
        }
      }
    } // files
  } // folders
  
  var_dump($names);
}

$AddonNames = getAddons();