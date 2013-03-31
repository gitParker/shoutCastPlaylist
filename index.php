<?php
ini_set('user_agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/3.7.0.9');

define('RADIO_FILE', '/net/kyle/musicDocs/radio.m3u');
define('URL_FILE', '/net/kyle/musicDocs/urls.txt');

if ($argv[1] == 'generate') {
	echo "Starting to regenerate " . RADIO_FILE;
	createPlaylist();
	echo "Done";
	exit(0);
}

if ($_REQUEST['updatePlaylist']) {
	updateUrls();
	createPlaylist();

} elseif (isset($_POST['shutdown'])) {
	print "Shuttting Down<br/>";
	print `super shutdown -h now 2>&1`;

} elseif (isset($_POST['restart'])) {
	print "Restarting<br/>";
	print `super restart -r now 2>&1`;
}
drawPage();

function drawPage() {
	?>
	<html>
	<head>
		<title>Digital Boombox Control</title>
		<style>
			body
			{color:white; background-color:black; font-size:large; }
			th { font-size:large; }
			input{ font-size:large; }
			input.des { width: 300px; }
			input.url { width: 500px; }
		</style>
	</head>
	<body>
	<form method="POST">
	<input type="submit" value="Create Radio Playlist" name="updatePlaylist" id="updatePlaylist"/>
	<br/><br/>

	<table>
	<tr><th></th><th>Description</th><th>URL</th></tr>
	<?
	$lines = file(URL_FILE);
	for ($i=0; $i<count($lines); $i+=2){
		$disp = $i/2 + 1;
		?>
		<tr>
			<td><?=$disp?></td>
			<td placeholder='Description'><input type='text' class='des' name='names[]' id='names<?=$disp?>' value='<?=substr(rtrim($lines[$i]),1)?>'/></td>
			<td placeholder='URL'><input type='text' class='url' name='urls[]' id='urls<?=$disp?>' value='<?=rtrim($lines[$i+1])?>'/></td>
		</tr>
		<?
	}
	for ($i=$disp+1; $i<$disp+3; $i++) {
		?>
		<tr><td rowspan='1'><?=$i?></td>
			<td placeholder='Description'><input type='text' class='des' name='names[]' id='names<?=$i?>' placeHolder="Description"/></td>
			<td placeholder='URL'><input type='text' class='url' name='urls[]' id='urls<?=$disp?>' placeHolder="URL"/></td>
		</tr>
		<?
	}


	?>
	</table>
	<br/><br/>
	<input type='submit' name='shutdown' value="Shutdown"/>
	<input type='submit' name='restart' value="Restart"/>
	</form>
	</body></html>
	<?
}

function updateUrls() {
	$output = "";
	for ($i=0; $i<count($_REQUEST['names']); $i++) {
		$name = $_REQUEST['names'][$i];
		$url = $_REQUEST['urls'][$i];
		if ($name && $url) {
			$output .= "#${name}\n${url}\n";	
		}
	}
	if ($output) {
		if (file_put_contents(URL_FILE, $output)) {
			echo "Saved URLs<br/>\n";
		} else {
			echo "Could Not Save Urls<br/>\n";
		}
	} else {
		echo "No URLs to save<br/>\n";
	}
}


function createPlaylist() {
	$playlist = "";
	$lines = file(URL_FILE);
	foreach ($lines as $lineNum => $line) {
		if ($line[0] == '#') { continue; }
		$playlistLines = file($line);
		$url = "";

		foreach ($playlistLines as $lNum => $curUrl) {
			if (preg_match("!File\d=(http://\d+.*)!i", $curUrl, $groups)) {
				$url = $groups[1];
				break;
			} else {
				continue;
			}
		}
		if ($url) {
			$playlist .= $url . "\n";
		} else {
			echo "error: couldn't find the first stream URL: ${line}<br/>\n";
		}
	}

	if ($playlist) {
		if (file_put_contents(RADIO_FILE, $playlist)) {
			echo "Recreated radio.m3u<br/>\n";
		} else {
			echo "Error: could not create radio.m3u<br/>\n";
		}
	} else {
		echo "No playlist to create: ${playlist}<br/>\n";
	}
}

?>
