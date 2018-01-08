<?php
function getCurrentUri()
{
    $basepath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
    $uri = substr($_SERVER['REQUEST_URI'], strlen($basepath));
    if (strstr($uri, '?')) $uri = substr($uri, 0, strpos($uri, '?'));
    $uri = '/' . trim($uri, '/');
    
    $uri = $_SERVER['REQUEST_URI'];
    return $uri;
}

define("CURRENT_URI", getCurrentUri());

/** self-made routing class by @kalioz
* use for simple cases only, this routing does NOT function on a MVC system
*
**/
class Route{
    private $_path;
    private $_function;

    /** construct the route
    * @param path the path the uri must check. ex : /some_path/{variable}
    * @param function the function called if the path works. the variables {} from the path will be passed to the function
    *
    * exemple : path = /path/{}/{var2}
    * works for : /path/variable1/variable2
    * and calls the function with : f($variable1, $variable2, array(0 => variable1, 1=> variable2, "var2"=>variable2))
    **/
    public function __construct($path, $function){
        $this->_path = $path;
        $this->_function = $function;

        if (! isset($GLOBALS["list_containing_all_routes"])){
            $GLOBALS["list_containing_all_routes"] = array();
            register_shutdown_function(function(){
                global $list_containing_all_routes;

                $uri = CURRENT_URI;

                for ($i = 0; $i < count($list_containing_all_routes); $i++){
                    $route = $list_containing_all_routes[$i];
                    if ($route->check($uri)){
                        $route->execute($uri);
                        break;
                    }
                }

                //todo : raise 404 if no parameters fit the argument

            });

        }
        array_push($GLOBALS["list_containing_all_routes"], $this);
    }

    /*return the regex to get the arguments from the uri*/
    public function get_regex(){
        $path_regexed = $this->_path;
        $path_regexed = preg_replace('/{[^}\\/]*}/', "([^/]*)", $path_regexed);//remplace les expressions sans nom
        $path_regexed = str_replace("/","\\/", $path_regexed);

        $path_regexed = "/^".$path_regexed."$/";
        return $path_regexed;
    }

    /*return the regex to get all the arguments from the uri*/
    public function get_regex_all(){
        $path_regexed = $this->_path;
        $path_regexed = preg_replace('/{([^}\/]+)}/', "(?P<$1>[^/]*)", $path_regexed);//remplace les expressions possédants un nom
        $path_regexed = preg_replace('/{[^}\\/]*}/', "([^/]*)", $path_regexed);//remplace les expressions sans nom
        $path_regexed = str_replace("/","\\/", $path_regexed);

        $path_regexed = "/^".$path_regexed."$/";
        return $path_regexed;
    }

    /*return a boolean indicating if this route corresponds to the $uri*/
    public function check($uri){
        $path_regexed = $this->get_regex();
        return preg_match($path_regexed, $uri);
    }

    /**return all arguments of the uri in a list  style 
    * e.g. : /{un}/{}/some_text/{deux} <=> /x/y/some_text/z
    * return {0:x,1:y,2:z}
    **/
    public function get_arguments($uri){
        preg_match($this->get_regex(), $uri, $output);
        $n = count($output);
        for ($i = 1; $i <=$n ; $i++){
            if (array_key_exists($i, $output) == 1){
                $output[$i-1] = $output[$i];
            }else{
                unset($output[$i-1]);
                break;
            }
        }
        return $output;
    }

    /**return all arguments of the uri in a list and dic style 
    * e.g. : /{un}/{}/some_text/{deux} <=> /x/y/some_text/z
    * return {0:x,1:y,2:z,"un":x,"deux":y}
    **/
    public function get_arguments_all($uri){
        preg_match($this->get_regex_all(), $uri, $output);
        $n = count($output);
        for ($i = 1; $i <=$n ; $i++){
            if (array_key_exists($i, $output) == 1){
                $output[$i-1] = $output[$i];
            }else{
                unset($output[$i-1]);
                break;
            }
        }
        return $output;
    }

    /*return the names of the arguments*/
    public function get_arguments_name(){
        $re = '/{([^}\/]*)}/';
        preg_match_all($re, $this->_path, $match);
        if (count($match)){
            return $match[1];
        }
        return $match;
    }

    public function execute($uri){
        if (! $this->check($uri)){
            die("Error - bad correspondance");
        }

        $arguments = $this->get_arguments($uri);

        $arguments_all = $this->get_arguments_all($uri);
        array_push($arguments, $arguments_all);

        $f = $this->_function;
        $f(...$arguments);
    }
}

/*shortcut to create a route*/
function route(...$args){return new Route(...$args);}
?>