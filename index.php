<?php
global $userme, $filename, $filemtime;

// set the default timezone to use.
date_default_timezone_set('Australia/Sydney');

if(array_key_exists('back', $_GET) && $_GET['back'] != '' ) {
	//parse number. 
	$back=intval($_GET['back']);
} else {
	$back = 0;
}


$time = mktime() - $back*86400 ;

$isodatetime = date(DATE_ATOM, $time);
$isodate = date('Y-m-d', $time);

$did_post = false;
$filename = "logs/$isodate.txt";
$filemtime = 0;
// todo: sanitise user




if(array_key_exists('user', $_GET) && $_GET['user'] != '' ) {
	$user = $_GET['user'];
	$safeuser = htmlentities($user, ENT_QUOTES, "UTF-8");
	$userme = $user;
} else {
	$user = 'anonymous';
	$safeuser = 'anonymous';

	// todo: Random color?
}


if(array_key_exists('poll', $_GET) && $_GET['poll'] != '' ) {
	$poll = $_GET['poll'];
	
	if(file_exists($filename)){
		$filemtime = filemtime($filename);
		echo $filemtime;

	}
	die();
} 
if(array_key_exists('bare', $_GET) && $_GET['bare'] != '' ) {
	$bare = true;
} else {
	$bare = false;
}

function parseparams ($str) {
	$results = array();
	$semisplit = explode(';',$str);

	foreach ($semisplit as $key => $value) {
		$semisplit[$key]=explode('=',$value);

		foreach ($semisplit[$key] as $subkey => $subvalue) {
			$semisplit[$key][$subkey] = trim($subvalue);
		}
		

		$results[$semisplit[$key][0]] = $semisplit[$key][1];
		
	}
	return $results;
}

function logmessage ($postparams, $fn, $safeuser, $isodatetime) {
	// todo: check for form template
	// for now, it's only one: "plain text message"

	$message = $postparams['message'];
	$cleanmessage =  (preg_replace("/\n---+/", "\n<hr>", htmlentities($message))) ;

	$txt = '';
	$txt .= "--- ";
	$txt .= "user = $safeuser; ";
	$txt .= "timestamp = $isodatetime; ";
	$txt .= "\n";
	$txt .= $cleanmessage;
	
	file_put_contents($fn, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
}


if( $_POST['message'] ) {
	
	logmessage($_POST, $filename, $safeuser, $isodatetime);
	$did_post = true; 

}
if(file_exists( $filename )) {
	$msgs = file_get_contents( $filename );
	$msgs = preg_split("/(^---+\n?)|(\n---+\n?)/",$msgs);
	$filemtime = filemtime($filename);
} else {
	$msgs = array();
}


function parsemsg ($msg) {
		global $userme;

		$msgarray = explode("\n",$msg);
		$firstline = array_shift($msgarray);
		$params = parseparams($firstline);
		
		
		$date =(array_key_exists('timestamp',$params))  ?  strtotime($params['timestamp']): mktime();
		$user = (array_key_exists('user',$params))  ?  $params['user']: 'anonymous';
		

		$time = '';
		if($date) {
			$time =  date('g:i:s a', $date);
		}
		$userclass = $userme == $user ? "user-me" : "user-1";

		$msg = trim(implode("\n",$msgarray));

		// treat emoji specially
		$is_emoji = '';
		if(mb_strlen($msg) == 1) {
			$is_emoji = 'is-emoji';
		}
		
		$params['user']      	 = $user;
		$params['is_emoji']  	 = $is_emoji; 
		$params['userclass'] 	 = $userclass; 
		$params['timestamp'] 	 = $date; 
		$params['time']      	 = $time; 
		$params['msg']       	 = trim($msg);
		$params['msg_length']	 = mb_strlen($msg);

		return $params;


}

function printmymsg ($msgs) {
	 $txt = '';
	 $count = 0;
	 global $userme, $filemtime, $filename;
	 
	 if($userme == '') { 
		$txt .= "<form id=\"userform\" action=\"#last\" method=\"GET\">
		<input name=\"user\"  id=\"user\"  >
		<button type=\"submit\" name=\"userset\" id=\"user\" value=\"userset\">set user</button>
		</form>";

	 }
	 if($userme != '') {
		 $txt .= "<section id=\"messagewindow\" data-timestamp=\"$filemtime\" class=\"message-window\">";
		 
		 $txt .= "<h1>$filename</h1>";
	   

	    
    
		 for ($i=0; $i < count($msgs); $i++) { 
			$msg = $msgs[$i];
			

			if(trim($msg) != '') {

				$count += 1;
				

				$pmsg = parsemsg($msg);
				extract($pmsg);

				if($i === count($msgs)-1) {
					$msgid = 'last';
				} else {
					$msgid = "msg-$i";
				}

				$txt .= "<article  id=\"$msgid\" class=\"$is_emoji $userclass day-forecast\">
							<h2>$user $time $msg_length</h2>
							<div class=\"message-body\">$msg</div>
							</article>";

			}


		 }

		if($count === 0 ){ 
			
			$txt .= "<h2 class=\"no-messages\" >no messages yet</h2>";
		}
		$txt .= '</section>';
	}
	 return $txt;
}

if($bare) {

	echo printmymsg ($msgs);
	die();
}


?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Logger</title>
	<style>

		/* kopi */








:root {

	--themecolor1: #872b23;
	--themecolor2: #ffb126;
	--themecolor3: #3a782d;
	--themecolor4: #e34550;
	--themecolor5: #2c3260;
	--themecolor6: #b3a2ac;
	--themecolor7: #e0d1cf;
	--themecolor8: #f9ebe2;
}



		body {
			padding: 0;
			margin:  0;
			margin-bottom: 10em;
		}
		.message-window {
			margin:  0;
			display: flex;
			padding: 1em;
			box-sizing: border-box;
			background: var(--themecolor7);
			font-family: sans-serif;
			min-height: calc( 100vh - 10em ) ;
			justify-content: center;
			flex-direction: column;
			


		}
		.message-body {
			white-space: pre-wrap;
		}
		.message-body hr{
			padding: 0;
			margin: 0;
		}

		.message-window article {
			background:  var(--themecolor6);
			border-radius: 1em;
			margin: .5em;
			padding: 1em;
			position: relative;
		}
		.message-window article h2 {
			font-size: .8em;
			opacity: 0.5;
			position: absolute;
			top: -.5em;
			right: .8em;
		}



		/* theme */
		.message-window article.user-me {
			background-color: var(--themecolor4);
			color: var(--themecolor8);
			margin-left: 20vw;
		}

		.message-window article.user-1 {
			background-color: var(--themecolor5);
			color: var(--themecolor8);
			margin-right: 20vw;
		}
		.message-window article.user-2 {

		}
		.message-window article.anonymous {

		}

		.message-window article.is-emoji {
			background-color: transparent;
			font-size: 5rem;
			text-align: right;
			padding: 0;
			margin: 1rem;
		}
		.message-window article.is-emoji h2 {
			display: none;
		}

		.message-window article.is-emoji p {
			margin: 0;
			padding: 0;
		}

		#message-box {
			position: fixed;
			bottom: 0;
			left: 0;
			right: 0;
			height: 10em;
			background: var(--themecolor8);
			box-shadow: 0px -2px 10px var(--themecolor1);


		}
		#message {
			bottom: 0;
			
			position: absolute;
			left: 0;
			top: 0;
			width:  -webkit-fill-available;
			background: var(--themecolor7);
			margin: 1em;
			margin-right: calc( 20vw + 1em);
			
			border-radius: 1em;
			border:  0;
			padding: 1em;
			resize: none;
			box-shadow: inset 0px -2px 10px 1px var(--themecolor1);

		}
		#sendbutton {
		    position: absolute;
		    left: 80vw;
		    top: 0;
		    right: 0;
		    bottom: 0;
		    font-size: 1rem;
		    width: calc( 20vw - 2em);
		    margin: 1em 1em 2em 0em;
		}

		.no-messages {
			color:  white;
			opacity: 0.8;
		}
		#userform {
			text-align: center;
		}
		textarea {
			font-size:  1rem;
		}

		#last {
			height: calc(auto / 2)
		}
	</style>
	<link rel="stylesheet" href="mp.css">
</head>
<body>
	 <?php echo printmymsg ($msgs); ?>

	 <?php if($userme!='') { ?>
    <form id="message-box" action="#last" method="POST">
	
    <textarea placeholder="<?php echo $userme; ?>" autofocus name="message"  id="message"  ></textarea>
    <button type="submit" name="sendbutton" id="sendbutton" value="send">send</button>
    </form>
    <script>
		//get timestamp

		//start poll

			//if poll true, fetch bare
				//replace messagewindow with result
				//scroll to bottom if necessary
				//get new timestamp




		// https://davidwalsh.name/command-enter-submit-forms
		document.querySelector('#message').addEventListener('keydown', function(e) {
			if(e.keyCode == 13 && e.metaKey) {
				this.form.submit();
			}
		});

    </script>
	<?php } ?>
</body>
</html>