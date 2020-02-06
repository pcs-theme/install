<?php
/*
Plugin Name: PCS-Theme Install
Version: 1.2
*/
define( 'CS_URL', '');
define( 'WP_VERSION_INST', '5.3.2-fr_FR' );
define( 'PCS_VERSION_INST', '3.0' );

if(!defined('ABSPATH')){

	// Global Configuration
	set_time_limit( 0 );
	error_reporting( E_ALL );
	ini_set('memory_limit','256M');

	// GitHub Information
	define( 'GITHUB_USERNAME' , 'lucanos' );
	define( 'GITHUB_PROJECT'  , 'WordPress-Remote-Installer' );

	// Version Information
	define( 'WPRI_VERSION'    , '0.4' );
	define( 'CS_THEME_URL'    , CS_URL.'pcs-theme/cs-theme.zip' );

	// Suggested Plugins and Themes
	$suggestions = array(

	  # Can be an Array of URLs for each Plugin, or a string URL for a text file with URLs for each Plugin on a new line
	  'plugins' => array(
	    //CS_URL.'plugins/pcs-mu-plugin-'.PCS_PCSMUPLU_VER_INST.'.zip',
	    //CS_URL.'plugins/pcs-plugins-light-'.PCS_PCSPLU_VER_INST.'.zip',
	    //CS_URL.'plugins/pcs-espocrm-'.PCS_ESPOCRMPLU_VER_INST.'.zip'
	  ),

	 # Can be an Array of URLs for each Theme, or a string URL for a text file with URLs for each Theme on a new line
	  'themes'  => array(
	    CS_URL.'pcs-theme/pcs-theme-'.PCS_VERSION_INST.'.zip',
	    CS_THEME_URL
	  )

	);

	function copyFile($url,$filename){
	    $file = fopen ($url, "rb");
	    if (!$file) return false; else {
	        $fc = fopen($filename, "wb");
	        while (!feof ($file)) {
	            $line = fread ($file, 1024);
	            fwrite($fc,$line);
	        }
	        fclose($fc);
	        return true;
	    }
	}

	// Function for Extraction
	function extractSubFolder( $zipFile , $target = null , $subFolder = null ){
	  if( is_null( $target ) )
	    $target = dirname( __FILE__ );
	  $zip = new ZipArchive;
	  $res = $zip->open( $zipFile );
	  if( $res === TRUE ){
	    if( is_null( $subFolder ) ){
	      $zip->extractTo( $target );
	    }else{
	      for( $i = 0 , $c = $zip->numFiles ; $i < $c ; $i++ ){
	        $entry = $zip->getNameIndex( $i );
	        //Use strpos() to check if the entry name contains the directory we want to extract
	        if( $entry!=$subFolder.'/' && strpos( $entry , $subFolder.'/' )===0 ){
	          $stripped = substr( $entry , 9 );
	          if( substr( $entry , -1 )=='/' ){
	           // Subdirectory
	            $subdir = $target.'/'.substr( $stripped , 0 , -1 );
	            if( !is_dir( $subdir ) )
	              mkdir( $subdir );
	          }else{
	            $stream = $zip->getStream( $entry );
	            $write = fopen( $target.'/'.$stripped , 'w' );
	            while( $data = fread( $stream , 1024 ) ){
	              fwrite( $write , $data );
	            }
	            fclose( $write );
	            fclose( $stream );
	          }
	        }
	      }
	    }
	    $zip->close();
	    return true;
	  }
	  die( 'Unable to open '.$zipFile );
	  return false;
	}

	// Function to Cleanse Webroot
	function rrmdir( $dir ){
	  if( is_dir( $dir ) ){
	    $objects = scandir( $dir );
	    foreach( $objects as $object ){
	      if( $object!='.' && $object!='..' ){
	        if( filetype( $dir.'/'.$object )=='dir' )
	          rrmdir( $dir.'/'.$object );
	        else
	          unlink( $dir.'/'.$object );
	      }
	    }
	    reset( $objects );
	    rmdir( $dir );
	  }else{
	    unlink( $dir );
	  }
	}
	function cleanseFolder( $exceptFiles = null ){
	  if( $exceptFiles == null )
	    $exceptFiles[] = basename( __FILE__ );
	  $contents = glob('*');
	  foreach( $contents as $c ){
	    if( !in_array( $c , $exceptFiles ) )
	      rrmdir( $c );
	  }
	}
	function downloadFromURL( $url = null , $local = null ){
	  $result = null;
	  if( is_null( $local ) )
	    $local = basename( $url );
	  if( $content = @file_get_contents( $url ) ){
	    $result = @file_put_contents( $local , $content );
	  }elseif( function_exists( 'curl_init' ) ){
	    $fp = fopen( dirname(__FILE__) . '/' . $local , 'w+' );
	    $ch = curl_init( str_replace( ' ' , '%20' , $url ) );
	    curl_setopt($ch , CURLOPT_TIMEOUT        , 50 );
	    curl_setopt($ch , CURLOPT_FILE           , $fp );
	    curl_setopt($ch , CURLOPT_FOLLOWLOCATION , true );
	    $result = curl_exec( $ch );
	    curl_close( $ch );
	    fclose( $fp );
	  }else{
	    $result = false;
	  }
	  return $result;
	}
	function getGithubVersion(){
	  $versionURL = 'https://' . GITHUB_USERNAME . '.github.io/' . GITHUB_PROJECT .'/version.txt';
	  $remoteVersion = null;
	  if( !( $remoteVersion = @file_get_contents( $versionURL ) )
	      && function_exists( 'curl_init' ) ){
	    $fp = fopen( dirname(__FILE__) . '/' . $local , 'w+' );
	    $ch = curl_init( str_replace( ' ' , '%20' , $url ) );
	    curl_setopt($ch , CURLOPT_TIMEOUT        , 50 );
	    curl_setopt($ch , CURLOPT_FILE           , $fp );
	    curl_setopt($ch , CURLOPT_FOLLOWLOCATION , true );
	    $remoteVersion = curl_exec( $ch );
	    curl_close( $ch );
	    fclose( $fp );
	  }
	  return $remoteVersion;
	}

	// Declare Parameters
	$step = 0;
	if( isset( $_POST['step'] ) )
	  $step = (int) $_POST['step'];

	?><!DOCTYPE html>
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
	<head>
	<meta name="viewport" content="width=device-width">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>WordPress &gt; Remote Installer</title>
	<style type="text/css">
	  /* buttons.css */
	.wp-core-ui .button,.wp-core-ui .button-primary,.wp-core-ui .button-secondary{display:inline-block;text-decoration:none;font-size:13px;line-height:26px;height:28px;margin:0;padding:0 10px 1px;cursor:pointer;border-width:1px;border-style:solid;-webkit-border-radius:3px;-webkit-appearance:none;border-radius:3px;white-space:nowrap;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.wp-core-ui button::-moz-focus-inner,.wp-core-ui input[type=reset]::-moz-focus-inner,.wp-core-ui input[type=button]::-moz-focus-inner,.wp-core-ui input[type=submit]::-moz-focus-inner{border-width:1px 0;border-style:solid none;border-color:transparent;padding:0}.wp-core-ui .button.button-large,.wp-core-ui .button-group.button-large .button{height:30px;line-height:28px;padding:0 12px 2px}.wp-core-ui .button.button-small,.wp-core-ui .button-group.button-small .button{height:24px;line-height:22px;padding:0 8px 1px;font-size:11px}.wp-core-ui .button.button-hero,.wp-core-ui .button-group.button-hero .button{font-size:14px;height:46px;line-height:44px;padding:0 36px}.wp-core-ui .button:active{outline:0}.wp-core-ui .button.hidden{display:none}.wp-core-ui input[type=reset],.wp-core-ui input[type=reset]:hover,.wp-core-ui input[type=reset]:active,.wp-core-ui input[type=reset]:focus{background:0 0;border:0;-moz-box-shadow:none;-webkit-box-shadow:none;box-shadow:none;padding:0 2px 1px;width:auto}.wp-core-ui .button,.wp-core-ui .button-secondary{color:#555;border-color:#ccc;background:#f7f7f7;-webkit-box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);vertical-align:top}.wp-core-ui p .button{vertical-align:baseline}.wp-core-ui .button.hover,.wp-core-ui .button:hover,.wp-core-ui .button-secondary:hover,.wp-core-ui .button.focus,.wp-core-ui .button:focus,.wp-core-ui .button-secondary:focus{background:#fafafa;border-color:#999;color:#222}.wp-core-ui .button.focus,.wp-core-ui .button:focus,.wp-core-ui .button-secondary:focus{-webkit-box-shadow:1px 1px 1px rgba(0,0,0,.2);box-shadow:1px 1px 1px rgba(0,0,0,.2)}.wp-core-ui .button.active,.wp-core-ui .button.active:hover,.wp-core-ui .button.active:focus,.wp-core-ui .button:active,.wp-core-ui .button-secondary:active{background:#eee;border-color:#999;color:#333;-webkit-box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5);box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5)}.wp-core-ui .button[disabled],.wp-core-ui .button:disabled,.wp-core-ui .button-secondary[disabled],.wp-core-ui .button-secondary:disabled,.wp-core-ui .button-disabled{color:#aaa!important;border-color:#ddd!important;-webkit-box-shadow:none!important;box-shadow:none!important;text-shadow:0 1px 0 #fff!important;cursor:default}.wp-core-ui .button-primary{background:#2ea2cc;border-color:#0074a2;-webkit-box-shadow:inset 0 1px 0 rgba(120,200,230,.5),0 1px 0 rgba(0,0,0,.15);box-shadow:inset 0 1px 0 rgba(120,200,230,.5),0 1px 0 rgba(0,0,0,.15);color:#fff;text-decoration:none}.wp-core-ui .button-primary.hover,.wp-core-ui .button-primary:hover,.wp-core-ui .button-primary.focus,.wp-core-ui .button-primary:focus{background:#1e8cbe;border-color:#0074a2;-webkit-box-shadow:inset 0 1px 0 rgba(120,200,230,.6);box-shadow:inset 0 1px 0 rgba(120,200,230,.6);color:#fff}.wp-core-ui .button-primary.focus,.wp-core-ui .button-primary:focus{border-color:#0e3950;-webkit-box-shadow:inset 0 1px 0 rgba(120,200,230,.6),1px 1px 2px rgba(0,0,0,.4);box-shadow:inset 0 1px 0 rgba(120,200,230,.6),1px 1px 2px rgba(0,0,0,.4)}.wp-core-ui .button-primary.active,.wp-core-ui .button-primary.active:hover,.wp-core-ui .button-primary.active:focus,.wp-core-ui .button-primary:active{background:#1e8cbe;border-color:#005684;color:rgba(255,255,255,.95);-webkit-box-shadow:inset 0 1px 0 rgba(0,0,0,.1);box-shadow:inset 0 1px 0 rgba(0,0,0,.1);vertical-align:top}.wp-core-ui .button-primary[disabled],.wp-core-ui .button-primary:disabled,.wp-core-ui .button-primary-disabled{color:#94cde7!important;background:#298cba!important;border-color:#1b607f!important;-webkit-box-shadow:none!important;box-shadow:none!important;text-shadow:0 -1px 0 rgba(0,0,0,.1)!important;cursor:default}.wp-core-ui .button-group{position:relative;display:inline-block;white-space:nowrap;font-size:0;vertical-align:middle}.wp-core-ui .button-group>.button{display:inline-block;border-radius:0;margin-right:-1px;z-index:10}.wp-core-ui .button-group>.button-primary{z-index:100}.wp-core-ui .button-group>.button:hover{z-index:20}.wp-core-ui .button-group>.button:first-child{border-radius:3px 0 0 3px}.wp-core-ui .button-group>.button:last-child{border-radius:0 3px 3px 0}@media screen and (max-width:782px){.wp-core-ui .button,.wp-core-ui .button.button-large,.wp-core-ui .button.button-small,input#publish,input#save-post,a.preview{padding:10px 14px;line-height:1;font-size:14px;vertical-align:middle;height:auto;margin-bottom:4px}#media-upload.wp-core-ui .button{padding:0 10px 1px;height:24px;line-height:22px;font-size:13px}.wp-core-ui .save-post-status.button{position:relative;margin:0 14px 0 10px}.wp-core-ui.wp-customizer .button,.press-this.wp-core-ui .button,.press-this input#publish,.press-this input#save-post,.press-this a.preview{padding:0 10px 1px;font-size:13px;line-height:26px;height:28px;margin:0;vertical-align:inherit}.interim-login .button.button-large{height:30px;line-height:28px;padding:0 12px 2px}}

	/* css.css */
	@font-face{font-family:'Open Sans';font-style:normal;font-weight:300;src:local('Open Sans Light'), local('OpenSans-Light'), url(http://themes.googleusercontent.com/static/fonts/opensans/v8/DXI1ORHCpsQm3Vp6mXoaTRa1RVmPjeKy21_GQJaLlJI.woff) format('woff');}
	@font-face{font-family:'Open Sans';font-style:normal;font-weight:400;src:local('Open Sans'), local('OpenSans'), url(http://themes.googleusercontent.com/static/fonts/opensans/v8/u-WUoqrET9fUeobQW7jkRT8E0i7KZn-EPnyo3HZu7kw.woff) format('woff');}
	@font-face{font-family:'Open Sans';font-style:normal;font-weight:600;src:local('Open Sans Semibold'), local('OpenSans-Semibold'), url(http://themes.googleusercontent.com/static/fonts/opensans/v8/MTP_ySUJH_bn48VBG8sNSha1RVmPjeKy21_GQJaLlJI.woff) format('woff');}
	@font-face{font-family:'Open Sans';font-style:italic;font-weight:300;src:local('Open Sans Light Italic'), local('OpenSansLight-Italic'), url(http://themes.googleusercontent.com/static/fonts/opensans/v8/PRmiXeptR36kaC0GEAetxrsuoFAk0leveMLeqYtnfAY.woff) format('woff');}
	@font-face{font-family:'Open Sans';font-style:italic;font-weight:400;src:local('Open Sans Italic'), local('OpenSans-Italic'), url(http://themes.googleusercontent.com/static/fonts/opensans/v8/xjAJXh38I15wypJXxuGMBtIh4imgI8P11RFo6YPCPC0.woff) format('woff');}
	@font-face{font-family:'Open Sans';font-style:italic;font-weight:600;src:local('Open Sans Semibold Italic'), local('OpenSans-SemiboldItalic'), url(http://themes.googleusercontent.com/static/fonts/opensans/v8/PRmiXeptR36kaC0GEAetxmWeb5PoA5ztb49yLyUzH1A.woff) format('woff');}

	/* install.css */
	html{background:#eee;margin:0 20px}body{background:#fff;color:#555;font-family:"Open Sans",sans-serif;margin:140px auto 25px;padding:20px 20px 10px;max-width:700px;-webkit-font-smoothing:subpixel-antialiased;-webkit-box-shadow:0 1px 3px rgba(0,0,0,.13);box-shadow:0 1px 3px rgba(0,0,0,.13)}a{color:#0074a2;text-decoration:none}a:hover{color:#2ea2cc}h1{border-bottom:1px solid #dedede;clear:both;color:#666;font-size:24px;margin:30px 0;padding:0;padding-bottom:7px;font-weight:400}h2{font-size:16px}p,li,dd,dt{padding-bottom:2px;font-size:14px;line-height:1.5}code,.code{font-family:Consolas,Monaco,monospace}ul,ol,dl{padding:5px 5px 5px 22px}a img{border:0}abbr{border:0;font-variant:normal}#logo{margin:6px 0 14px;border-bottom:0;text-align:center}#logo a{}.step{margin:20px 0 15px}.step,th{text-align:left;padding:0}.step .button-large{font-size:14px}textarea{border:1px solid #dfdfdf;font-family:"Open Sans",sans-serif;width:100%;-moz-box-sizing:border-box;-webkit-box-sizing:border-box;-ms-box-sizing:border-box;box-sizing:border-box}.form-table{border-collapse:collapse;margin-top:1em;width:100%}.form-table td{margin-bottom:9px;padding:10px 20px 10px 0;border-bottom:8px solid #fff;font-size:14px;vertical-align:top}.form-table th{font-size:14px;text-align:left;padding:16px 20px 10px 0;width:140px;vertical-align:top}.form-table code{line-height:18px;font-size:14px}.form-table p{margin:4px 0 0;font-size:11px}.form-table input{line-height:20px;font-size:15px;padding:3px 5px;border:1px solid #ddd;box-shadow:inset 0 1px 2px rgba(0,0,0,.07)}input,submit{font-family:"Open Sans",sans-serif}.form-table input[type=text],.form-table input[type=password]{width:206px}.form-table th p{font-weight:400}.form-table.install-success td{vertical-align:middle;padding:16px 20px 10px 0}.form-table.install-success td p{margin:0;font-size:14px}.form-table.install-success td code{margin:0;font-size:18px}#error-page{margin-top:50px}#error-page p{font-size:14px;line-height:18px;margin:25px 0 20px}#error-page code,.code{font-family:Consolas,Monaco,monospace}#pass-strength-result{background-color:#eee;border-color:#ddd!important;border-style:solid;border-width:1px;margin:5px 5px 5px 0;padding:5px;text-align:center;width:200px;display:none}#pass-strength-result.bad{background-color:#ffb78c;border-color:#ff853c!important}#pass-strength-result.good{background-color:#ffec8b;border-color:#fc0!important}#pass-strength-result.short{background-color:#ffa0a0;border-color:#f04040!important}#pass-strength-result.strong{background-color:#c3ff88;border-color:#8dff1c!important}.message{border:1px solid #c00;padding:.5em .7em;margin:5px 0 15px;background-color:#ffebe8}#dbname,#uname,#pwd,#dbhost,#prefix,#user_login,#admin_email,#pass1,#pass2{direction:ltr}body.rtl,.rtl textarea,.rtl input,.rtl submit{font-family:Tahoma,sans-serif}:lang(he-il) body.rtl,:lang(he-il) .rtl textarea,:lang(he-il) .rtl input,:lang(he-il) .rtl submit{font-family:Arial,sans-serif}@media only screen and (max-width:799px){body{margin-top:115px}#logo a{margin:-125px auto 30px}}@media screen and (max-width:782px){.form-table{margin-top:0}.form-table th,.form-table td{display:block;width:auto;vertical-align:middle}.form-table th{padding:20px 0 0}.form-table td{padding:5px 0;border:0;margin:0}textarea,input{font-size:16px}.form-table td input[type=text],.form-table td input[type=password],.form-table td select,.form-table td textarea,.form-table span.description{width:100%;font-size:16px;line-height:1.5;padding:7px 10px;display:block;max-width:none;box-sizing:border-box;-moz-box-sizing:border-box}}

	/* custom css */
	#footer{border-top:1px solid #ddd;margin-top:15px;padding:15px 0 5px;font-size:.8em;color:#333;position:relative}#footer .legal{font-size:0.8em}#footer a.github{position:absolute;top:50%;right:0;margin-top:-16px;height:32px;width:32px;text-indent:-9999px;margin-top:-7px}ul,li{list-style:none;padding:0;margin:0}li{padding:10px 10px 10px 36px;margin:10px 0;border:solid 1px;background:#FFF none no-repeat 10px 14px;border-radius:5px}li.fail{border-color:#df8f8f;background-color:#ffd7d7;}li.warn{border-color:#e6db55;background-color:#fffbcc;}li.pass{border-color:#acdbad;background-color:#ecfae3;}li.done{border-color:#085999;}li.skip{border-color:#CCC;background-color:#EEE;color:#666}textarea{height:150px}.version_alert{border:1px solid #dedede;border-radius:10px;font-weight:bold;padding:20px 20px 20px 72px}
	</style>
	</head>
	<body class="wp-core-ui">
	<h1 id="logo"><a href="http://wordpress.org/">WordPress Remote Installer</a></h1>

	<?php

	switch( $step ){

	  default :
	  case 0 :

	?>
	<!-- STEP 0 //-->
	<h1>WordPress Remote Installer</h1>
	<p>The WordPress Remote Installer is a script designed to streamline the installation of the WordPress Content Management System. Some users have limited experience using FTP, some webhosts do not allow files to be decompressed after being uploaded, and some people want to make their WordPress installs faster and simpler.</p>
	<p>Using the WordPress Remote Installer is simple - upload a single PHP file to your server, access it via a web-browser and simply follow the prompts through 7 easy steps, at the end of which, the Wordpress Installer will commence.</p>
	<?php
	    if( 0 && version_compare( WPRI_VERSION , $githubVersion = getGithubVersion() , '<' ) ){
	?>
	<p class="version_alert">You are using Version <?php echo WPRI_VERSION; ?>. Version <?php echo $githubVersion; ?> is available through <a href="https://github.com/<?php echo GITHUB_USERNAME; ?>/<?php echo GITHUB_PROJECT; ?>">Github</a>.</p>
	<?php
	    }
	?>
	<form method="post">
	  <input type="hidden" name="step" value="1" />
	  <input type="submit" name="submit" value="Let's Get Started!" class="button button-large" />
	</form>
	<?php

	    break;

	  case 1 :

	    if( isset( $_POST['action'] ) && $_POST['action']=='cleanse' )
	      cleanseFolder();

	    $tests = array(
	      array(
	        'result' => ini_get( 'allow_url_fopen' ) ,
	        'pass' => '<strong>allow_url_open</strong> is Enabled' ,
	        'fail' => '<strong>allow_url_open</strong> is Disabled'
	      ) ,
	      array(
	        'result' => !count( array_diff( glob( '*' ) , array( basename( __FILE__ ) , 'version.txt' ) ) ) ,//( glob( '*' ) == array( basename( __FILE__ ) ) ) ,
	        'pass' => 'The server is empty (apart from this file)' ,
	        'fail' => 'The server is not empty.'
	      )
	    );
	?>
	<!-- STEP 1 //-->
	<h1>Step 1/7: Pre-Install Checks</h1>
	<?php
	    if( isset( $_POST['action'] ) && $_POST['action']=='cleanse' ){
	?>
	<p>All Files Deleted from the Directory as requested.</p>
	<?php
	    }
	?>
	<ul>
	<?php

	    $proceed = true;
	    foreach( $tests as $t ){
	      if( !$t['result'] )
	        $proceed = false;
	?>
	  <li class="<?php echo ( $t['result'] ? 'pass' : 'fail' ); ?>"><?php echo $t[( $t['result'] ? 'pass' : 'fail' )]; ?></li>
	<?php
	    }
	?>
	</ul>
	<?php
	    if( !$proceed ){
	?>
	<p>NOTE: We are unable to proceed until the above issue(s) are resolved.</p>
	<form method="post">
	  <input type="hidden" name="step" value="1" />
	  <input type="hidden" name="action" value="cleanse" />
	  <input type="submit" name="submit" value="Delete All Files from Directory to Proceed" class="button button-large confirm" data-msg="Are you sure? All files, Wordpress-related or not, will be removed. Delete files are unrecoverable." />
	</form>
	<?php
	    }else{
	?>
	<form method="post">
	  <input type="hidden" name="step" value="2" />
	  <input type="submit" name="submit" value="Commence Install of WordPress" class="button button-large" />
	</form>
	<?php
	    }

	    break;

	  case 2 :

	?>
	<!-- STEP 2 //-->
	<h1>Step 2/7: Installing Wordpress</h1>
	<ul>
	<?php
	    $proceed = true;

	    if( downloadFromURL( CS_URL.'wordpress/wordpress-'.WP_VERSION_INST.'.zip' , 'wordpress.zip' ) ){
	?>
	  <li class="pass">Downloading WordPress <?=WP_VERSION_INST?> - OK</li>
	<?php
	    }else{
	      $proceed = false;
	?>
	  <li class="fail">Downloading WordPress <?=WP_VERSION_INST?> - FAILED</li>
	<?php
	    }

	    if( !$proceed ){
	?>
	  <li class="skip">Extract WordPress - SKIPPED</li>
	<?php
	    }elseif( extractSubFolder( 'wordpress.zip' , null , 'wordpress' ) ){
	?>
	  <li class="pass">Extract WordPress - OK</li>
	<?php
	    }else{
	      $proceed = false;
	?>
	  <li class="fail">Extract WordPress - FAILED</li>
	<?php
	    }

	    if( !$proceed ){
	?>
	  <li class="skip">Delete WordPress ZIP - SKIPPED</li>
	<?php
	    }elseif( unlink( 'wordpress.zip' ) ){
	?>
	  <li class="pass">Delete WordPress ZIP - OK</li>
	<?php
	    }else{
	      $proceed = false;
	?>
	  <li class="fail">Delete WordPress ZIP - FAILED</li>
	<?php
	    }
	?>
	</ul>
	<?php

	    if( !$proceed ){
	?>
	<p>NOTE: We are unable to proceed until the above issue(s) are resolved.</p>
	<?php
	    }else{
	?>
	<form method="post">
	  <input type="hidden" name="step" value="3" />
	  <input type="submit" name="submit" value="Next Step - Plugins" class="button button-large" />
	</form>
	<?php
	    }

	    break;

	  case 3 :
	  
	    $suggest = '';
	    if( is_array( $suggestions['plugins'] ) ){
	      $suggest = implode( "\n" , $suggestions['plugins'] );
	    }elseif( is_string( $suggestions['plugins'] ) ){
	      if( !( $suggest = @file_get_contents( $suggestions['plugins'] ) ) )
	        $suggest = '';
	    }

	?>
	<!-- STEP 3 //-->
	<h1>Step 3/7: Installing Plugins</h1>
	<p>List the Download URLs for all WordPress Plugins, one per line</p>
	<form method="post">
	  <textarea name="plugins"><?php echo $suggest; ?></textarea>
	  <input type="hidden" name="step" value="4" />
	  <input type="submit" name="submit" value="Install Plugins" class="button button-large" />
	</form>
	<?php

	    break;

	  case 4 :

	?>
	<!-- STEP 4 //-->
	<h1>Step 4/7: Installing Plugins</h1>
	<ul>
	<?php
	    $plugin_result = ( !file_exists( @unlink( dirname( __FILE__ ).'/wp-content/plugins/hello.php' ) || dirname( __FILE__ ).'/wp-content/plugins/hello.php' ) );
	?>
	  <li class="<?php echo ( $plugin_result ? 'pass' : 'fail' ); ?>">Delete Unneeded "Hello Dolly" Plugin - <?php echo ( $plugin_result ? 'OK' : 'FAILED' ); ?></li>
	<?php    
	    if( isset( $_POST['plugins'] ) ){
	      $plugins = array_filter( explode( "\n" , $_POST['plugins'] ) );
	      foreach( $plugins as $url ){
	        $plugin_result = false;
	        $plugin_message = 'UNKNOWN';
	        $url = trim( $url );
	        $bits = array();
	        if( strpos( $url , 'http' )!==0 )
	          $url = 'http://'.$url;
	        if( preg_match( '/^(https?\:\/\/?downloads\.wordpress\.org\/plugin\/)([^\.]+)((?:\.\d+)+)?\.zip$/' , $url , $bits ) )
	          $url = $bits[1].$bits[2].'.zip';
	        if(1){
	          $get = copyFile($url,'temp_plugin.zip');
	          if( !$get ){
	            $plugin_message = 'FAILED TO DOWNLOAD';
	          }else{
	            if( !extractSubFolder( 'temp_plugin.zip' , dirname( __FILE__ ).'/wp-content/plugins' ) ){
	              $plugin_message = 'FAILED TO EXTRACT';
	            }else{
	              $plugin_result = true;
	              $plugin_message = 'OK';
	              $bits[2] = basename($url);
	            }
	            @unlink( 'temp_plugin.zip' );
	          }
	        }
	        else{
	        $get = @file_get_contents( $url );
	        if( !$get ){
	          $plugin_message = 'FAILED TO DOWNLOAD';
	        }else{
	          file_put_contents( 'temp_plugin.zip' , $get );
	          if( !extractSubFolder( 'temp_plugin.zip' , dirname( __FILE__ ).'/wp-content/plugins' ) ){
	            $plugin_message = 'FAILED TO EXTRACT';
	          }else{
	            $plugin_result = true;
	            $plugin_message = 'OK';
	            $bits[2] = basename($url);
	          }
	          @unlink( 'temp_plugin.zip' );
	        }
	        }
	?>
	  <li class="<?php echo ( $plugin_result ? 'pass' : 'fail' ); ?>">Installing <strong><?php echo $bits[2]; ?></strong> - <?php echo $plugin_message; ?></li>
	<?php
	      }
	    }
	?>
	</ul>
	<form method="post">
	  <input type="hidden" name="step" value="5" />
	  <input type="submit" name="submit" value="Next Step - Themes" class="button button-large" />
	</form>
	<?php

	    break;

	  case 5 :
	  
	    $suggest = '';
	    if( is_array( $suggestions['themes'] ) ){
	      $suggest = implode( "\n" , $suggestions['themes'] );
	    }elseif( is_string( $suggestions['themes'] ) ){
	      if( !( $suggest = @file_get_contents( $suggestions['themes'] ) ) )
	        $suggest = '';
	    }

	?>
	<!-- STEP 5 //-->
	<h1>Step 5/7: Installing Themes</h1>
	<p>List the Download URLs for all WordPress Themes, one per line</p>
	<form method="post">
	  <textarea name="themes"><?php echo $suggest; ?></textarea>
	  <input type="hidden" name="step" value="6" />
	  <input type="submit" name="submit" value="Install Themes" class="button button-large" />
	</form>
	<?php

	    break;

	  case 6 :

	?>
	<!-- STEP 6 //-->
	<h1>Step 6/7: Installing Themes</h1>
	<ul>
	<?php

	    if( isset( $_POST['themes'] ) ){
	      $themes = array_filter( explode( "\n" , $_POST['themes'] ) );
	      foreach( $themes as $url ){
	        $theme_result = false;
	        $theme_message = 'UNKNOWN';
	        $url = trim( $url );
	        $bits = array();
	        if( !$url ) continue;
	        if( strpos( $url , 'http' )!==0 )
	          $url = 'http://'.$url;
	        preg_match( '/^(https?\:\/\/?wordpress.org\/extend\/themes\/download\/)([^\.]+)((?:\.\d+)+)\.zip$/' , $url , $bits );
	        $get = @file_get_contents( $url );
	        if( !$get ){
	          $theme_message = 'FAILED TO DOWNLOAD';
	        }else{
	          file_put_contents( 'temp_theme.zip' , $get );
	          if( !extractSubFolder( 'temp_theme.zip' , dirname( __FILE__ ).'/wp-content/themes' ) ){
	            $theme_message = 'FAILED TO EXTRACT';
	          }else{
	            $theme_result = true;
	            $theme_message = 'OK';
	            $bits[2] = basename($url);
	          }
	?>
	  <li class="<?php echo ( $theme_result ? 'pass' : 'fail' ); ?>">Installing <strong><?php echo $bits[2]; ?>.zip</strong> - <?php echo $theme_message; ?></li>
	<?php
	          @unlink( 'temp_theme.zip' );
	        }
	        echo '</li>';
	      }
	    }

	?>
	</ul>
	<form method="post">
	  <input type="hidden" name="step" value="7" />
	  <input type="submit" name="submit" value="Next Step - Clean Up" class="button button-large" />
	</form>
	<?php

	    break;

	  case 7 :

	?>
	<!-- STEP 7 //-->
	<h1>Step 7/7: Cleaning Up</h1>
	<ul>
	<?php

	    $tests = array(
	      array(
	        'result' => ( !file_exists( 'wordpress.zip' ) || @unlink( 'wordpress.zip' ) ) ,
	        'pass' => 'Remove WordPress Installer - OK' ,
	        'fail' => 'Remove WordPress Installer - FAILED'
	      ) ,
	      array(
	        'result' => ( !file_exists( 'temp_plugin.zip' ) || @unlink( 'temp_plugin.zip' ) ) ,
	        'pass' => 'Remove Temporary Plugin File - OK' ,
	        'fail' => 'Remove Temporary Plugin File - FAILED'
	      ) ,
	      array(
	        'result' => ( !file_exists( 'temp_theme.zip' ) || @unlink( 'temp_theme.zip' ) ) ,
	        'pass' => 'Remove Temporary Theme File - OK' ,
	        'fail' => 'Remove Temporary Theme File - FAILED'
	      ) ,
	      array(
	        'result' => ( !file_exists( __FILE__ ) || @unlink( __FILE__ ) ) ,
	        'pass' => 'Remove WordPress Remote Installer - OK' ,
	        'fail' => 'Remove WordPress Remote Installer - FAILED'
	      ) ,
	    );
	    
	    foreach( $tests as $t ){
	?>
	  <li class="<?php echo ( $t['result'] ? 'pass' : 'fail' ); ?>"><?php echo $t[( $t['result'] ? 'pass' : 'fail' )]; ?></li>
	<?php
	    }
	?>
	</ul>
	<form method="post" action="./wp-admin/setup-config.php">
	  <input type="submit" name="submit" value="Launch WordPress Installer" class="button button-large" />
	</form>
	<?php

	    break;
	}

	?>

	<div id="footer">
	  <a href="https://github.com/<?php echo GITHUB_USERNAME; ?>/<?php echo GITHUB_PROJECT; ?>" class="github">View on GitHub</a>
	  Created by <a href="http://lucanos.com">Luke Stevenson</a><br/>
	  <div class="legal">
	    <strong>NOTE:</strong> This script is not an official WordPress product.<br/>
	    The WordPress logo is the property of the WordPress Foundation.
	  </div>
	</div>

	<script src="//code.jquery.com/jquery.min.js"></script>
	<script>
	  jQuery(document).ready(function($){

	    $('input.confirm')
	      .on('click',function(e){
	        var $t = $(this) ,
	            msg = 'Are you sure?';
	        if( $t.data( 'msg' ) )
	          msg = $t.data( 'msg' );
	        if( !confirm( msg ) )
	          e.preventDefault();
	      });

	  });
	</script>
	</body>
	</html><?php
}
else{
	add_filter('pcs_plugins_url',function(){
		return CS_URL.'plugins/';
	});
	class install_pcs_theme {
		protected $gp_installed = false;
		protected $gpzip;

		function __construct(){
			if(is_file(WP_CONTENT_DIR.'/themes/pcs-theme/functions.php'))
				$this->gp_installed = true;

			if(!$this->gp_installed){
				$this->gpzip = 'pcs-theme/pcs-theme-'.PCS_VERSION_INST.'.zip';
				add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
				add_action('update-custom_cs-url-theme-upload', array( &$this, 'custom_url_theme_upload' ));
			}

		}
		public function admin_menu() {
			// Make sure privileges are correct to see the page.
			if ( ! current_user_can( 'install_themes' ) ) {
				return;
			}

		    add_submenu_page('themes.php' ,'Installer PCS Theme', 'Installer PCS Theme', 'install_themes', 'cs-install-pcs', array(&$this,'install_theme') );
			
		}
		function install_theme(){
			?>
			<h4><?php echo 'Installer PCS Theme'; ?></h4>
			<form method="post" action="<?php echo self_admin_url('update.php?action=cs-url-theme-upload') ?>">
			<?php wp_nonce_field( 'theme-url-upload') ?>
				<?php submit_button( __( 'Install Now' ), 'button', 'install-theme-submit', false ); ?>
			</form>
			<?php	
		}
		function custom_url_theme_upload () {
			if(!$this->gp_installed){
				
				//if user cannot install themes we die
				if ( ! current_user_can('install_themes') )
					wp_die(__('You do not have sufficient permissions to install themes for this site.'));

				check_admin_referer('theme-url-upload');
					
				$this->installTheme(CS_URL.$this->gpzip);

				return;
			}
			wp_safe_redirect( admin_url('themes.php') );
		
		}

		function installTheme($file) {
		
			$title = __('Upload Theme');
			$parent_file = 'themes.php';
			$submenu_file = 'theme-install.php';
			add_thickbox();
			wp_enqueue_script('theme-preview');
			require_once(ABSPATH . 'wp-admin/admin-header.php');

			$title = sprintf( __('Installing Theme from uploaded file: %s'), basename( $file ) );
			$nonce = 'theme-upload';
			//$url = add_query_arg(array('package' => $file_upload->id), 'update.php?action=upload-theme');
			$type = 'upload';

			$upgrader = new Theme_Upgrader( new Theme_Installer_Skin( compact('type', 'title', 'nonce') ) );
			$result = $upgrader->install( $file );

			//if ( $result )
			//	self::cleanup($file);

			include(ABSPATH . 'wp-admin/admin-footer.php');
		
		}
	}
	new install_pcs_theme;
}