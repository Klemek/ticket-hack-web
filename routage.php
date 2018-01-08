<?php
function getCurrentUri()
{
    $basepath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
    $uri = substr($_SERVER['REQUEST_URI'], strlen($basepath));
    if (strstr($uri, '?')) $uri = substr($uri, 0, strpos($uri, '?'));
    $uri = '/' . trim($uri, '/');
    return $uri;
}

define("CURRENT_URI", getCurrentUri());

class Route{
    private $_path;
    private $_function;

    public function __construct($path, $function){
        $this->_path = $path;
        $this->_function = $function;
    }

    /*retourne la complexité de la route; sir deux routes peuvent être utilisée, la route la moins élevée sera choisie*/
    public function complexite(){
        $c = preg_replace("[^/{}]");
        
    }
    
    public function get_regex(){
        $path_regexed = $this->_path;
        $path_regexed = preg_replace('/{[^}\\/]*}/', "([^/]*)", $path_regexed);
        $path_regexed = str_replace("/","\\/", $path_regexed);

        $path_regexed = "/^".$path_regexed."$/";
        return $path_regexed;
    }
    
    public function check($uri){
        $path_regexed = $this->get_regex();
        return preg_match($path_regexed, $uri);
    }
    
    public function get_arguments($uri){
        
    }
    
    public function execute($uri){
        if (! $this->check($uri)){
            die("Error - bad correspondance");
        }
        
        
    }
}

$routes = array();

/*create a route*/
function route($path, $function){    
    //calcul du path
    $path_stripped = explode("/", $path);

    $uri_stripped = explode("/", CURRENT_URI);

    if (count($uri_stripped)!=count($path_stripped)){
        //return;
    }

    // path/to/{my dir} => path/to/([])
    $path_regexed = $path;
    $path_regexed = preg_replace('/{[^}\\/]*}/', "([^/]*)", $path_regexed);
    $path_regexed = str_replace("/","\\/", $path_regexed);

    $path_regexed = "/^".$path_regexed."$/";

    if (preg_match($path_regexed, CURRENT_URI)){
        echo "Success ! ";
    }

    return $path_regexed;

    /*check if each part correspond*/
    for ($i = 0; $i < count($uri_stripped); $i++){

    }
}

function test($x){
    echo "$x => ".route($x,function(){})."\n<br/>";
}

test("/test/normal/path");
test("/test/{argument}/suite");
test("/test/prev{arg1}/{arg2}");
test("other/path");
test("/testeur.php");

?>