<?php

/**
 * `https://windows.php.net/download/`
 * unpack to `c:\php`
 * mv `php.ini-develop` to `php.ini`
 * uncomment `extension=curl`
 * uncomment `extension=zip`
 */

const PATH_ROOT = 'C:\Program Files (x86)\World of Warcraft\_retail_';
const URL_DOWNLOAD = 'https://mediafilez.forgecdn.net/files/';

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

define('SCRIPT_DIR', dirname($_SERVER['SCRIPT_FILENAME']));
define('VERSIONS_FILE', SCRIPT_DIR . '\versions.txt');

function backup() {
  if (defined('BACKUPED')) return;
  define('BACKUPED', 1);

  $file = PATH_ADDONS . "\..\bu-" . date('Y-m-d_H-i-s') . ".zip";
  echo "Backup to {$file}...\n";
  
  $zip = new ZipArchive();
  $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
  
  $files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(PATH_ADDONS),
    RecursiveIteratorIterator::LEAVES_ONLY
  );
  
  $index = 0;
  foreach ($files as $file) {
      if (!$file->isDir()) {
          $filePath = $file->getRealPath();
          $relativePath = substr($filePath, strlen(PATH_ADDONS) + 1);
          $zip->addFile($filePath, $relativePath);
          $zip->setCompressionIndex($index, ZipArchive::CM_STORE);
          $index++;
      }
  }

  $zip->close();
  echo "Backup finished.\n\n";
}

define('KEY', trim(file_get_contents(SCRIPT_DIR . '\key.txt')));

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

function get($url, $withKey = true) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  // curl_setopt($ch, CURLOPT_POST, 1);
  // curl_setopt($ch, CURLOPT_POSTFIELDS, $vars); // Post Fields
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

  if ($withKey) {
    $headers = [
        'User-Agent: Php wow addon updater 0.0.1',
        'Accept: application/json',
        'x-api-key: ' . KEY,
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  }

  $server_output = curl_exec($ch);
  
  if (!$server_output) {
    die('Curl error: <' . curl_error($ch) . "> on {$url}\n");
  }

  curl_close($ch);

  return $server_output;
}

$games = get(URL_GAMES);
$games = json_decode($games, true);

foreach ($games['data'] ?? [] as $game) {
  if (($game['name'] ?? '') == 'World of Warcraft') {
    define('GAME_ID_WOW', $game['id'] ?? null);
    
    break;
  }
}

if (!defined('GAME_ID_WOW')) {
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
  $query = http_build_query(['gameId' => GAME_ID_WOW, 'searchFilter' => $name, 'sortField' => 2, 'sortOrder' => 'desc']); // 2 - popular
  
  $mods = get(URL_SEARCH_MOD . $query);
  $mods = json_decode($mods, true);
  // var_dump($mods);
  
  // Полное совпадение имени
  foreach ($mods['data'] ?? [] as $mod) {
     if (($mod['name'] ?? '') == $name) 
      return $mod;
  }
  
  // Возвращаю первого
  foreach ($mods['data'] ?? [] as $mod) {
      return $mod;
  }

  return null;
}

// var_dump(getModId("Shadowed Unit Frames"));
// die;

function getDownload($file) {
  $download = $file['downloadUrl'] ?? "";
  if (!$download) {
    $download = URL_DOWNLOAD . substr($file['id'] ?? 'noid', 0, 4) . "/" . substr($file['id'] ?? '    nid', 4, 3) . "/" . ($file['fileName'] ?? '')
      . "?not-sure-url";
  }
  
  return $download;
}


function updateMod($url, $filename) {
  $fullfilename = PATH_ADDONS . "\\{$filename}";
  echo "  Downloading to {$fullfilename}...\n";

  $file = get($url, false);
  file_put_contents($fullfilename, $file);
  
  $zip = new ZipArchive;
  if ($zip->open($fullfilename) === true) {
      $zip->extractTo(PATH_ADDONS);
      $zip->close();
      echo "  Unzip complate\n";
  } else {
      echo "  Unzip ERROR\n";
  }
  
  unlink($fullfilename);
}

function freshLocalVersionFromDisk($addonNames) {
  if (!file_exists(VERSIONS_FILE)) {
    return $addonNames;
  }

  $versions = file_get_contents(VERSIONS_FILE);
  $versions = json_decode($versions, true);

  foreach ($addonNames as $name => $version) {
    $addonNames[$name] = $versions[$name] ?? $addonNames[$name];
  }
  
  return $addonNames;
}

$addonNames = freshLocalVersionFromDisk($addonNames);

$log = [];
$index = 0;
foreach ($addonNames as $name => $version) {
  echo "{$name} local:[$version]\n";

  $mod = getModId($name);
  
  echo "  remote_name: " . ($mod['name'] ?? 'Has no name') . "\n";
  
  $files = $mod['latestFiles'] ?? [];
  // var_dump($files);
  
  foreach ($files as $file) {
    $fileGameVersion = $file['gameVersions'] ?? [];
    if (!in_array($gameVersion, $fileGameVersion)) {
      // echo "  bad remote game version: " . json_encode($fileGameVersion) . "\n";
      continue;
    }
    // var_dump($file); echo "\n\n\n";

    $releaseType = $file['releaseType'] ?? 0;
    if ($releaseType <> 1) {
      // echo "  releaseType={$releaseType}\n";
      continue;
    }

    $fileVersion = $file["displayName"] ?? 'EMPTY_URL';    
    
    if ($fileVersion <> $version) {
      backup();
      
      $download = getDownload($file);
      echo "  remote:[$fileVersion] has update -> {$download}, updating...\n";
      $index++;
      $addonNames[$name] = $fileVersion;
      $log[count($log) + 1] = "Updated [$name]\tfrom [$version]\tto [$fileVersion]";
      updateMod($download, $file['fileName'] ?? ($index . '.zip'));
    }
    else {
      echo "  remote:[$fileVersion] has NO update\n";
    }
  } // files
  
  echo "\n\n";
} // addon names

file_put_contents(VERSIONS_FILE, json_encode($addonNames, JSON_PRETTY_PRINT));

// short log
foreach($log as $index => $line) {
  echo "$index. $line\n";
}
