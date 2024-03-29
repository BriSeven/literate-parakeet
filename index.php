<?php
global $userme, $filename, $filemtime, $backlink, $forwardlink;

// set the default timezone to use.
date_default_timezone_set('Australia/Sydney');



if(array_key_exists('back', $_GET) && $_GET['back'] != '' ) {
	//parse number. 
	$back=intval($_GET['back']);
} else {
	$back = 0;
}


$time = mktime() - $back*86400 ;
$backlink = preg_replace("/&back=[0-9]+/",'',$_SERVER['QUERY_STRING']);

$forwardlink = '';

if($back == 1) {
	$forwardlink = '?' . $backlink;
} else if ($back > 0) {
	$forwardlink = '?' . $backlink . '&back=' . ($back-1);
} 


$backlink = '?' . $backlink . '&back=' . ($back+1);


$thisurl = '?' . $_SERVER['QUERY_STRING'];

$isodatetime = date(DATE_ATOM, $time);
$isodate = date('Y-m-d', $time);

$dow = date('l', $time);

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
		if( array_key_exists($key, $semisplit) && 
			array_key_exists(0, $semisplit[$key]) &&
			array_key_exists(1, $semisplit[$key])
		) {

			$results[$semisplit[$key][0]] = $semisplit[$key][1];
		}
		
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
	// echo print_r($_SERVER);
	header('Location: '.$_SERVER['HTTP_REFERER']."#last");
	

}

if(file_exists( $filename )) {
	$msgs = file_get_contents( $filename );
	$msgs = preg_split("/(^---+\n?)|(\n---+\n?)/",$msgs);
	$filemtime = filemtime($filename);
} else {
	$msgs = array();
}

global $users, $usercounter;
$users = array();
$usercounter = 0;

function parsemsg ($msg) {
		// todo: replace globals with a setting fetcher/setter method.
		global $userme, $users, $usercounter;

		$msgarray = explode("\n",$msg);
		$firstline = array_shift($msgarray);
		$params = parseparams($firstline);
		
		
		$date =(array_key_exists('timestamp',$params))  ?  strtotime($params['timestamp']): mktime();
		$user = (array_key_exists('user',$params))  ?  $params['user']: 'anonymous';
		

		$time = '';
		if($date) {
			$time =  date('g:i:s a', $date);
		}
		

		$msg = trim(implode("\n",$msgarray));

		// treat emoji specially
		$is_emoji = '';
		if(mb_strlen(html_entity_decode($msg)) <= 5) {
			$is_emoji = 'is-emoji';
		}
		// assign users colors
		if(!array_key_exists($user,$users)){
			$users[$user] = $usercounter;
			$usercounter += 1;
		}

		$userclass = 'user-' . (($users[$user] % 6) + 1);

		$userclass = $userme == $user ? "user-me" : $userclass;

		$params['user']      	 = $user;
		$params['is_emoji']  	 = $is_emoji; 
		$params['userclass'] 	 = $userclass; 
		$params['timestamp'] 	 = $date; 
		$params['time']      	 = $time; 
		$params['msg']       	 = trim($msg);
		$params['msg_length']	 = mb_strlen(html_entity_decode($msg)) ;

		return $params;


}

function printmymsg ($msgs) {
	global $dow;
	 $txt = '';
	 $nav = '';
	 $count = 0;
	 global $userme, $filemtime, $filename, $backlink, $forwardlink;
	 
	 if($userme == '') { 
		$txt .= "<form id=\"userform\" action=\"#last\" method=\"GET\">
		<input name=\"user\"  id=\"user\"  >
		<button type=\"submit\" name=\"userset\" id=\"user\" value=\"userset\">set user</button>
		</form>";

	 }
	 if($userme != '') {

		 $txt .= "<section id=\"messagewindow\" data-timestamp=\"$filemtime\" class=\"message-window\">";

		 $nav .= "<a  class=\"btn backlink\" href=\"$backlink\">older</a>";
		 $nav .= "<a  class=\"btn filename\"href=\"$filename\">$filename</a>";
	   
		if($forwardlink) {
			$nav .= "<a class=\"btn forwardlink\" href=\"$forwardlink\">newer</a>";
		}

		$nav = "<nav>$nav</nav>";
	    
		$txt .= $nav; 

		 $txt .= "<h1>$dow</h1>";

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
		} else {
			$txt .= $nav;
		}

		$txt .= '</section>';
	}
	 return $txt;
}

if($bare) {

	echo printmymsg ($msgs);
	die();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0">
	<!-- Set the iOS status bar to black. -->
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<link rel="apple-touch-icon" href="logger.png">

	<title>Literate Parakeet</title>
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
			
			background: var(--themecolor7);
		}
		h1 a {
			color:  var(--themecolor5);
		}

		h1 {
			color:  var(--themecolor6);
			text-align: center;
		}

		nav {
			
			display: flex;
			justify-content: space-between;
		}

		.btn {
			border-top: 3px solid var(--themecolor8);



			background: linear-gradient(0deg,  var(--themecolor6) 0%, var(--themecolor7) 100%);
			padding: 11px 22px;
			opacity: 0.5;


			box-shadow:  var(--themecolor1) 0 3px 2px;
			text-shadow: var(--themecolor3) 0 -1px 0;
			color: black;
			font-size: 15px;
			font-family: 'Lucida Grande', Helvetica, Arial, Sans-Serif;
			text-decoration: none;
			vertical-align: middle;
		}

		.btn:hover {
			/*border-top-color: #28597a;*/
			background: var(--themecolor7);
			
		}
		.btn:active {
			box-shadow:  var(--themecolor1) 0 1px 1px;
			transform: translate(0px,2px);
			
		}

		.btn {	
		    
		    border-radius: 1rem;
		    
		    padding: 1rem;
		    position: relative;
		    z-index: 1;

		}

		.btn:visited {
			color:  var(--themecolor5);
		}
		.message-window {
			margin:  0;
			display: flex;
			padding: 1em 1em 11rem 1em;
			box-sizing: border-box;
			background: var(--themecolor7);
			font-family: sans-serif;
			min-height: calc( 100vh - 10em ) ;
			justify-content: start;
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
			border-radius: 1em 1em 1em 0em;
			margin: .5em;
			padding: 1em;
			position: relative;

		}
		.message-window article h2 {
			font-size: .8em;
			opacity: 0.5;
			position: absolute;
			bottom: -.5em;
			right: .8em;
		}



		/* theme */
		.message-window article.user-me {
			background-color: var(--themecolor4);
			color: var(--themecolor8);
			margin-left: 20vw;
			margin-right: .5em;
			border-radius: 1em 1em 0em 1em;
		}

		.message-window article.user-1 {
			background-color: var(--themecolor5);
			color: var(--themecolor8);
			margin-right: 20vw;
			margin-left: .5em;
		}
		.message-window article.user-2 {
			background-color: var(--themecolor3);
			color: var(--themecolor8);
			margin-right: 21vw;
			margin-left: calc(-1vw + .5em);
		}
		.message-window article.anonymous, .message-window article.user-3 {
			background-color: var(--themecolor2);
			color: var(--themecolor5);
			margin-right: 19vw;
			margin-left:  calc( 1vw + .5em);
		}
		.message-window article.user-4 {
			background-color: var(--themecolor1);
			color: var(--themecolor8);
			margin-right: 18vw;
			margin-left:  calc( 2vw + .5em);
		}
		.message-window article.user-5 {
			background-color: var(--themecolor6);
			color: var(--themecolor8);
			margin-right: 17vw;
			margin-left:  calc( 3vw + .5em);
		}
		.message-window article.user-6 {
			background-color: var(--themecolor8);
			color: var(--themecolor5);
			margin-right: 16vw;
			margin-left:  calc( 4vw + .5em);
		}

		.message-window article.is-emoji {
			background-color: transparent;
			font-size: 5rem;
			text-align: right;
			padding: 0;
			margin: 1rem;
			color: var(--themecolor5);
		}
		article.user-me + article.user-me,
		article.user-1  + article.user-1 ,
		article.user-2  + article.user-2 ,
		article.user-3  + article.user-3 ,
		article.user-4  + article.user-4 ,
		article.user-5  + article.user-5 ,
		article.user-6  + article.user-6 
		{
			margin-top: -1.0em;
			box-shadow: 0px -3px 0px 0px rgba(0, 0, 0, 0.2);
		}

		article.is-emoji + article.is-emoji ,
		article.user-me  + article.user-me.is-emoji, article.user-me.is-emoji + article.user-me,
		article.user-1   + article.user-1.is-emoji,  article.user-1.is-emoji  + article.user-1 ,
		article.user-2   + article.user-2.is-emoji,  article.user-2.is-emoji  + article.user-2 ,
		article.user-3   + article.user-3.is-emoji,  article.user-3.is-emoji  + article.user-3 ,
		article.user-4   + article.user-4.is-emoji,  article.user-4.is-emoji  + article.user-4 ,
		article.user-5   + article.user-5.is-emoji,  article.user-5.is-emoji  + article.user-5 ,
		article.user-6   + article.user-6.is-emoji,  article.user-6.is-emoji  + article.user-6 
		{
			margin-top: 1rem;
			box-shadow: none;
		}

		.message-window article.is-emoji.user-1,
		.message-window article.is-emoji.user-2,
		.message-window article.is-emoji.user-3,
		.message-window article.is-emoji.user-4,
		.message-window article.is-emoji.user-5,
		.message-window article.is-emoji.user-6 {
			text-align: left;
		}
		.message-window article.is-emoji h2 {
			opacity: 1;
			font-size: .5rem;
			position: relative;
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
			z-index: 2;

		}
		#message {
			bottom: 0;
			position: absolute;
			left: 0;
			right: 25vw;
			top: 0;
			-webkit-appearance: none;
			background: var(--themecolor7);
			margin: 1em;
			border-radius: 1em 1em 0em 1em;
			border: 0;
			padding: 1em;
			resize: none;
			box-sizing: border-box;
			box-shadow: inset 0px -2px 10px 1px var(--themecolor1);
		}
		#sendbutton {
			position: absolute;
			left: 75vw;
			right: 0;
			bottom: 0;
			font-size: 1rem;
			margin: 1em 2em 2em 0em;
			box-sizing: border-box;
			top: 0;
		}

		@media (max-width: 398px) {
			#sendbutton {
				font-size: .8rem;
			}
			
		}
		.no-messages {
			color:  white;
			opacity: 0.8;
			justify-content: center;
			
			display: flex;
			bottom: 10rem;
			position: absolute;
			left: 0;
			right: 0;
			top: 0;
			align-items: center;
			pointer-events: none;
		}
		#userform {
			text-align: center;
		}
		textarea {
			font-size:  1rem;
		}

		#last {
			height: calc(auto / 2);
			transform: translate(0,200px);
			transition: transform 200ms ease-in-out;
		}
		#last.animate {
			
			
			transform: translate(0,0);
		}

	</style>
	<link rel="stylesheet" href="mp.css">
</head>
<body>
	 <?php echo printmymsg ($msgs); ?>

	 <?php if($userme!='') { ?>
    <form id="message-box" action="#last" method="POST">
	
    <textarea placeholder="<?php echo $userme; ?>" autofocus name="message"  id="message"  ></textarea>
    <button type="submit" name="sendbutton" id="sendbutton" value="send" ontouchstart>send</button>
    </form>
    <script>
		//get timestamp
		let timestamp = +document.querySelector('.message-window').dataset.timestamp;

		
		let localtimestamp = Date.now()/1000;
		let updateInterval = 0;

		const sinceLastMod = (t=timestamp) => (Date.now()/1000 - (localtimestamp - t) - t);
		const secondsSinceLoad = () => (Date.now()/1000 - localtimestamp);


		let pollInterval = 5000;
		let pollMax = 60000*5;
		let isLeader = false;
		const backlink = "<?=$backlink?>";
		const thisurl = "<?=$thisurl?>";
		
		setTimeout(()=>{
			document.querySelector('#last').classList.add("animate");
		},10);
		

		setTimeout(function poll (){
			/* make multiple open tabs less mean */
			

			if( localStorage.getItem('windowID') ){
				if( +localStorage.getItem('windowID') < localtimestamp ) {
					localStorage.setItem('windowID', localtimestamp);
					console.log('i am the leader');
					isLeader = true;
					pollInterval = 5000;
					pollMax = 60000*5;
				} else if( +localStorage.getItem('windowID') === localtimestamp ) {
					console.log('i am still the leader');
				} else {
					console.log('i am the follower');
					isLeader = false;
					pollMax = 60000*30;
					pollInterval = 60000*5;
				}
			}  else {
				localStorage.setItem('windowID', localtimestamp);
			};

		//start poll
			
			//if poll true, fetch bare
				//replace messagewindow with result
				//scroll to bottom if necessary
				//get new timestamp

			fetch(thisurl+"&poll=poll")
			.catch((x)=>{
				pollInterval*=1.4142135624;
				pollInterval = pollInterval > pollMax ? pollMax : pollInterval;
				setTimeout(poll,pollInterval);
			})
			.then((x)=>x.text())
			.then((x)=>(+x))
			.then(function(lastmod){


					
				if( lastmod > timestamp) {

					fetch(location.search + "&bare=bare")
					.then((x)=>x.text())
					.then((html)=>{
						document.querySelector('.message-window').outerHTML=html;
						
					})
					.then(()=>{
						let last = document.querySelector('#last');
						if(last) { 
							last.scrollIntoView({behavior: 'smooth'});
							last.classList.add("animate");
						}
					});

					if(updateInterval === 0) {
						updateInterval=lastmod-timestamp;
					} else {
						updateInterval = updateInterval*0.5 + (lastmod-timestamp)*0.5;
					}
					timestamp = lastmod;

					pollInterval=1000;
					setTimeout(poll,pollInterval);
				} else {
					pollInterval*=1.4142135624;
					pollInterval = pollInterval > 60000*5 ? 60000*5 : pollInterval;
					setTimeout(poll,pollInterval);
				}
			});
			

		},pollInterval);



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