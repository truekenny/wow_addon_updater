<?php

const PATH_ROOT = 'C:\Program Files (x86)\World of Warcraft\_retail_';

if (!is_dir(PATH_ROOT)) {
  die("Path is not valid, " . PATH_ROOT . "\n");
}

const URL_DOWNLOAD = 'https://mediafilez.forgecdn.net/files/';

const PATH_ADDONS = PATH_ROOT . DIRECTORY_SEPARATOR . 'Interface' . DIRECTORY_SEPARATOR . 'AddOns';
const PATH_WTF = PATH_ROOT . DIRECTORY_SEPARATOR . 'WTF';

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

const FILE_DO_NOT_UPDATE = 'DO_NOT_UPDATE';

const REMOVE_FROM_NAME = [
  '/\|r/',
  '/\|c.{8}/',
];

const BACKUP_DIR = PATH_ADDONS . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR;

const REMOVE_OLD_BACKUPS = true;

const STORE_BACKUP_SECONDS = 5 * 24 * 3600; // 5 days

define('SCRIPT_DIR', dirname($_SERVER['SCRIPT_FILENAME']));
define('VERSIONS_FILE', SCRIPT_DIR . DIRECTORY_SEPARATOR . 'versions.txt');
$keyFilename = SCRIPT_DIR . DIRECTORY_SEPARATOR . 'key.txt';
if (!file_exists($keyFilename)) {
  die("File with curse api key not found, {$keyFilename}\n");
}
define('KEY', trim(file_get_contents($keyFilename)));
define('UNFOUND_ADDONS_FILE', SCRIPT_DIR . DIRECTORY_SEPARATOR . 'unfound.txt');

// Устанавливает GAME_VERSION
function getGameVersion() {
  $configFilename = PATH_WTF . DIRECTORY_SEPARATOR . 'Config.wtf';
  if (!file_exists($configFilename)) {
    die("File not found, {$configFilename}\n");
  }
  $lines = file($configFilename);
  
  foreach ($lines as $line) {
    if (strpos($line, WTF_GAME_VERSION) !== false) {
      $version = trim(str_replace(WTF_GAME_VERSION, '', $line), " \n\r\t\v\x00\"");
      // echo "game version={$version}\n";
      
      define('GAME_VERSION', intval(substr($version, 0, 2)) . "." . intval(substr($version, 2, 2)) . "." . intval(substr($version, 4, 2)));
      return ;
    }
  }
  
  die("No game version found");
}

getGameVersion();

// Выполняет HTTP запрос и возвращает содержимое
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

// Устанавливает GAME_ID_WOW
function getGameId() {
  $games = get(URL_GAMES);
  $games = json_decode($games, true);

  foreach ($games['data'] ?? [] as $game) {
    if (($game['name'] ?? '') == 'World of Warcraft') {
      define('GAME_ID_WOW', $game['id'] ?? null);
      
      return;
    }
  }
  
  die("GAME_ID_WOW not found\n");
}

getGameId();

// Возвращает имя-версия из каталога с аддонами
function getAddons() {
  $names = [];

  $folders = scandir(PATH_ADDONS);
  foreach ($folders as $folder) {
    // echo "{$folder}\n";
  
    if (substr($folder, 0, 1) == '.') continue;
    $fullPath = PATH_ADDONS . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($fullPath)) continue;
    
    if (file_exists($fullPath . DIRECTORY_SEPARATOR . FILE_DO_NOT_UPDATE)) {
      echo "$folder skip by DO_NOT_UPDATE\n\n";
      continue;
    }
    
    // $skipFolder = false;

    $files = scandir($fullPath);
    foreach ($files as $file) {
      $name = null;
      $version = null;

      // echo "  {$file}\n";

      if (substr($file, 0, 1) == '.') continue;
      $fullPathFile = PATH_ADDONS . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $file;
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
          
          foreach (REMOVE_FROM_NAME as $badString) {
            $name = preg_replace($badString, '', $name);
          }
          // echo "    name={$name}\n";
        }
       
        if (strpos($line, HEADER_VERSION) !== false) {
          $version = trim(str_replace(HEADER_VERSION, '', $line));
          // echo "    version={$version}\n";
        }
         
        foreach (SKIP_IF_HEADER as $badHeader) {
          if (strpos($line, $badHeader) !== false) {
            // echo "      badHeader {$badHeader}, $fullPathFile\n";
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

// Добавляет имена аддонов без установленной версии из файла UNFOUND_ADDONS_FILE
function addUnfoundAddons($addonNames) {
  if (!file_exists(UNFOUND_ADDONS_FILE)) { 
    return $addonNames;
  }
  
  $unfounds = file(UNFOUND_ADDONS_FILE);
  $unfounds = array_map('trim', $unfounds);
  
  foreach($unfounds as $unfound) {
    if (empty($unfound)) continue;
  
    $addonNames[$unfound] = 'no version';
  }
  
  return $addonNames;
}

$addonNames = addUnfoundAddons($addonNames);

// Обновляет версии аддонов в массиве взятых из предыдущих обновлений аддонов
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

// Возвращает информацию о моде по имени
function getMod($name) {
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

// Удаляет старые резервние копии
function removeOldBackups() {
  if (!REMOVE_OLD_BACKUPS) return;

  $zips = [];
  $files = scandir(BACKUP_DIR);
  foreach ($files as $file) {
    $fullPathFile = BACKUP_DIR . $file;
    if (!is_file($fullPathFile)) continue;
    
    $ext = pathinfo($fullPathFile, PATHINFO_EXTENSION);
    if ($ext <> 'zip') continue;
    
    $zips[] = $fullPathFile;
  }
  
  sort($zips);
  $zips = array_slice($zips, 0, count($zips) - 1 - 2); // anyway store last 2 backup

  foreach ($zips as $zip) {
    if (filectime($zip) > time() - STORE_BACKUP_SECONDS) continue;
    
    echo "Old backup removed: {$zip}\n";
    unlink($zip);
  }
}

removeOldBackups();

// Делает резервную версию перед обновлением аддона
function backup() {
  if (defined('BACKUPED')) return;
  define('BACKUPED', 1);

  $file = BACKUP_DIR . "bu-" . date('Y-m-d_H-i-s') . ".zip";
  echo "    Backup to {$file}...\n";
  
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
  echo "    Backup finished.\n";
}

// Скачивает файл по ссылке
function getDownload($file) {
  $download = $file['downloadUrl'] ?? "";
  if (!$download) {
    $download = URL_DOWNLOAD . substr($file['id'] ?? 'noid', 0, 4) . "/" . substr($file['id'] ?? '    nid', 4, 3) . "/" . ($file['fileName'] ?? '')
      . "?not-sure-url";
  }
  
  return $download;
}

// Обновление аддона из ссылки через временным файл
function updateMod($url, $filename) {
  $fullfilename = PATH_ADDONS . DIRECTORY_SEPARATOR . "{$filename}";
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

// Главный цикл перебора аддонов
function main($addonNames) {
  $log = [];
  $index = 0;
  foreach ($addonNames as $name => $version) {
    echo "{$name} local:[$version]\n";

    $mod = getMod($name);
    
    echo "  remote_name: " . ($mod['name'] ?? 'Has no name') . "\n";
    
    $files = $mod['latestFiles'] ?? [];
    // var_dump($files);
    
    foreach ($files as $file) {
      $fileGameVersion = $file['gameVersions'] ?? [];
      if (!in_array(GAME_VERSION, $fileGameVersion)) {
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
  
  return $log;
}

$log = main($addonNames);

// short log
foreach($log as $index => $line) {
  echo "$index. $line\n";
}
