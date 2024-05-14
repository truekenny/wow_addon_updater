<?php

const KEY = '$2a$10$vZTkiGHNXmUtmdR8ClTHZO4R/TTioh12ZjbwAukH3mn8i.pOLHeZO';

const URL_GAMES = 'https://api.curseforge.com/v1/games';

const GAME_ID_WOW = 1;

function get($url) {
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
      'x-api-key: ' . KEY,
  ];

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $server_output = curl_exec($ch);
  
  if (!$server_output) {
    echo 'Curl error: ' . curl_error($ch) . " on {$url}\n";
    
    die;
  }

  curl_close($ch);

  return $server_output;
}

//$games = get(URL_GAMES);
//$games = json_decode($games);
//echo json_encode($games, JSON_PRETTY_PRINT);
