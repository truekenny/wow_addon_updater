<?php

const PATH_ROOT = 'C:\Program Files (x86)\World of Warcraft\_retail_';

if (!is_dir(PATH_ROOT)) {
  die("Path is not valid, " . PATH_ROOT . "\n");
}

const URL_DOWNLOAD = 'https://mediafilez.forgecdn.net/files/';

const PATH_INTERFACE = PATH_ROOT . DIRECTORY_SEPARATOR . 'Interface';
const PATH_ADDONS = PATH_INTERFACE . DIRECTORY_SEPARATOR . 'AddOns';
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

const BACKUP_DIR = PATH_INTERFACE . DIRECTORY_SEPARATOR;

const REMOVE_OLD_BACKUPS = true;

const STORE_BACKUP_SECONDS = 5 * 24 * 3600; // 5 days

const VERSION_URL = 'https://raw.githubusercontent.com/truekenny/wow_addon_updater/master/version.txt';

const SOFT_URL = 'https://github.com/truekenny/wow_addon_updater';

const CURSE_SORT_FIELD_POPULAR = 2;

const MULTI_REQEUST_MAX = 10;

define('SCRIPT_DIR', dirname($_SERVER['SCRIPT_FILENAME']));
define('VERSIONS_FILE', SCRIPT_DIR . DIRECTORY_SEPARATOR . 'versions.txt');
$keyFilename = SCRIPT_DIR . DIRECTORY_SEPARATOR . 'key.txt';
if (!file_exists($keyFilename)) {
  die("File with curse api key not found, {$keyFilename}\n");
}
define('KEY', trim(file_get_contents($keyFilename)));
define('UNFOUND_ADDONS_FILE', SCRIPT_DIR . DIRECTORY_SEPARATOR . 'unfound.txt');

define('VERSION_FILE', SCRIPT_DIR . DIRECTORY_SEPARATOR . 'version.txt');

define('REQUEST_HEADERS', [
  'User-Agent: ' . SOFT_URL,
  'Accept: application/json',
  'x-api-key: ' . KEY,
]);

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

// Инициализация ресурса curl
function getCurl($url, $withKey = true) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

  if ($withKey) {
    $headers = REQUEST_HEADERS;

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  }

  return $ch;
}

// Выполняет HTTP запрос и возвращает содержимое
function get($url, $withKey = true) {
  $ch = getCurl($url, $withKey);
  $server_output = curl_exec($ch);
  
  if (!$server_output) {
    die('Curl error: <' . curl_error($ch) . "> on {$url}\n");
  }

  curl_close($ch);

  return $server_output;
}

// Уведомляет об обновлении
function checkVersion() {
  if (!file_exists(VERSION_FILE)) return;
  
  $localVersion = trim(file_get_contents(VERSION_FILE));
  $remoteVersion = trim(get(VERSION_URL, false));
  
  if ($localVersion <> $remoteVersion) {
    echo "Another version found, local: $localVersion, remote: $remoteVersion\n";
    echo SOFT_URL, "\n\n\n\n";
    sleep(1);
  }
}

checkVersion();

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

// Парсит результат поиска и возвращает мод с наиболее подходящим именем name
function getMod($mods, $name) {
  // var_dump($mods);
  
  // Полное совпадение имени
  foreach ($mods['data'] ?? [] as $mod) {
    // echo $mod['name'], " - full\n";
    if (($mod['name'] ?? '') == $name) 
      return $mod;
  }
  
  // В имени на сайте есть часть из локального имени
  foreach ($mods['data'] ?? [] as $mod) {
    // echo $mod['name'], " - part 1\n";
    if (strpos($mod['name'] ?? '', $name) !== false) 
      return $mod;
  }

  // В локальном имени есть часть из имени с сайта
  foreach ($mods['data'] ?? [] as $mod) {
    // echo $mod['name'], " - part 2\n";
    if (strpos($name, $mod['name'] ?? '') !== false) 
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

  $unlinked = 0;
  foreach ($zips as $zip) {
    if (filectime($zip) > time() - STORE_BACKUP_SECONDS) continue;
    
    echo "Old backup removed: {$zip}\n";
    unlink($zip);
    $unlinked++;
  }
  
  if ($unlinked) echo "\n";
}

removeOldBackups();

// Делает резервную версию перед обновлением аддона
function backup() {
  if (defined('BACKUPED')) return;
  define('BACKUPED', 1);

  $file = BACKUP_DIR . "bu-" . date('Y-m-d_H-i-s') . ".zip";
  echo "      backup to {$file}...\n";
  
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
  echo "      backup finished.\n";
}

// Делает ссылку на аддон
function getDownloadUrl($file) {
  $download = $file['downloadUrl'] ?? "";
  if (!$download) {
    $download = URL_DOWNLOAD 
      . intval(substr($file['id'] ?? 'noid', 0, 4)) . "/" 
      . intval(substr($file['id'] ?? '    nid', 4, 3)) . "/" 
      . ($file['fileName'] ?? '')
      . "?not-sure-url";
  }
  
  return $download;
}

// Обновление аддона из ссылки через временным файл
function updateMod($url, $filename) {
  $fullfilename = PATH_ADDONS . DIRECTORY_SEPARATOR . "{$filename}";
  echo "      downloading to {$fullfilename}...\n";

  $file = get($url, false);
  file_put_contents($fullfilename, $file);
  
  $zip = new ZipArchive;
  if ($zip->open($fullfilename) === true) {
      $zip->extractTo(PATH_ADDONS);
      $zip->close();
      echo "      unzip complate\n";
  } else {
      echo "      unzip ERROR\n";
  }
  
  unlink($fullfilename);
}

// Выполняет одновременные запросы к api и возвращает массив результата
function getMods($allNames) {
  $result = [];

  echo "Requests processing...";
  $lastPrint = time();

  foreach(array_chunk($allNames, MULTI_REQEUST_MAX) as $names) {
    $chs = [];
    $mh = curl_multi_init();
    
    // init
    foreach ($names as $name) {
      $query = http_build_query([
        'gameId' => GAME_ID_WOW,
        'searchFilter' => $name,
        'sortField' => CURSE_SORT_FIELD_POPULAR,
        'sortOrder' => 'desc']);
      $url = URL_SEARCH_MOD . $query;
      $ch = getCurl($url, true);

      $chs[$name] = $ch;
      curl_multi_add_handle($mh, $ch);
    }
    
    // exec
    do {
      $status = curl_multi_exec($mh, $active);
      // Ставлю точку не чаще 1 раза в секунду
      if ($lastPrint <> time()) {
        echo ".";
        $lastPrint = time();
      }
      if ($active) {
          curl_multi_select($mh);
      }
    } while ($active && $status == CURLM_OK);
    
    // parse data
    foreach ($chs as $name => $ch) {
      $response = curl_multi_getcontent($ch);
      $result[$name] = getMod(json_decode($response, true), $name);
      curl_multi_remove_handle($mh, $ch);
    }
    curl_multi_close($mh);
  }

  echo "\n\n";
  
  return $result;
}

// Главный цикл перебора аддонов
function main($addonNames) {
  $log = [];
  $index = 0;
  
  // multi request
  $mods = getMods(array_keys($addonNames));
  
  foreach ($addonNames as $name => $version) {
    echo "{$name} local: [$version]\n";

    $mod = $mods[$name];
    
    $modId = $mod['id'] ?? 'no';
    echo "  remote_name: " . ($mod['name'] ?? 'Has no name') . " mod_id: $modId\n";
    
    $files = $mod['latestFiles'] ?? [];
    // var_dump($files);
    
    $toUpdate = ['file_id' => null, 'file' => null];
    foreach ($files as $file) {
      echo "  file_id: $file[id]\n";
    
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
   
      $fileId = $file['id'] ?? 'EMPTY_ID';
      
      // Забирается последний file_id
      if (!$toUpdate['file_id'] || ($toUpdate['file_id'] < $fileId)) {
        $toUpdate = ['file_id' => $fileId, 'file' => $file];
        
        $fileVersion = $file["displayName"] ?? 'EMPTY_VERSION'; 
        echo "    selected version: {$fileVersion}\n";
      }
    } // files
    
    if ($toUpdate['file_id']) {
      $file = $toUpdate['file'];
      $fileVersion = $file["displayName"] ?? 'EMPTY_VERSION';
      
      if ($fileVersion <> $version) {
        $download = getDownloadUrl($file);
        echo "    remote: [$fileVersion] has update -> {$download}, updating...\n";

        backup();

        $index++;
        $addonNames[$name] = $fileVersion;
        $log[count($log) + 1] = "Updated [$name]\tfrom [$version]\tto [$fileVersion]";
        updateMod($download, $file['fileName'] ?? ($index . '.zip'));
      }
      else {
        echo "    remote: [$fileVersion] has NO update\n";
      }
    }
    else {
      echo "    remote: [empty] has NO update\n";
    }
    
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
