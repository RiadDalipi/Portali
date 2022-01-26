<?php

namespace App\Models;

class Article extends Model
{

    public static function getLastArticles($conn)
    {
        $sql = "SELECT * FROM articles ORDER BY created_at DESC LIMIT 10";
        $result = $conn->query($sql);
        $articles = [];

        if ($result->num_rows > 0) {
            while ($article = $result->fetch_assoc()) {
                $articles[] = $article;
            }
        }

        return $articles;
    }

    public static function countPages($conn, $search_term, $limit = 10)
    {
        $sql = "SELECT COUNT(*) as total FROM articles WHERE 
         title LIKE '%" . $conn->real_escape_string($search_term) . "%' 
         OR body LIKE '%" . $conn->real_escape_string($search_term) . "%'";

        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $pages = ceil($row['total'] / $limit);
        return $pages;
    }

    public static function search($conn, $search_term = "", $page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM articles WHERE 
         title LIKE '%" . $conn->real_escape_string($search_term) . "%' 
         OR body LIKE '%" . $conn->real_escape_string($search_term) . "%' 
         ORDER BY created_at DESC LIMIT $offset,$limit";

        $result = $conn->query($sql);
        $articles = [];
        if ($result->num_rows > 0) {
            while ($article = $result->fetch_assoc()) {
                $articles[] = $article;
            }
        }
        return $articles;
    }


    public static function getArticle($conn, $id)
    {
        $sql = "SELECT * FROM articles
                LEFT JOIN categories ON categories.cat_id = articles.category_id
                LEFT JOIN users ON users.usr_id = articles.user_id
                WHERE id='$id' LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $article = $result->fetch_assoc();
            return $article;
        } else {
            return NULL;
        }
    }


    public static function deleteArticle($conn, $id)
    {
        $sql = "DELETE FROM articles WHERE id='" . $conn->real_escape_string($id) . "' LIMIT 1";
        $result = $conn->query($sql);
        return $result;
    }
}