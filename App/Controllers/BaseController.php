<?php

namespace App\Controllers;

use Swift_Attachment;
use Swift_Image;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class BaseController
{
    public $conn = NULL;
    public $twig = NULL;
    public $config = [];

    public function __construct()
    {
        $this->config = require_once('config.php');
        $this->conn = new \mysqli(
            $this->config['db_host'],
            $this->config['db_user'],
            $this->config['db_pass'],
            $this->config['db_name']);

        if ($this->conn->connect_errno > 0) {
            echo "Gabim gjate konektimit me MySQL: " . $this->conn->connect_error;
            exit();
        }
        $this->conn->set_charset("utf8");


        $loader = new \Twig\Loader\FilesystemLoader('App/Views');
        $this->twig = new \Twig\Environment($loader, [
            'debug' => true,
            'cache' => false,
        ]);

        $this->twig->addExtension(new \Twig\Extension\DebugExtension());

    }

    public function checkAdmin()
    {
        if (!(isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'])) {
            echo "Ndalohet hyrja / Access denied";
            die();
        }
    }


    public function view($view, $data = [])
    {
        $data['isMember'] = isset($_SESSION['isMember']) ? $_SESSION['isMember'] : false;
        $data['isAdmin'] = isset($_SESSION['isAdmin']) ? $_SESSION['isAdmin'] : false;
        $data['userID'] = isset($_SESSION['userID']) ? $_SESSION['userID'] : 0;
        $data['userName'] = isset($_SESSION['userName']) ? $_SESSION['userName'] : "Visitor";

        echo $this->twig->render($view . ".html", $data);
    }


    public function email($to, $subject, $body_html, $body_text = "")
    {
        $transport = (new Swift_SmtpTransport(
            $this->config['mail_host'],
            $this->config['mail_port']))
            ->setUsername($this->config['mail_username'])
            ->setPassword($this->config['mail_password']);

        $mailer = new Swift_Mailer($transport);

        $message = (new Swift_Message($subject))
            ->setFrom([$this->config['mail_username'] => $this->config['mail_name']])
            ->setTo([$to])
//            ->attach(Swift_Attachment::fromPath('images/python.pdf')->setFilename('pythonbook.pdf'))
//            ->attach(Swift_Attachment::fromPath('images/logo.jpg')->setDisposition('inline'))
            ->setBody($body_html, 'text/html')
            ->addPart($body_text, 'text/plain')
           ;

        /*
        $message->setBody(
            '<html>' .
            ' <body>' .
            '  Here is an image <img src="' . // Embed the file
            $message->embed(Swift_Image::fromPath('images/logo.jpg')) .
            '" alt="Image" />' .
            '  Rest of message' .
            ' </body>' .
            '</html>',
            'text/html' // Mark the content-type as HTML
        );
        */
        return $mailer->send($message);
    }


    public function randomPassword() {
        $string = "qazwsxedcrfvtgbyhnujmiklopPLKOIJNBHUYGVCFTRDXZSEWAQ1234568790!@#$%()_=+";
        $length = rand(8,15);
        $password = "";
        for($i = 1; $i<=$length; $i++) {
            $password .= $string[rand(0, mb_strlen($string)-1)];
        }

        return $password;
    }
}