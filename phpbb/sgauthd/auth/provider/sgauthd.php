<?php

namespace sg\sgauthd\auth\provider;

/**
 * Database authentication provider for phpBB3
 *
 * This is for authentication via the integrated user table
 */
class sgauthd extends \phpbb\auth\provider\base
{
    /**
     * phpBB passwords manager
     *
     * @var \phpbb\passwords\manager
     */
    protected $passwords_manager;

    /**
     * Database Authentication Constructor
     *
     * @param \phpbb\db\driver\driver_interface $db
     */
    public function __construct(\phpbb\db\driver\driver_interface $db)
    {
        $this->db = $db;
        $this->config = $config;
        $this->passwords_manager = $passwords_manager;
        $this->user = $user;

    }

    /**
     * {@inheritdoc}
     */
    public function login($username, $password)
    {
        if (!$password)
        {
            return array(
                'status'    => LOGIN_ERROR_PASSWORD,
                'error_msg' => 'NO_PASSWORD_SUPPLIED',
                'user_row'  => array('user_id' => ANONYMOUS),
            );
        }

        if (!$username)
        {
            return array(
                'status'    => LOGIN_ERROR_USERNAME,
                'error_msg' => 'LOGIN_ERROR_USERNAME',
                'user_row'  => array('user_id' => ANONYMOUS),
            );
        }

        $service_port = 4017;
        $address = "127.0.0.1";

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($socket === false)
        {
            return array(
                'status'                => LOGIN_ERROR_EXTERNAL_AUTH,
                'error_msg'             => 'SOCKET_CREATION_FAIL',
                'user_row'              => array('user_id' => ANONYMOUS),
            );
        }
        $socket_conn = socket_connect($socket, $address, $service_port);
        if ($socket_conn === false)
        {
            return array(
                'status'                => LOGIN_ERROR_EXTERNAL_AUTH,
                'error_msg'             => 'SG_AUTHD_CONNECTION_ERROR',
                'user_row'              => array('user_id' => ANONYMOUS),
            );
        }

        // // $username = utf8_clean_string($username);

        $inpt  = $username;
        $inpt .= "\n";
        $inpt .= $password;
        $inpt .= "\n";

        socket_write($socket, $inpt, strlen($inpt));

        $outp = socket_read($socket, 2048);

        socket_close($socket);

            print("This is okay!");
        if (preg_match("/^OK,/",$outp))
        {


            $sql ='SELECT user_id, username, user_password, user_passchg, user_email, user_type
                   FROM ' . USERS_TABLE . "
                   WHERE username_clean = '" . $this->db->sql_escape(utf8_clean_string($username)) . "'";
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if($row)
            {
                // User inactive...
                if ($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE)
                {
                    return array(
                        'status'                => LOGIN_ERROR_ACTIVE,
                        'error_msg'             => 'ACTIVE_ERROR',
                        'user_row'              => $row,
                    );
                }

                // Successful login... set user_login_attempts to zero...
                return array(
                    'status'                => LOGIN_SUCCESS,
                    'error_msg'             => false,
                    'user_row'              => $row,
                );
            } else {
                preg_match("/^OK,(?P<position>[\w ]+),(?P<mail>.*)/",$outp,$matches);
                $group = $matches['position'];
                $mail = $matches['mail'];

                $sql = 'SELECT group_id
                        FROM ' . GROUPS_TABLE . "
                        WHERE group_name = '" . $this->db->sql_escape($group) . "'";
                $result = $this->db->sql_query($sql);
                $row = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);

                if (!$row)
                {
                    trigger_error($group . " group does not exist");
                }

                $authd_row = array(
                    'username' => $username,
                    'user_email' => $mail,
                    'group_id' => (int) $row['group_id'],
                    'user_type' => USER_NORMAL,
                    'user_ip' => $this->user->ip,
                    'user_new' => ($this->config['new_member_post_limit']) ? 1 : 0,
                );

                return array(
                    'status' => LOGIN_SUCCESS_CREATE_PROFILE,
                    'error_msg' => false,
                    'user_row' => $authd_row,
                );
            };
        };

        return array(
            'status'        => LOGIN_ERROR_USERNAME,
            'error_msg'     => 'LOGIN_ERROR_USERNAME',
            'user_row'      => array('user_id' => ANONYMOUS),
        );
    }
}
