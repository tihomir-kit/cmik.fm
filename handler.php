<?php
require 'lfm_fetch.php';
require 'lfm_db.php';

session_start();

$method = $_POST['method'];



// set session variables
if ($method == "SetSessionVars") {
	$_SESSION['username'] = $_POST['username'];
	$_SESSION['amount'] = $_POST['amount'];
	$_SESSION['safeamount'] = 0;
}



// fetch users XY top artists
// example usage: "handler.php?method=GetArtists"
if ($method == "GetArtists") {
	$lfm_fetch_obj = new LfmFetch();
	echo $lfm_fetch_obj->XmlArtists();
}



// fetch tracks and playcounts for an artist
// example usage: "handler.php?method=GetTracks&playcount=3972"
if ($method == "GetTracks") {
	$artist = urldecode($_POST['artist']);
	$artist = str_replace("cmikfmplusreplacer", "+", $artist);
	$artist = str_replace("cmikfmapostrophereplacer", "'", $artist);
	$artist_playcount = $_POST['playcount'];

	$lfm_fetch_obj = new LfmFetch();
	echo $lfm_fetch_obj->XmlTracks($artist, $artist_playcount);
}



// fetch users total playcount
if ($method == "GetTotalUserPlaycount") {
	$lfm_fetch_obj = new LfmFetch();
	echo $lfm_fetch_obj->GetTotalPlaycount();
}



// check artist amount
if ($method == "CheckSafeAmount") {
	echo $_SESSION['safeamount'];
}



// store user score into the database
if ($method == "StoreData") {
	$lfm_db_obj = new LfmDb();
	$lfm_db_obj->StoreData($_POST['avg_score']);
}



// fetch data for HoF tables from the database
if (($method == "GetTop") || ($method == "GetLowest")) {
	$lfm_db_obj = new LfmDb(); 
	echo $lfm_db_obj->PrepareTables($method);
}



// get users HoF score rank
if ($method == "GetUserHofRank") {
	$lfm_db_obj = new LfmDb();
	echo $lfm_db_obj->GetUserHofRank();
}
?>
