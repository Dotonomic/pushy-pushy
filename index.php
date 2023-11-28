<!DOCTYPE HTML>
<?php
    session_start();
    if (!empty($_POST['key'])) $_SESSION['key'] = $_POST['key'];
    if (!isset($_SESSION['model'])) $_SESSION['model'] = "gpt-4-1106-preview";
    if (isset($_SESSION['key'])) echo "<script>var key = true;</script>";
    else echo "<script>var key = false;</script>";
?>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

.loader-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: #C0C0C0;
  display: none;
}

.loader {
  border: 16px solid #f3f3f3;
  border-top: 16px solid #3498db;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  animation: spin 1s linear infinite;
  position: absolute;
  top: 50%;
  left: 50%;
  margin-left: -38px; /* half its width (border included) */
  margin-top: -38px; /* half its height */
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.button {
  background-color: #ff00ff;
  color: black;
  padding: 15px 32px;
  text-align: center;
  font-size: 24px;
  cursor: pointer;
}

.button2 {
  padding: 2px 2px;
  text-align: center;
  font-size: 17px;
  cursor: pointer;
}
</style>
</head>

<body>
<center>
    
<br><br>
<form id='needs-validation' method='post'>
<input type='hidden' name='newgame' value=''>
<div id="keycontainer">
<?php
    if (empty($_SESSION['key'])) {
        echo '<strong style="font-size: 20px">OpenAI API Key<br><input type="text" name="key" id="key" value=""></strong><br><br><br>';
        unset($_POST['newgame']);
    }
    else echo '<button class="button2" type="button" onclick="removeKey()">REMOVE API KEY</button><br><br><br>';
?>
</div>
<strong style="font-size: 20px" id="modeldisplay"><?=$_SESSION['model']?></strong><br><button class="button2" type="button" onclick="changeModel()">CHANGE</button><br><br><br>
<?php
    if (isset($_SESSION['shorterrormessage'])) {echo $_SESSION['shorterrormessage']."<br><br>"; unset($_SESSION['shorterrormessage']);}
    if (!isset($_POST['newgame'])) echo "<button class='button' type='submit'>CREATE A GAME</button><br><br><div class='loader-container'><div class='loader'></div></div>";
?>
</form><br><br>

<script>
function removeKey(){
    const xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
	    if (this.readyState == 4 && this.status == 200) {
		    document.getElementById('keycontainer').innerHTML = '<strong style="font-size: 20px">OpenAI API Key<br><input type="text" name="key" id="key" value=""></strong><br><br><br>';
		    key = false;
	    }
    };
    xmlhttp.open('GET','removekey.php');
    xmlhttp.send();
}

function changeModel(){
    const xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
	    if (this.readyState == 4 && this.status == 200) {
		    document.getElementById('modeldisplay').innerHTML = xmlhttp.responseText;
	    }
    };
    xmlhttp.open('GET','changemodel.php');
    xmlhttp.send();
}

var myForm = document.getElementById('needs-validation');
myForm.addEventListener('submit', showLoader);
function showLoader(e){
    this.querySelector('.loader-container').style.display = 'block';
}
</script>
</center>
</body>
</html>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' & !empty($_SESSION['key'])) {
    
    if (isset($_POST['redo'])) $type = $_SESSION['type'];
    else {
        switch (rand(0,6)) {
            case 0 : $type = "a game in JavaScript"; break;
            case 1 : $type = "an action game in JavaScript."; break;
            case 2 : $type = "a puzzle game in JavaScript."; break;
            case 3 : $type = "an adventure game in JavaScript"; break;
            case 4 : $type = "a strategy game in JavaScript"; break;
            case 5 : $type = "in JavaScript a game somehow based on (or implementing) the concept of cellular automaton."; break;
            default : $type = "in JavaScript a game which takes place on a lattice or grid. The lattice/grid may or may not be infinite. Either a game of type 1 or type 2. Type 1: the user controls a character that can move within the lattice. You may also implement other character actions. Type 2: there is no character, the user is able to perform some action or actions.";
        }
    }

    if (rand(0,1)) $timed = "Implement a time constraint and display the timer. ";
    else $timed = "";

    $prompt = "Fully implement ".$type." Be creative, try to come up with original and unique gameplay (this is very important). The game can neither be too easy to win nor too hard to lose, this is very important. ".$timed."Reply with a html document (complete with CSS styling) that includes game instructions for the user and a very creative title for the game. Include the option to reset/restart the game at any time, and also after winning or losing. You may use emojis, both in the instructions and in the game itself, but it is not mandatory. The code must be fully functional, you must implement all the features and not ommit any logic. The game must be playable both on computers and on mobile devices.";

    require 'vendor/autoload.php';
    $userApiKey = $_SESSION['key'];
    $client = OpenAI::client($userApiKey); // https://github.com/openai-php/client
    
    $messages = [['role' => 'system', 'content' => $prompt]];

    if (isset($_POST['redo'])) {
        $prompt1 = "Examine the code included in the text, do you foresee any bugs (including visual bugs)? Explain very briefly. Text: ".$_SESSION['result']." Also, do you foresee any failure to fulfill the specifications? Explain very briefly. Specifications: ".$prompt;
        
        try {    
            $result1 = $client->chat()->create([
                 'model' => $_SESSION['model'],
                 'messages' => [['role' => 'system', 'content' => $prompt1]],
	        ]);
	        $feedback = $_POST['userFeedback']."\n\n".$result1['choices'][0]['message']['content'];
	        file_put_contents($_SESSION['path']."_EVAL.txt",$feedback);
    
            $messages[] = ['role' => 'assistant', 'content' => $_SESSION['result']];
            $messages[] = ['role' => 'system', 'content' => 'Make it better, even if the feedback is positive. Reply with the full new html document, do not abbreviate by referencing parts of the previous document. Feedback: '.$feedback];
        } catch (Exception $e) {
            echo preg_replace("~in \/home(.|\n)*~i","",$e)."<br><br>";
        }
        
	    $buttonText = "MAKE IT BETTER";
	    $userFeedbackBox = "<br><br><strong style='font-size: 18px'>Optional feedback/suggestions:</strong><br><textarea name='userFeedback' rows='3' cols='40' maxlength='400' value=''></textarea>";
    }
    else {
        $buttonText = "GO ON...";
        $userFeedbackBox = "";
    }

    $path = 'games/'.date("Y-m-d H:i:s");
    
    try {
        $result = $client->chat()->create([
             'model' => $_SESSION['model'],
             'messages' => $messages,
        ]);
        file_put_contents($path.".txt",$result['choices'][0]['message']['content']);
    } catch (Exception $e) {
        $shortErrorMessage = preg_replace("~in \/home(.|\n)*~i","",$e);
        if (isset($_POST['newgame'])) {
            $_SESSION['shorterrormessage'] = $shortErrorMessage;
            header("Location: /");
            exit();
        }
        echo $shortErrorMessage;
    }
?>	

<html>
<body>
<center>
<br><br>
<form method='post' onsubmit='return showLoaderOrPreventSubmit()'>
<input type='hidden' name='redo' value=''>
<input type='hidden' name='key' value='' id='newkey'>
<button class='button' type='submit'><?=$buttonText?></button><?=$userFeedbackBox?><div class='loader-container'><div class='loader'></div></div>
</form><br><br>

<script>
function showLoaderOrPreventSubmit(){
    var newkey = '';
    try {
        newkey = document.getElementById('key').value;
    }
    catch {
    }
    finally {
        document.getElementById('newkey').value = newkey;
    }
    if (key || newkey) {document.querySelector('.loader-container').style.display = 'block'; return true;}
    else return false;
}
</script>
</center>
</body>
</html>

<?php
    if (isset($result)) {
        $_SESSION['result'] = $result['choices'][0]['message']['content'];
        if (isset($_POST['redo'])) {
    	    $content = preg_replace("/(.|\n)*```html/","",$result['choices'][0]['message']['content']);
    	    $content = preg_replace("/```(.|\n)*/","",$content);
    	    echo $content;
        }
    }
    if (isset($type)) $_SESSION['type'] = $type;
    if (isset($path)) $_SESSION['path'] = $path;
}
?>
<html>
<body>
<script>window.scrollTo(0, document.body.scrollHeight);</script>
</body>
</html>