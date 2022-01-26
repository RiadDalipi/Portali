<?php
namespace App\Controllers;
use App\Models\Article;

class RestController extends BaseController
{


    public function index()
    {
        $articles = Article::getLastArticles($this->conn);
        echo json_encode($articles);

    }

    public function show($id)
    {
       $article = Article::getArticle($this->conn, $id);
       echo json_encode($article);
    }
}