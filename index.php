<?php

/**
 * `https://windows.php.net/download/`
 * unpack to `c:\php`
 * mv `php.ini-develop` to `php.ini`
 * uncomment `extension=curl`
 */

const PATH_ROOT = 'C:\Program Files (x86)\World of Warcraft\_retail_';

const PATH_ADDONS = PATH_ROOT . '\Interface\AddOns';
const PATH_WTF = PATH_ROOT . '\WTF';

const URL_ROOT = 'https://api.curseforge.com';
const URL_GAMES = URL_ROOT . '/v1/games';
const URL_MOD = URL_ROOT . '/v1/mods/'; // + modId
const URL_SEARCH_MOD = URL_ROOT . '/v1/mods/search?'; // + query

const HEADER_TITLE = '## Title: ';
const HEADER_ID = '## X-Curse-Project-ID: ';
const HEADER_VERSION = '## Version: ';

const WTF_GAME_VERSION = 'SET lastAddonVersion ';

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

function getGameVersion() {
  $lines = file(PATH_WTF . '\Config.wtf');
  
  foreach ($lines as $line) {
    if (strpos($line, WTF_GAME_VERSION) !== false) {
      $version = trim(str_replace(WTF_GAME_VERSION, '', $line), " \n\r\t\v\x00\"");
      // echo "game version={$version}\n";
      
      return intval(substr($version, 0, 2)) . "." . intval(substr($version, 2, 2)) . "." . intval(substr($version, 4, 2));
    }
  }
  
  return null;
}

$gameVersion = getGameVersion();
if (!$gameVersion) {
  die("No game version found");
}

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
    // echo "GAME_ID_WOW = {$GAME_ID_WOW}\n";
    
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
  
    if (substr($folder, 0, 1) == '.') continue;
    $fullPath = PATH_ADDONS . "\\" . $folder;
    if (!is_dir($fullPath)) continue;
    
    // $skipFolder = false;

    $files = scandir($fullPath);
    foreach ($files as $file) {
      $name = null;
      $version = null;

      // echo "  {$file}\n";

      if (substr($file, 0, 1) == '.') continue;
      $fullPathFile = PATH_ADDONS . "\\" . $folder . "\\" . $file;
      if (is_dir($fullPathFile)) continue;
      
      $ext = pathinfo($fullPathFile, PATHINFO_EXTENSION);
      // echo "    {$ext}\n";
      
      if ($ext != "toc") continue;
      // echo "  {$file}\n";
      
      $skipNoHeader = true;
      
      $lines = file($fullPathFile);
      foreach ($lines as $line) {
        if (strpos($line, HEADER_TITLE) !== false) {
          $name = trim(str_replace(HEADER_TITLE, '', $line));
          // echo "    name={$name}\n";
        }
       
        if (strpos($line, HEADER_VERSION) !== false) {
          $version = trim(str_replace(HEADER_VERSION, '', $line));
          // echo "    version={$version}\n";
        }
         
        foreach (SKIP_IF_HEADER as $badHeader) {
          if (strpos($line, $badHeader) !== false) {
            // echo "      badHeader {$badHeader}\n";
            break 3;
          }
        }
        
        foreach (SKIP_IF_NO_HEADER as $goodHeader) {
          if (strpos($line, $goodHeader) !== false) {
            // echo "      goodHeader {$goodHeader}\n";
            $skipNoHeader = false;
          }
        }
      } // lines
      
      
      if ($skipNoHeader) {
        // echo "      skipNoHeader\n";
        continue;
      }
      
      if ($name && $version) {
        foreach (SKIP_IF_NAME as $badName) {
          if (strpos($name, $badName) !== false) {
            // echo "      badName {$badName}\n";
            $name = null;
            break;
          }
        }

        if ($name) {
          $names[$name] = $version;
          break;
        }
      }
    } // files
  } // folders
  
  return $names;
}

$addonNames = getAddons();

function getModId($name) {
  global $GAME_ID_WOW;
  $query = http_build_query(['gameId' => $GAME_ID_WOW, 'searchFilter' => $name, 'sortField' => 2, 'sortOrder' => 'desc']); // 2 - popular
  
  $mods = get(URL_SEARCH_MOD . $query);
  $mods = json_decode($mods, true);
  // var_dump(json_encode($mods, JSON_PRETTY_PRINT));
  
  foreach ($mods['data'] ?? [] as $mod) {
    if (($mod['name'] ?? '') == $name) 
      return $mod;
  }
  
  return null;
}

foreach ($addonNames as $name => $version) {
  echo "{$name} local:[$version] ";

  $mod = getModId($name);
  
  $files = $mod['latestFiles'] ?? [];
  // var_dump($files);
  
  foreach ($files as $file) {
    $fileGameVersion = $file['gameVersions'] ?? [];
    if (in_array($gameVersion, $fileGameVersion)) {
      // var_dump($file); echo "\n\n\n";
      
      $fileVersion = $file["displayName"] ?? '';
      
      echo "remote:[$fileVersion] ";
      
      if ($fileVersion <> $version) {
        $download = $file['downloadUrl'] ?? "";
        echo "has update -> {$download}";
      }
      else {
        echo "has NO update";
      }
      
      break;
    }
  }
  
  echo "\n";
  
  // echo $version . "\n";
  
  // break;
}