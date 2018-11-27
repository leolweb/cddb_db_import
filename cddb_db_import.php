#!/usr/bin/env php
<?php
/**
 * cddb_db_import.php, 0.2
 * 
 * Shows README.md file.
 * 
 * @version 0.2
 * @copyright Copyright (c) 2018 Leonardo Laureti
 * @license MIT License
 */


file_exists('README.md') && print(file_get_contents('README.md'));
