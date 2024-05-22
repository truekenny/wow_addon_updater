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

## Example output

```
>c:\php\php index.php
PremadeGroupsFilter skip by DO_NOT_UPDATE

Auctionator local:[10.2.41]
  remote_name: Auctionator
  remote:[10.2.41] has NO update


BlizzMove local:[v3.5.29]
  remote_name: BlizzMove
  remote:[v3.5.29] has NO update


CursorTrail local:[CursorTrail_10.2.7.1.zip]
  remote_name: CursorTrail
  remote:[CursorTrail_10.2.7.1.zip] has NO update


Details! Damage Meter local:[Details.20240519.12755.156]
  remote_name: Details! Damage Meter
  remote:[Details.20240519.12755.156] has NO update


HealBot local:[24.05.20]
  remote_name: HealBot Continued
  remote:[24.05.20] has NO update


Health Alarm local:[HealthAlarm-2.9.zip]
  remote_name: Health Alarm
  remote:[HealthAlarm-2.9.zip] has NO update


Macro Toolkit local:[v2.2.16]
  remote_name: Macro Toolkit
  remote:[v2.2.16] has NO update


Mythic Dungeon Tools local:[v202405210600]
  remote_name: Raider.IO Mythic Plus, Raid Progress, and Recruitment
  remote:[v202405210600] has NO update


Pawn local:[2.9.3]
  remote_name: Pawn
  remote:[2.9.3] has NO update


Plater local:[Plater-v579]
  remote_name: Plater Nameplates
  remote:[Plater-v579] has NO update


Raider.IO Mythic Plus, Raiding, and Recruitment local:[v202405210600]
  remote_name: Raider.IO Mythic Plus, Raid Progress, and Recruitment
  remote:[v202405210600] has NO update


Scrap local:[10.2.11]
  remote_name: Scrap (Junk Seller)
  remote:[10.2.11] has NO update


Shadowed Unit Frames local:[v4.4.8]
  remote_name: Shadowed Unit Frames


TellMeWhen local:[10.2.6]
  remote_name: TellMeWhen
  remote:[10.2.6] has NO update


TinyPad local:[TinyPad_3_1_16.zip]
  remote_name: TinyPad
  remote:[TinyPad_3_1_16.zip] has NO update


TomTom local:[v3.6.2-release]
  remote_name: TomTom
  remote:[v3.6.2-release] has NO update


Deadly Boss Mods local:[10.2.42]
  remote_name: Deadly Boss Mods (DBM)
  remote:[10.2.42] has NO update


DBM - Dungeons & Events (Requires Deadly Boss Mods) local:[DBM-Dungeons-r129]
  remote_name: DBM - Dungeons & Events (Requires Deadly Boss Mods)
  remote:[DBM-Dungeons-r129] has NO update



>pause

```
