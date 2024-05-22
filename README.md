# Wow Addon Updater via PHP

## Install PHP (Windows)

- Download `https://windows.php.net/download/`
- Unpack to `c:\php`
- Rename `php.ini-develop` to `php.ini`
- Uncomment `extension=curl`
- Uncomment `extension=zip`

## Configurate

- Open `index.php`
- Change constant `PATH_ROOT` if need
- Create `key.txt`
- Add to this file a Api Key from https://console.curseforge.com/?#/signup

## Run

- Open `update.bat`

## Additions

### If addon not found

- Create `unfound.txt`
- Add addon's name per line

Example:

```
Deadly Boss Mods
DBM - Dungeons & Events (Requires Deadly Boss Mods)
```

### If you want skip update single addon

- Create empty file `DO_NOT_UPDATE` in addon's folder

Example: `C:\Program Files (x86)\World of Warcraft\_retail_\Interface\AddOns\PremadeGroupsFilter\DO_NOT_UPDATE`
