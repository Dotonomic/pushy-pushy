<!DOCTYPE HTML>
<?php
    session_start();
    if (!empty($_POST['key'])) $_SESSION['key'] = $_POST['key']; //Store/update api key in session if it was entered 
    if (!isset($_SESSION['model'])) $_SESSION['model'] = "gpt-4-1106-preview"; //Default LLM model
    if (isset($_SESSION['key'])) echo "<script>var key = true;</script>"; //Initialize JavaScript control variable 'key' to track presence of api key
    else echo "<script>var key = false;</script>"; //api key not present
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
    if (empty($_SESSION['key'])) { //Display input field for api key
        echo '<strong style="font-size: 20px">OpenAI API Key<br><input type="text" name="key" id="key" value=""></strong><br><br><br>';
        unset($_POST['newgame']); //and unset variable that controls wether CREATE A GAME button was pushed
    } //Or else display 'remove api key' button
    else echo '<button class="button2" type="button" onclick="removeKey()">REMOVE API KEY</button><br><br><br>';
?>
</div>
<!--Display 'change model' button-->
<strong style="font-size: 20px" id="modeldisplay"><?=$_SESSION['model']?></strong><br><button class="button2" type="button" onclick="changeModel()">CHANGE</button><br><br><br>
<?php
    //"Catch" error message if 'shorterrormessage' session variable is set, display it, and unset variable
    if (isset($_SESSION['shorterrormessage'])) {echo $_SESSION['shorterrormessage']."<br><br>"; unset($_SESSION['shorterrormessage']);}
    //Display CREATE A GAME button if it isn't set as pushed
    if (!isset($_POST['newgame'])) echo "<button class='button' type='submit'>CREATE A GAME</button><br><br><div class='loader-container'><div class='loader'></div></div>";
?>
</form><br><br>

<script>
function removeKey(){
    const xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
	    if (this.readyState == 4 && this.status == 200) { //Update 'key container' with input field for api key
		    document.getElementById('keycontainer').innerHTML = '<strong style="font-size: 20px">OpenAI API Key<br><input type="text" name="key" id="key" value=""></strong><br><br><br>';
		    key = false; //and update JavaScrip control variable 'key'
	    }
    };
    xmlhttp.open('GET','removekey.php'); //Unsets session variable 'key'
    xmlhttp.send();
}

function changeModel(){
    const xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
	    if (this.readyState == 4 && this.status == 200) { //Update model name displayed
		    document.getElementById('modeldisplay').innerHTML = xmlhttp.responseText;
	    }
    };
    xmlhttp.open('GET','changemodel.php'); //Toggles LLM model and returns model name
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
    
    if (isset($_POST['redo'])) $type = $_SESSION['type']; //If creating new version of same game, no need to select game type
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
    //Initial system prompt, containing subprompts 'type' and 'timed'
    $prompt = "Fully implement ".$type." Be creative, try to come up with original and unique gameplay (this is very important). The game can neither be too easy to win nor too hard to lose, this is very important. ".$timed."Reply with a html document (complete with CSS styling) that includes game instructions for the user and a very creative title for the game. Include the option to reset/restart the game at any time, and also after winning or losing. You may use emojis, both in the instructions and in the game itself, but it is not mandatory. The code must be fully functional, you must implement all the features and not ommit any logic. The game must be playable both on computers and on mobile devices.";
    
    $messages = [['role' => 'system', 'content' => $prompt]]; //Initialize 'messages' array, the first message is the initial system prompt

    require 'vendor/autoload.php';
    $userApiKey = $_SESSION['key'];
    $client = OpenAI::client($userApiKey); // https://github.com/openai-php/client

    if (isset($_POST['redo'])) { //Creating new version of same game
        //System prompt to examine code and provide feedback
        $prompt1 = "Examine the code included in the text, do you foresee any bugs (including visual bugs)? Explain very briefly. Text: ".$_SESSION['result']." Also, do you foresee any failure to fulfill the specifications? Explain very briefly. Specifications: ".$prompt;
        
        try { //Attempt to prompt LLM    
            $result1 = $client->chat()->create([
                 'model' => $_SESSION['model'],
                 'messages' => [['role' => 'system', 'content' => $prompt1]],
	        ]);
            //Prepend user feedback to LLM reply
	        $feedback = $_POST['userFeedback']."\n\n".$result1['choices'][0]['message']['content'];
	        //Create text file with user + LLM feedback, file name based on the previous game version's file name
            file_put_contents($_SESSION['path']."_EVAL.txt",$feedback);
            //Add message with the previous version of the game to the 'messages' array
            $messages[] = ['role' => 'assistant', 'content' => $_SESSION['result']];
            //Add message with system prompt to request new version based on the feedback 
            $messages[] = ['role' => 'system', 'content' => 'Make it better, even if the feedback is positive. Reply with the full new html document, do not abbreviate by referencing parts of the previous document. Feedback: '.$feedback];
        } catch (Exception $e) { //Display trimmed error message
            echo preg_replace("~in \/home(.|\n)*~i","",$e)."<br><br>";
        }
        
	    $buttonText = "MAKE IT BETTER"; //Set button text, set 'user feedback box' html 
	    $userFeedbackBox = "<br><br><strong style='font-size: 18px'>Optional feedback/suggestions:</strong><br><textarea name='userFeedback' rows='3' cols='40' maxlength='400' value=''></textarea>";
    }
    else { //If creating new game, set different button text and set empty 'user feedback box' html
        $buttonText = "GO ON...";
        $userFeedbackBox = "";
    }
    //Path for text file to dump LLM reply (game code)
    $path = 'games/'.date("Y-m-d H:i:s");
    
    try { //Attempt to prompt LLM with the 'messages' array
        $result = $client->chat()->create([
             'model' => $_SESSION['model'],
             'messages' => $messages,
        ]);
        //Dump to new text file
        file_put_contents($path.".txt",$result['choices'][0]['message']['content']);
    } catch (Exception $e) { //Trim error message
        $shortErrorMessage = preg_replace("~in \/home(.|\n)*~i","",$e);
        if (isset($_POST['newgame'])) { //If the button pushed was CREATE NEW GAME, store trimmed error message in session and start over
            $_SESSION['shorterrormessage'] = $shortErrorMessage;
            header("Location: /");
            exit();
        } //if not, display trimmed error message
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
function showLoaderOrPreventSubmit(){ //Assign key typed in by user (possibly empty) to hidden field 'key' in form
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
    else return false; //If no key is present/stored and no key is typed in, do not submit form
}
</script>
</center>
</body>
</html>

<?php
    if (isset($result)) { //If there is reply (game code) from LLM, store it in session
        $_SESSION['result'] = $result['choices'][0]['message']['content'];
        if (isset($_POST['redo'])) { //and, if creating new version of same game, trim reply and serve game
    	    $content = preg_replace("/(.|\n)*```html/","",$result['choices'][0]['message']['content']);
    	    $content = preg_replace("/```(.|\n)*/","",$content);
    	    echo $content;
        }
    }
    if (isset($type)) $_SESSION['type'] = $type; //Store game type in session
    if (isset($path)) $_SESSION['path'] = $path; //Store text file path
}
?>
<html>
<body>
<script>window.scrollTo(0, document.body.scrollHeight);</script>
</body>
</html>
