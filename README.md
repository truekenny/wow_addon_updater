# World of Warcraft Addon Updater via PHP

![World of Warcraft Addon Updater via PHP](https://github.com/truekenny/wow_addon_updater/blob/master/title.png?raw=true)

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

## Example output

```
C:\Users\Kenny\Documents\Curse>c:\php\php index.php
PremadeGroupsFilter skip by DO_NOT_UPDATE

Macro Toolkit local:[v2.2.16]
  remote_name: Macro Toolkit
  remote:[v2.2.16] has NO update


Mythic Dungeon Tools local:[v202405220600]
  remote_name: Raider.IO Mythic Plus, Raid Progress, and Recruitment
Backup to C:\Program Files (x86)\World of Warcraft\_retail_\Interface\AddOns\..\bu-2024-05-23_23-17-52.zip...
Backup finished.

  remote:[v202405230600] has update -> https://edge.forgecdn.net/files/5366/169/RaiderIO-v202405230600.zip, updating...
  Downloading to C:\Program Files (x86)\World of Warcraft\_retail_\Interface\AddOns\RaiderIO-v202405230600.zip...
  Unzip complate


Pawn local:[2.9.3]
  remote_name: Pawn
  remote:[2.9.3] has NO update


Raider.IO Mythic Plus, Raiding, and Recruitment local:[v202405220600]
  remote_name: Raider.IO Mythic Plus, Raid Progress, and Recruitment
  remote:[v202405230600] has update -> https://edge.forgecdn.net/files/5366/169/RaiderIO-v202405230600.zip, updating...
  Downloading to C:\Program Files (x86)\World of Warcraft\_retail_\Interface\AddOns\RaiderIO-v202405230600.zip...
  Unzip complate


Scrap local:[10.2.11]
  remote_name: Scrap (Junk Seller)
  remote:[10.2.11] has NO update


Deadly Boss Mods local:[10.2.42]
  remote_name: Deadly Boss Mods (DBM)
  remote:[10.2.42] has NO update


1. Updated [Mythic Dungeon Tools]       from [v202405220600]    to [v202405230600]
2. Updated [Raider.IO Mythic Plus, Raiding, and Recruitment]    from [v202405220600]    to [v202405230600]

C:\Users\Kenny\Documents\Curse>pause
Для продолжения нажмите любую клавишу . . .
```
