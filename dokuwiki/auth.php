<?php

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class auth_plugin_authsg extends DokuWiki_Auth_Plugin {

    public function __construct() {
        parent::__construct();

        $this->cando['addUser']   = false;
        $this->cando['delUser']   = false;
        $this->cando['modLogin']  = false;
        $this->cando['modPass']   = false;
        $this->cando['modMail']   = false;
        $this->cando['modGroups'] = false;
        $this->cando['getUsers']     = false;
        $this->cando['getUserCount'] = false;
        $this->_pregsplit_safe = version_compare(PCRE_VERSION,'6.7','>=');
    }

    public function checkPass($user, $pass) {
        $service_port = 4017;
        $address = "127.0.0.1";

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if($socket==false) {
            return false;
        }
        $socket_conn = socket_connect($socket, $address, $service_port);
        if($socket_conn==false) {
            return false;
        }
        $inpt  = $user;
        $inpt .= "\n";
        $inpt .= $pass;
        $inpt .= "\n";
        socket_write($socket, $inpt, strlen($inpt));
        $outp = socket_read($socket, 2048);
        socket_close($socket);
        if (preg_match("/^OK,/",$outp))
        {
            return true;
        }
        return false;
    }

    public function getUserData($user, $requireGroups=true) {
        $service_port = 4017;
        $address = "127.0.0.1";

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($socket == false) {
            return false;
        }
        $socket_conn = socket_connect($socket, $address, $service_port);
        if ($socket_conn == false) {
            return false;
        }
        $inpt  = $user;
        $inpt .= "\n";
        $inpt .= "info";
        $inpt .= "\n";
        socket_write($socket, $inpt, strlen($inpt));

        $outp = socket_read($socket, 2048);

        socket_close($socket);
        if (!preg_match("/^INFO,/",$outp))
        {
            return false;
        }
        preg_match("/^INFO,(?P<position>[\w ]+),(?P<mail>.*)/",$outp,$matches);

        return array(
            'name' => $user,
            'mail' => $matches['mail'],
            'grps' => array($matches['position'],),
        );
    }
}
