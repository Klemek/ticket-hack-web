<?php
/** Routing class for PHP
* enable to quickly create routes 
* ex : $route = new Route();
*      $route->route("/modele/{vue}/{controller}", function(vue, controller_or_other_name){}); to get all requests of the type /modele/(.*)/(.*) using this function
*      $route->post("/modele2/{vue}", function(vue){}); to get only the POST requests; POST/GET/DELETE/PUT
*      $route->error_404(function(){}); to set an error if no route correspond to the current url
*      $route->route("path",function(){},array("POST","PUT")) to execute the path on POST and PUT
*      $route->route(array(path1,path2,...),function, method) will route all paths to the method. please take care about possible missing arguments between paths. works for route, get, put, post and delete.
*
*      note the path must correspond to $_SERVER['REQUEST_URI']
*      only the first corresponding route will be activated; all next one will be ignored.
**/
class Route{

    public $_current_uri;
    private $_route_used;
    private $_errors;

    /*construct the Route using an uri; */
    public function __construct(){
        $this->_routes = array();

        $this->_current_uri = Route::getCurrentUri();

        $this->_route_used = null;

        $this->_errors = array();

        register_shutdown_function(array($this,"on_shutdown"));

    }

    /**
    * return the current URI
    * ex : https://my_website.com/path/to/folder?arg1&arg2 => path/to/folder
    **/
    public static function getCurrentUri()
    {
        $uri = $_SERVER['REQUEST_URI'];
        
        $re = '/([^?]*)\??.*/';
        $str = $uri;
        $subst = '$1';

        $result = preg_replace($re, $subst, $str);
        return $result;
    }

    /*function called on shutdown*/
    public function on_shutdown(){
        if ($this->_route_used === null){
            $errors = $this->_errors;
            if (array_key_exists(404, $errors)){
                $f = $errors[404];
                $f();
            }
        }

        exit;
    }

    /*execute the function in case of File Not Found error*/
    public function error_404($function){
        $this->_errors[404] = $function;
    }

    /*take care of GET requests only*/
    public function get($path, $function){
        if ($_SERVER['REQUEST_METHOD'] === "GET"){
            $this->route($path, $function);
        }
    }

    /*take care of POST requests only*/
    public function post($path, $function){
        if ($_SERVER['REQUEST_METHOD'] === "POST"){
            $this->route($path, $function);
        }
    }

    /*take care of PUT requests*/
    public function put($path, $function){
        if ($_SERVER['REQUEST_METHOD'] === "PUT"){
            $this->route($path, $function);
        }
    }

    /*take care of delete requests*/
    public function delete($path, $function){
        if ($_SERVER['REQUEST_METHOD'] === "DELETE"){
            $this->route($path, $function);
        }

    }

    /*take care of all cases
    @param $method = list containing the types authorized. keep null to authorize all*/
    public function route($path, $function, $methods = null){

        if ($methods !== null){
            $continue = false;
            foreach($methods as $method){
                $continue |= strtoupper($method) === $_SERVER['REQUEST_METHOD'];
            }

            if (! $continue){
                return;
            }
        }

        if (is_array($path)){
            foreach($path as $p){
                $this->route($p, $function, $methods);
            }
            return;
        }

        if (! $this->_route_used && $this->check($path)){
            $this->_route_used = $path;

            $args = $this->get_arguments_indexed_only($path);
            array_push($args,$this->get_arguments($path));//enable us to access directly by keyword

            if (count($args) === 1){//no arguments on the url, there's only the list containing everything
                $args = array();
            }

            $function(...$args);
        }
    }

    /*private functions*/

    /*check if the path correspond to the current uri*/
    private function check($path){
        $regex = $this->get_regex($path);
        $uri = $this->_current_uri;
        return preg_match($regex, $uri);
    }

    /**return all arguments of the uri in a list and dic style 
    * e.g. : /{un}/{}/some_text/{deux} <=> /x/y/some_text/z
    * return {0:x,1:y,2:z,"un":x,"deux":y}
    **/
    private function get_arguments($path){
        if (! $this->check($path)){
            die("Error - Bad correspondance");
        }

        $regex = $this->get_regex_args($path);
        preg_match($regex, $this->_current_uri, $output);
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

    private function get_arguments_indexed_only($path){
        if (! $this->check($path)){
            die("Error - Bad correspondance");
        }

        preg_match($this->get_regex($path), $this->_current_uri, $output);
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

    /*extrapolate the regex from the path - this regex checks wether or not the path correspond to the URI*/
    private function get_regex($path){
        $path_regexed = $path;
        $path_regexed = preg_replace('/{[^}\\/]*}/', "([^/]*)", $path_regexed);//remplace les expressions sans nom
        $path_regexed = str_replace("/","\\/", $path_regexed);

        $path_regexed = "/^".$path_regexed."$/";
        return $path_regexed;
    }

    /*get the regex to extrapolate the arguments from a path*/
    private function get_regex_args($path){
        $path_regexed = $path;
        $path_regexed = preg_replace('/{([^}\/]+)}/', "(?P<$1>[^/]*)", $path_regexed);//remplace les expressions possédants un nom
        $path_regexed = preg_replace('/{[^}\\/]*}/', "([^/]*)", $path_regexed);//remplace les expressions sans nom
        $path_regexed = str_replace("/","\\/", $path_regexed);

        $path_regexed = "/^".$path_regexed."$/";
        return $path_regexed;
    }
}
?>