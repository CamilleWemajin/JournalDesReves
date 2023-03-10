<?php

    class Page
    {
        public $json ;
        public $meta ;
        public $title ;

        public $menu;

        public $content ;
        protected $currentVersion = "0.1" ;

        public $footer;
        public $css = ["mandatory"] ;
        public $beforePath = '' ;
        public $js = [] ;

        public $pageId = 'index' ;
        public $pageConfig ;

        public $searchPath = 'pages' ;

        public function __construct() {

            $this->json = new Json() ;
            $this->pageAccess() ;

        }

        public function menu(){
            $this->menu = file_get_contents("html/structures/menu.html") ;
            require_once "php/structures/menu.php" ;
            $this->css[] = "structures/menu" ;
        }


        public function pageAccess(){

            $file = 'pages' ;


            $access = $this->json->getFile($file) ;

            if (isset($access->{$this->pageId})){

                $this->pageConfig =$access->{$this->pageId} ;
                $this->buildPageHtml() ;
            } else {
                $this->content ="Cette page n'existe pas" ;
            }

            if (!empty( $this->pageConfig->title)){
                $this->title = $this->pageConfig->title ;
            }


        }

        function buildPageHtml(){

            $html = file_get_contents("html/{$this->searchPath}/{$this->pageId}.html") ;

            if (file_exists("php/{$this->searchPath}/{$this->pageId}.php")){

                require_once "php/{$this->searchPath}/{$this->pageId}.php" ;
            }

            if (file_exists("css/{$this->searchPath}/{$this->pageId}.css")){
                $this->css[] = "{$this->searchPath}/{$this->pageId}" ;
            }

            if (file_exists("js/{$this->searchPath}/{$this->pageId}.js")){
                $this->js[] = "{$this->searchPath}/{$this->pageId}" ;
            }

            $this->content = $html ;
        }


        public function output($onlyBody = false):string{


            $this->menu();
            $css = "" ;

            $this->css = array_unique($this->css) ;
            foreach ($this->css AS $stylesheet){
                $css .= "<link rel='stylesheet' href='{$this->beforePath}css/{$stylesheet}.css?{$this->currentVersion}'>" ;
            }

            $this->js = array_unique($this->js) ;

            foreach ($this->js AS $script){
                $css .= "<script src='{$this->beforePath}js/{$script}.js?{$this->currentVersion}'></script>" ;
            }


            $bodyClass = '' ;

            if (!empty($_GET['framed'])){
                $bodyClass = 'inIframe' ;
            }

            if ($onlyBody == false){
                $this->displayNotification() ;

                $output = "
<!doctype html>
<html lang='fr' class='{$bodyClass}'>
        <head>
          <title>{$this->title} - Katchacha</title>
          <meta name='viewport' content='width=device-width, initial-scale=1' />
          <meta charset='utf-8'>
          {$this->meta}
          {$css}
        </head>
    <body>
        {$this->menu}
        {$this->content}
        {$this->footer}
    </body>
</html>
" ;
            }else {
                $output = "<div class='bodyContent'>{$this->content}</div>" ;
            }

            return str_replace("\n\r", '', $output );

        }

        function newNotification($notificationId = 'Unknown', $isError = true){
            $messages = [
                "Unknown" =>["Erreur"],
                "goodSolution" =>["C'??tait vraiment facile apr??s","Beau gosse", "Bien jou?? ???"],
                "noSolution" =>["Merci d'indiquer une solution, une case vide ??a marche pas, looser"],
                "badSolution" =>["C'est pas ??a, pourtant vraiment c'est facile", "Aucune chance que ce soit ??a la solution srx", "C'est pas bon, pourtant m??me Fluff aurait trouv?? ", "Looser", "Attend, tu ??tais sur de toi ?"]
            ] ;

            if ($isError == true) {
                $_SESSION['errors'][] = $messages[$notificationId][array_rand($messages[$notificationId])];
            } else {
                $_SESSION['notifications'][] = $messages[$notificationId][array_rand($messages[$notificationId])];
            }

        }
        function displayNotification(){
            if (!empty($_SESSION['errors']) || !empty($_SESSION['notifications'])){
                $errorList = "<ul class='notifications'>" ;
                if (!empty($_SESSION['errors'])) {
                    foreach ($_SESSION['errors'] as $error) {
                        $errorList .= "<li class='error'>{$error}</li>";
                    }
                }

                if (!empty($_SESSION['notifications'])) {
                    foreach ($_SESSION['notifications'] as $error) {
                        $errorList .= "<li class='notification'>{$error}</li>";
                    }
                }
                $errorList .="</ul>" ;
                $this->menu .= $errorList ;
                $_SESSION['errors'] = NULL ; // supprime les erreurs une fois affich??es
                $_SESSION['notifications'] = NULL ;
            }
        }


        function stringToCleanUrl(string $string):string{
            $caracteres = array(
                '??' => 'a', '??' => 'a', '??' => 'a', '??' => 'a', '??' => 'a', '??' => 'a', '??' => 'a', '@' => 'a',
                '??' => 'e', '??' => 'e', '??' => 'e', '??' => 'e', '??' => 'e', '??' => 'e', '??' => 'e', '??' => 'e', '???' => 'e',
                '??' => 'i', '??' => 'i', '??' => 'i', '??' => 'i', '??' => 'i', '??' => 'i', '??' => 'i', '??' => 'i',
                '??' => 'o', '??' => 'o', '??' => 'o', '??' => 'o', '??' => 'o', '??' => 'o', '??' => 'o', '??' => 'o',
                '??' => 'u', '??' => 'u', '??' => 'u', '??' => 'u', '??' => 'u', '??' => 'u', '??' => 'u', '??' => 'u', '??' => 'u',
                '??' => 'oe', '??' => 'oe', '??' => 'c',
                '$' => 's');

            $url = strtr($string, $caracteres);
            $url = preg_replace('#[^A-Za-z0-9]+#', '-', $url);
            $url = trim($url, '-');

            return strtolower($url);
        }


        function stringToColor(string $string):string{
            $alreadyColors = ['Blanc' => 'e1e6e2', 'Gris' => '6e6e6e','Noir' => '292929','Vert' => '8bed61'] ;
            if (!empty($alreadyColors[$string])){
                $code = $alreadyColors[$string] ;
            } else {
                $code = dechex(crc32($string));
                $code = substr($code, 1, 6);
            }
            return '#'.$code;
        }

    }
