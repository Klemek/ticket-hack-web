<?php
/** 
* Route is a routing class for PHP
*
* Route enables to quickly create routes with the request_uri parameter
*
* @author Clément Loiselet <clement.loiselet@reseau.eseo.fr>
* @version 1.5
* @access public
* ex : $route = new Route();
*      $route->route("/modele/{vue}/{controller}", function(vue, controller_or_other_name){}); to get all requests of the type /modele/(.*)/(.*) using this function
*      $route->post("/modele2/{vue}", function(vue){}); to get only the POST requests; POST/GET/DELETE/PUT
*      $route->error_404(function(){}); to set an error if no route correspond to the current url
*      $route->route("path",function(){},array("POST","PUT")) to execute the path on POST and PUT
*      $route->route(array(path1,path2,...),function, method) will route all paths to the method. please take care about possible missing arguments between paths. works for route, get, put, post and delete.
*       
*      only the first corresponding route will be activated; all next one will be ignored.
*
*      note $route->delete correspond to a DELETE request
*      note $route->post/delete/put/get uses $route->route, and are therefore limited to one execution only
*      note the path must correspond to $_SERVER['REQUEST_URI']
*      
**/
class Route{

    public $_current_uri;
    private $_route_used;
    private $_errors;

    /**
    * Create the route class
    * adds a register_shutdown_function to execute if no page has been executed from this item
    **/
    public function __construct(){
        $this->_routes = array();

        $this->_current_uri = Route::getCurrentUri();

        $this->_route_used = null;

        $this->_errors = array();

        register_shutdown_function(array($this,"on_shutdown"));

    }

    /**
    * get the current uri
    * 
    * @return the current URI without the GET parameter
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

    /**
    * function called on shutdown verifying if a page has been served. if not, serve a 404 custom page.
    **/
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

    /**
    * set the function to execute in case of 404
    * @param function
    **/
    public function error_404($function){
        $this->_errors[404] = $function;
    }

    /**
    * check if the path correspond to the current URI in a GET request and do the function in case of success
    * @param path the path to check
    * @param function
    **/
    public function get($path, $function){
        if ($_SERVER['REQUEST_METHOD'] === "GET"){
            $this->route($path, $function);
        }
    }

    /**
    * check if the path correspond to the current URI in a POST request and do the function in case of success
    * @param path the path to check
    * @param function
    **/
    public function post($path, $function){
        if ($_SERVER['REQUEST_METHOD'] === "POST"){
            $this->route($path, $function);
        }
    }

    /**
    * check if the path correspond to the current URI in a PUT request and do the function in case of success
    * @param path the path to check
    * @param function
    **/
    public function put($path, $function){
        if ($_SERVER['REQUEST_METHOD'] === "PUT"){
            $this->route($path, $function);
        }
    }

    /**
    * check if the path correspond to the current URI in a DELETE request and do the function in case of success
    * @param path the path to check
    * @param function
    **/
    public function delete($path, $function){
        if ($_SERVER['REQUEST_METHOD'] === "DELETE"){
            $this->route($path, $function);
        }

    }

    /**
    * check if the path correspond to the current URI and do the function in case of success
    * @param path the path to check
    * @param function
    * @param methods the methods that can do it. set it with an array of request methods to have it work only on these cases
    **/
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

    /* ------------- private functions ------------- */

    /**
    * check if the path correspond to the current uri*
    * @param path the path to check
    *
    * @return boolean
    */
    private function check($path){
        $regex = $this->get_regex($path);
        $uri = $this->_current_uri;
        return preg_match($regex, $uri);
    }

    /**
    * extract the arguments defined by the path from the current uri 
    * @param path
    *
    * @return all arguments of the uri in a list and dic style 
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

    /**
    * previous version of get_arguments
    *
    **/
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

    /**
    * get the regex to check if the uri correspond to the path
    * @param path
    *
    * @return regex
    **/
    private function get_regex($path){
        $path_regexed = $path;
        $path_regexed = preg_replace('/{[^}\\/]*}/', "([^/]*)", $path_regexed);//remplace les expressions sans nom
        $path_regexed = str_replace("/","\\/", $path_regexed);

        $path_regexed = "/^".$path_regexed."$/";
        return $path_regexed;
    }

    /**
    * get the regex to get the arguments defined by the path from the uri
    * @param path
    *
    * @return regex
    **/
    private function get_regex_args($path){
        $path_regexed = $path;
        $path_regexed = preg_replace('/{([^}\/]+)}/', "(?P<$1>[^/]*)", $path_regexed);//remplace les expressions possédants un nom
        $path_regexed = preg_replace('/{[^}\\/]*}/', "([^/]*)", $path_regexed);//remplace les expressions sans nom
        $path_regexed = str_replace("/","\\/", $path_regexed);

        $path_regexed = "/^".$path_regexed."$/";
        return $path_regexed;
    }
}
//php file : do not put "? >" at the end to the risk of having a whitespace included 