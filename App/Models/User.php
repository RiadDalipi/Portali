<?php

namespace App\Models;

class User extends Model
{

    public static function getUsers($conn)
    {
        $list = [];
        $result = $conn->query("SELECT * FROM users WHERE role='moderator' OR role='admin' ORDER BY full_name");
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $list[] = $row;
            }
        }

        return $list;
    }

    public static function getUser($conn, $id)
    {
        $sql = "SELECT * FROM users  WHERE usr_id='" . $conn->real_escape_string($id) . "' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return NULL;
        }
    }

    public static function checkEmail($conn, $email)
    {
        $result = $conn->query("SELECT * FROM users  WHERE email='"
            . $conn->real_escape_string(trim($email)) . "' LIMIT 1");
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return false;
        }
    }


    public static function changePassword($conn, $hash)
    {
        $sql = "UPDATE users SET `password` = '" . $conn->real_escape_string($hash) . "' 
                WHERE usr_id='" . $_SESSION['userID'] . "' LIMIT 1";
        return $conn->query($sql);
    }
}