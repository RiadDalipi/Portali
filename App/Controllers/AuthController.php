<?php

namespace App\Controllers;

use App\Models\User;


class AuthController extends BaseController
{
    public function login()
    {
        $this->view('login');
    }

    public function check()
    {
        $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
        $password = trim(filter_var($_POST['password'], FILTER_SANITIZE_STRING));

        $result = $this->conn->query("SELECT * FROM users WHERE email='$email' LIMIT 1");
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {

                if ($row['activated'] == 1) {
                    $_SESSION['isMember'] = TRUE;
                    $_SESSION['userID'] = $row['usr_id'];
                    $_SESSION['userName'] = $row['full_name'];
                    if ($row['role'] == "admin") {
                        $_SESSION['isAdmin'] = TRUE;
                        header("Location: /admin/dashboard");
                    } else {
                        $_SESSION['isAdmin'] = FALSE;
                        header("Location: /");
                    }
                } else {
                    $this->view('report', ['report' => "Llogaria e juaj nuk eshte e aktivizuar. Kontrolloni emailin tuaj per mesazhin e derguar nga ne per aktivizim."]);
                }

            } else {
                $this->view('report', ['report' => "Emaili ose Fjalekalimi i gabuar"]);
            }
        } else {
            $this->view('report', ['report' => "Emaili ose Fjalekalimi i gabuar"]);
        }
    }

    public function logout()
    {
        session_destroy();
        header("Location: /auth/login");
    }

    public function signup()
    {
        $this->view('signup');
    }

    public function register()
    {
        $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
        $password = password_hash($password, PASSWORD_DEFAULT);

        $result = $this->conn->query("INSERT INTO users SET full_name = '$full_name', 
email='$email', password = '$password'");
        if ($result == true) {
            echo "<p>Te dhenat ne databaze u regjistruan me sukses!</p>";

            $hash = sha1($this->config['salt'] . $email);
            $link = $this->config['app_domain'] . "/auth/activation/$hash";

            $body_html = "
            <h1>Miresevini ne ickphp.test!</h1>
            <p>Jeni regjistruar ne websajtin tone. Luteni ta aktivizoni llogarine tuaj duke klikuar ne linkun meposhte:</p>
            <p>$link</p>
            ";
            $body_text = "";
            $this->email($email, "Regjistrim ne ickphp.test", $body_html, $body_text);

        } else {
            echo "<p>Gabim gjate regjistrimit!</p>";
        }
    }


    public function activation($hash)
    {
        $result = $this->conn->query("SELECT * FROM users WHERE SHA1(CONCAT('" . $this->config['salt'] . "', email)) = '$hash' LIMIT 1");
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if ($user['activated'] == 1) {
                $this->view('report', ['report' => "Llogaria juaj eshte e aktivizuar!"]);
            } else {
                $r = $this->conn->query("UPDATE users SET activated = 1 WHERE usr_id = '" . $user['usr_id'] . "' LIMIT 1");
                if ($r) {
                    $this->view('report', ['report' => "Llogaria juaj u aktivizua!"]);
                } else {
                    $this->view('report', ['report' => "Llogaria juaj NUK u aktivizua!"]);
                }
            }
        } else {
            $this->view('report', ['report' => "Linku jovalid i aktivizimit!"]);
        }
    }

    public function profile()
    {

        $user = User::getUser($this->conn, $_SESSION['userID']);
        $this->view('profile', compact('user'));
    }

    public function profileupdate()
    {
        $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
        $password1 = filter_var($_POST['password1'], FILTER_SANITIZE_STRING);
        $password2 = filter_var($_POST['password2'], FILTER_SANITIZE_STRING);

        $raporti = "";

        if ($password1 == $password2) {
            if (mb_strlen($password1) >= 8) {
                $user = User::getUser($this->conn, $_SESSION['userID']);
                if (password_verify($password, $user['password'])) {
                    if (User::changePassword($this->conn, password_hash($password1, PASSWORD_DEFAULT))) {
                        $raporti .= "<p>Fjalekalimi u ndryshua me sukses!";
                    } else {
                        $raporti .= "<p>Ndryshimi i fjalekalimit deshtoi!</p>";
                    }
                } else {
                    $raporti .= "<p>Fjalekalimi i vjeter nuk eshte i sakte!</p>";
                }
            } else {
                $raporti .= "<p>Fjalekalimi i ri duhet t'i kete se paku 8 shkronja</p>";
            }
        } else {
            $raporti .= "<p>Fjalekalimet nuk jane te njejta!</p>";
        }

        $this->view('report', ['report' => $raporti]);
    }


    public function forgotpasswordform()
    {
        $this->view('forgotpasswordform');
    }


    public function forgotpassword()
    {
        $raporti = "";
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = User::checkEmail($this->conn, $email);
            if ($user === false) {
                $raporti .= "<p>Ky email nuk eshte ne databaze!</p>";
            } else {
                $hash = sha1($this->config['salt'] . $user['usr_id']);
                $link = "https://ickphp.test/auth/verification/" . $hash;

                $message_html = "
                <p><img src='" . $this->config['app_domain'] . "/images/logo.jpg'></p>
                <p>Keni kerkuar ndryshimin e passwordit.</p>
                <p>Nese deshironi ta ndryshoni, klikoni ne linkun:</p>
                <p><a href='$link'>$link</a></p>";

                $message_text = "
                Keni kerkuar ndryshimin e passwordit.\n
                Nese deshironi ta ndryshoni, klikoni ne linkun:\n
                $link\n";

                if ($this->email($user['email'], "Konfirmim i ndryshimit te fjalekalimit",
                    $message_html, $message_text)) {
                    $raporti .= "<p>Emaili u dergua!</p>";
                } else {
                    $raporti .= "<p>Emaili NUK u dergua!</p>";
                }
            }
        } else {
            $raporti .= "<p>Emaili nuk eshte i formatit valid!</p>";
        }


        $this->view('report', ['report' => $raporti]);
    }


    public function verification($hash)
    {
        $sql = "SELECT * FROM users
                WHERE SHA1(CONCAT('" . $this->config['salt'] . "', usr_id))
                 = '" . $this->conn->real_escape_string($hash) . "' LIMIT 1";
        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            $password = $this->randomPassword();

            $message_html = "<h2>Fjalekalimi i ri: $password</h2>";
            $message_text = "Fjalekalimi i ri: $password";

            $sql = "UPDATE users SET password='" . password_hash($password, PASSWORD_DEFAULT) . "' 
                    WHERE usr_id='" . $user['usr_id'] . "' LIMIT 1";
            $this->conn->query($sql);

            $raporti = "";
            if ($this->email($user['email'], "Ndryshimi i fjalekalimit",
                $message_html, $message_text)) {
                $raporti .= "<p>Emaili u dergua!</p>";
            } else {
                $raporti .= "<p>Emaili NUK u dergua!</p>";
            }
        }
        $this->view('report', ['report' => $raporti]);

    }
}