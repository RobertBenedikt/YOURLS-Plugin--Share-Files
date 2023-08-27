<?php
/*
Plugin Name: Share Files
Plugin URI: http://www.mattytemple.com/projects/yourls-share-files/
Description: A simple plugin that allows you to easily share files
Version: 1.0
Author: Matt Temple
Author URI: http://www.mattytemple.com/
*/

// Register our plugin admin page
yourls_add_action( 'plugins_loaded', 'matt_share_files_add_page' );
function matt_share_files_add_page() {
	yourls_register_plugin_page( 'share_files', 'Sdílení souborů', 'matt_share_files_do_page' );
	// parameters: page slug, page title, and function that will display the page itself
}

// Display admin page
function matt_share_files_do_page() {

	$allow_extension = array('pdf','jpg','gif','doc','csv');

	// Check if a form was submitted
	if(isset($_FILES['file_upload']['name']))
	{
		$check_extension = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
		$status_check_extension = null;
		foreach ($allow_extension as $value)
		{
			 if($check_extension === $value)
			 {
			 	$status_check_extension = true;
			 	break;
			 }
		}
		if ($status_check_extension)
		{
			matt_share_files_save_files();
		}
		else
		{
			echo '<p><b>Soubor není v povoleném formátu!!!</b> Nelze použít soubor <b>'.$check_extension.'.</b></p>';
		}
	}
	$print_allow_extension = '[';
	foreach ($allow_extension as $value)
	{
		$print_allow_extension .= ' '.$value.',';
	}
	$print_allow_extension = rtrim($print_allow_extension,',');
	$print_allow_extension .= ']';
	
	echo '
				<h2>Sdílení souborů</h2>
				<p>Tento plugin umožňuje sdílení nahraných souborů online pomocí zkrácené URL adresy</p>
				<p>Povolené přípony jsou: <b>'.$print_allow_extension.'</b></p>
				<form method="post" enctype="multipart/form-data">
				<p><label for="file_upload">Vyber soubor pro sdílení</label> <input type="file" id="file_upload" name="file_upload" /></p>
				<p><label for="custom_keyword">Vlastní URL</label> <input type="text" id="custom_keyword" name="custom_keyword" /></p>
				<p><input type="submit" value="Nahrát soubor" /></p>
				</form>';
}
// Update option in database
function matt_share_files_save_files()
{
	$matt_dir = '/files_upload/';
	$matt_uploaddir = YOURLS_ABSPATH.$matt_dir;
	$matt_ssl = $_SERVER['HTTPS'] ? 'https' : 'http';
	$matt_url = $matt_ssl.'://'.$_SERVER['HTTP_HOST'].$matt_dir;
	//
	$matt_extension = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
	$matt_filename = pathinfo($_FILES['file_upload']['name'], PATHINFO_FILENAME);
	$matt_filename_trim = trim($matt_filename);
	$matt_RemoveChars  = array( "([\40])" , "([^a-zA-Z0-9-])", "(-{2,})" ); 
	$matt_ReplaceWith = array("-", "", "-"); 
	$matt_safe_filename = preg_replace($matt_RemoveChars, $matt_ReplaceWith, $matt_filename_trim); 
	$matt_count = 2;
	$matt_path = $matt_uploaddir.$matt_safe_filename.'.'.$matt_extension;
	$matt_final_file_name = $matt_safe_filename.'.'.$matt_extension;
	while(file_exists($matt_path))
	{
		$matt_path = $matt_uploaddir.$matt_safe_filename.'-'.$matt_count.'.'.$matt_extension;
		$matt_final_file_name = $matt_safe_filename.'-'.$matt_count.'.'.$matt_extension;
		$matt_count++;	
	}

	if(copy($_FILES['file_upload']['tmp_name'], $matt_path))
	{
		if(isset($_POST['custom_keyword']) && $_POST['custom_keyword'] != '')
		{
			$matt_custom_keyword = $_POST['custom_keyword'];
			$matt_short_url = yourls_add_new_link($matt_url.$matt_final_file_name, $matt_custom_keyword, $matt_filename);
		}
		else
		{
			$matt_short_url = yourls_add_new_link($matt_url.$matt_final_file_name, NULL, $matt_filename);
		}
		echo 'Soubor byl úspěšně nahrán. Přístupný je pod krátkou URL <a href="'.$matt_short_url['shorturl'].'"><b>'.$matt_short_url['shorturl'].'</b></a>';
	}
	else
	{
		echo 'Došlo k nějaké chybě při nahrávání souboru';
	}
}
