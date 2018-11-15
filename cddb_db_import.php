#!/usr/bin/env php
<?php
/**
 * cddb_db_import.php, 0.1
 * 
 * Script to import CDDB entries in sqlite db.
 * 
 * 
 * Portion of "extravex" codebase.
 * 
 * @version 0.1
 * @copyright Copyright (c) 2018 Leonardo Laureti
 * @license MIT License
 * 
 * 
 * 
 * 
 * 
 * Import sql schema to create a new sqlite db:
 * 
 * ~$ sqlite3
 * 
 * 		> .open cddb_db.sqlite
 * 		> .read cddb_db_schema.sql  
 * 		> .quit
 * 
 * 
 * Run the script:
 * 
 * ~$ php cddb_db_import.php
 * 
 * 
 * Some utilization examples:
 * 
 * 	SELECT * FROM "TRACKS" LEFT JOIN "ALBUMS" ON TRACKS.ID=ALBUMS.ID WHERE DT1 LIKE "%bob%" LIMIT 100;
 * 	SELECT * FROM "TRACKS" WHERE TT0 LIKE "%dog%" LIMIT 100;
 * 	SELECT * FROM "ALBUMS" WHERE DT1 LIKE "%puppy%" LIMIT 100;
 */

error_reporting('E_ALL');

@ini_set('memory_limit', -1);
@ini_set('max_execution_time', 0);




// Directory path of CDDB tarball extract
define('CDDB_BASEPATH', './freedb-update-yyyymmdd-yyyymmdd');

// File path of db
define('SQLITE_PATH', './cddb_db.sqlite');







// Skeleton for album insert
define('INS_ALBUM', "INSERT OR REPLACE INTO \"ALBUMS\" VALUES (%s);\n");

// Skeleton for album tracks query
define('INS_ALBUM_TRACKS', "%s");

// Skeleton for album track insert
define('INS_TRACK', "INSERT OR REPLACE INTO \"TRACKS\" VALUES (%s);\n");


// Maxiumum length of title extended field
define('EXT_TITLE_MAX_LENGTH', 256);


// Frames per one second
define('OFFSET_FPS', 75);

// Ratio to extimate better track duration
define('OFFSET_GAP', 1.0030);

// Function to round duration length digits (default: intval | round, ceil, floor)
define('DURATION_ROUND', 'intval');



$GLOBALS['db'] = NULL;

/**
 * Connect to db
 */
function db_connect() {
	try {
		print("Connecting to DB\n\n");
		$sqlite_path = realpath(SQLITE_PATH);
		$GLOBALS['db'] = new SQLite3($sqlite_path);
		$GLOBALS['db']->enableExceptions(true);
	} catch (Exception $e) {
		die(sprintf("ERR DB %s\n", $e->getMessage()));
	}
}

/**
 * Disconnect from db
 */
function db_disconnect() {
	print("Disconnecting from DB\n\n");
	$GLOBALS['db']->close();
}

/**
 * Perform db transaction
 *
 * @param string $transation
 */
function db_transaction($transaction) {
	print("\nBegin transaction\n");
	$GLOBALS['db']->exec('BEGIN TRANSACTION;');
	$GLOBALS['db']->exec($transaction);
	$GLOBALS['db']->exec('END TRANSACTION;');
	print("\nEnd transaction\n");
}

/**
 * Clean encode text string
 *
 * @param string $text
 * @return string
 */
function enc_func($text) {
	$text = str_replace(array('\n', "\n", '\r', "\r", '\t', "\t"), ' ', $text);
	$value = htmlentities($text, ENT_QUOTES, 'UTF-8');
	if (! $value) {
		$value = utf8_encode($text);
		$value = htmlentities($value, ENT_QUOTES, 'UTF-8');
	}
	$value = preg_replace('/[\s]{2}+/', ' ', $value);

	return trim($value);
}

/**
 * Escape string in double quotes
 *
 * @param string $value
 * @return string
 */
function qas_func($value) {
	if (is_string($value))
		return '"' . $value . '"';

	return $value;
}

/**
 * Convert time from seconds to (hh:)mm:ss
 *
 * @param string $seconds
 * @param string|boolean $sep ':'
 * @return string|array
 */
function hms_func($seconds, $sep = ':') {
	$format = '';

	$hh = intval($seconds / 60 / 60);
	$mm = intval($seconds / 60 % 60);
	$ss = intval($seconds % 60);

	if ($sep && strlen($sep) === 1)
		$format = ($hh ? '%2$02d%1$s' : '') . '%3$02d%1$s%4$02d';

	if ($format)
		return sprintf($format, $sep, $hh, $mm, $ss);

	return array($hh, $mm, $ss);
}



if (
	! (defined('SQLITE_PATH') && file_exists(SQLITE_PATH)) ||
	! (defined('CDDB_BASEPATH') && file_exists(CDDB_BASEPATH))
)
	die("ERR\n");


db_connect();


$cddb_db_path = @realpath(CDDB_BASEPATH);

printf("CDDB: %s\n\n", $cddb_db_path);


$dir = @opendir($cddb_db_path);


while (($idir = @readdir($dir)) !== false) {
	if (strpos($idir, '.') === 0)
		continue;


	$indir = @opendir($cddb_db_path . '/' . $idir);

	$ix = 0;

	printf("\nEntering category: %s\n\n", $idir);

	$transaction = '';


	$dirs = @stat($cddb_db_path . '/' . $idir);
	$in = $dirs[3] - 1;


	while (($file = @readdir($indir)) !== false) {
		if (! is_file($cddb_db_path . '/' . $idir . '/' . $file))
			continue;

		$fileh = @fopen($cddb_db_path . '/' . $idir . '/' . $file, 'r');

		$id = $file;
		$sepocc = 0;

		printf("Parsing entry: %s\n", $file);

		/**
		 * Each $_album index to entry field or 'album' mutation
		 *
		 *	[
		 *		ID	<=>	DISCID (file, singular)
		 *		DI	<=>	DISCID (entry, maybe plural)
		 *		DS	<=>	category
		 *		DT0	<=>	DTITLE  
		 *		DT1	<=>	DTITLE
		 *		DY	<=>	DYEAR
		 *		DG	<=>	DGENRE
		 *		DN	<=>	num. tracks
		 *		DD	<=>	disc duration
		 *		DR	<=>	entry revision
		 *	]
		 */
		$_album = array('', '', '', '', '', '', '', '', '');
		$_tracks = array();
		$_offsets = array();

		$_album_length = 0;
		$_track_ext_title = true;

		while (($buffer = fgets($fileh)) !== false) {

			/* Skip some empty unwanted fields */
			if ($buffer[0] == '#') {
				if (! isset($buffer[2]))
					continue;

				if (! $_album_length || $buffer[2] == 'D' || $buffer[2] == 'R')
					$_nums = (int) preg_replace('/[^\d]+/', '', $buffer);

				if ($buffer[2] == 'D')
					$_album_length = $_nums;
				else if ($buffer[2] == 'R')
					$_album[9] = $_nums;
				else if (! $_album_length && $_nums)
					$_offsets[] = $_nums;

				continue;
			}

			$line = preg_split('/=/', $buffer);

			if ($line[0] == 'PLAYORDER')
				break;

			$line[1] = call_user_func_array(
				'enc_func',
				array($line[1])
			);

			if ($buffer[0] == 'D') {
				if ($line[0] == 'DISCID') {
					$_album[0] = $id;
					$_album[1] = $line[1];
					$_album[2] = $idir;
				}

				if ($line[0] == 'DTITLE') {
					$_album[3] .= $line[1];
				}

				if ($line[0] == 'DYEAR') {
					$_album[5] = (int) $line[1];
				}

				if ($line[0] == 'DGENRE') {
					$_album[6] = $line[1];
				}

				continue;
			}

			if (($line[0][0] . $line[0][1]) == 'TT') {
				$tti = str_replace('TTITLE', '', $line[0]);
				$ttn = intval($tti) + 1;

				if (! isset($_tracks[$tti]))
					$_tracks[$tti] = array($id, $ttn, '', '');

				$_tracks[$tti][2] .= $line[1];

				/* Calculate separator occurrencies */
				$sepocc += preg_match_all('/\s[-\/]\s/', $line[1]);

				continue;
			}

			if (($line[0][0] . $line[0][3]) == 'ET') {
				if (! $_track_ext_title)
					continue;

				$tti = str_replace('EXTT', '', $line[0]);

				$_tracks[$tti][3] .= $line[1];

				/* Has reach max limit, stop capturing */
				if (strlen($_tracks[$tti][3]) > EXT_TITLE_MAX_LENGTH)
					$_track_ext_title = false;

				continue;
			}
		}

		/* Split album title */
		if (strpos($_album[3], ' / ')) {
			$_album[3] = explode(' / ', $_album[3]);
			
			$_album[4] = $_album[3][0];
			$_album[3] = $_album[3][1];
		}

		/* Calculate last offset from entry disc length */
		$_offsets[] = call_user_func_array(
			DURATION_ROUND,
			array($_album_length * OFFSET_FPS * OFFSET_GAP)
		);


		$sep = '';
		$_album_tracks = '';
		$_album_tracks_tot = count($_tracks) - 1;
		$_album_duration = 0;



		/**
		 * Each $_track index to entry field or 'track' mutation
		 *
		 *	[
		 *		ID	<=>	DISCID (file, singular)
		 *		TN	<=>	track num.
		 *		TT0	<=>	TTITLE#
		 *		TT1	<=>	EXTT# | TTITLE#
		 *		TD	<=>	track duration
		 *	]
		 */
		foreach ($_tracks as $i => $_track) {
			$title = array('', '', '');

			/* Found ' / ' as separator, could be improved */
			if ((! $sep || $sep == '/') && strpos($_track[2], ' / ')) {
				$sep = '/';
				$title[2] = explode(' / ', $_track[2]);
				$title[3] = count($title[2]) - 1;
				$title[1] = $title[2][$title[3]];
				unset($title[2][$title[3]]);

			/* Found ' - ' as separator, could be improved */
			} elseif ((! $sep || $sep == '-') && strpos($_track[2], ' - ')) {
				$sep = '-';
				$title[2] = explode(' - ', $_track[2]);
				$title[1] = $title[2][0];
				unset($title[2][0]);

			/* No separator */
			} else {
				$title[1] = $_track[2];
			}

			if ($sep && $title[2]) {
				$title[0] = array();

				/* Trying to get infos from track title */
				foreach ($title[2] as $t) {
					$t = preg_split('/\s(\+|ft\.|feat\.|featuring|with|vs\.|vs)\s/i', $t);
					$t = array_map('trim', $t);
					$title[0] = array_merge($title[0], $t);
				}

				/* Have not enough tracks info, revert back title */
				if (count($title[0]) && $sepocc < ($tti / 3)) {
					$title[0] = implode((' ' . $sep . ' '), $title[0]);
					$title[1] .= ($title[1] ? ' ' . $sep . ' ' . $title[0] : $title[0]);
					$title[0] = '';
				} else {
					$title[0] = implode(',', $title[0]);
				}
			}

			$_track[2] = $title[1];

			if (! $_track_ext_title)
				$_track[3] = '';

			if ($title[0] && ! $_track[3])
				$_track[3] = $title[0];

			$_track_length = $_offsets[($i + 1)] - $_offsets[$i];
			$_track_length /= OFFSET_GAP;
			$_album_duration += $_track_length;
			$_track_length /= OFFSET_FPS;

			$_track[4] = intval($_track_length);

			$_track = array_map('qas_func', $_track);
			$_track = implode(',', $_track);

			$_album_tracks .= sprintf(INS_TRACK, $_track);
		}

		$_album_duration /= OFFSET_FPS;

		$_album[7] = $_album_tracks_tot;
		$_album[8] = call_user_func_array(
			DURATION_ROUND,
			array($_album_duration)
		);

		$_album = array_map('qas_func', $_album);
		$_album = implode(',', $_album);


		$transaction .= sprintf(INS_ALBUM, $_album);
		$transaction .= sprintf(INS_ALBUM_TRACKS, $_album_tracks);


		if ($ix++ > 999) {
			$ix = 0;

			db_transaction($transaction);
			$transaction = '';
		}
	}


	if ($transaction)
		db_transaction($transaction);


	@closedir($indir);


}


@closedir($dir);


db_disconnect();


echo microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'] . "\n";
