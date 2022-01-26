<?php

namespace App\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;

class ArticleController extends BaseController
{
    public function index()
    {
        $articles = Article::getLastArticles($this->conn);

        if (count($articles) > 0) {
            $this->view('articles', ['articles' => $articles]);
        } else {
            $this->view('report', ['report' => "Nuk ka asnje artikull!"]);
        }
    }

    public function show($id)
    {
        $article = Article::getArticle($this->conn, $id);

        if ($article !== NULL) {
            $this->view('article', ['article' => $article]);
        } else {
            $this->view('report', ['report' => "Nuk e gjeta lajmin"]);
        }
    }

    public function search()
    {
        $limit = 5;
        $page = isset($_GET['page']) ? filter_var($_GET['page'],
            FILTER_SANITIZE_NUMBER_INT) : 1;
        $search_term = filter_var($_GET['search_term'], FILTER_SANITIZE_STRING);
        $pages = Article::countPages($this->conn, $search_term, $limit);
        $articles = Article::search($this->conn, $search_term, $page, $limit);

        $this->view('search_results', compact('page', 'pages',
            'search_term', 'articles'));
    }

    public function advanced_search_form()
    {
        $categories = Category::getCategories($this->conn);
        $users = User::getUsers($this->conn);
        $this->view('advanced_search_form', compact('categories', 'users'));
    }

    public function advanced_search()
    {
        $limit = 5;
        $page = isset($_GET['page']) ? filter_var($_GET['page'],
            FILTER_SANITIZE_NUMBER_INT) : 1;




        $keywords = filter_var($_GET['keywords'], FILTER_SANITIZE_STRING);
        $category_id = filter_var($_GET['category_id'], FILTER_SANITIZE_NUMBER_INT);
        $user_id = filter_var($_GET['user_id'], FILTER_SANITIZE_NUMBER_INT);
        $date_from = filter_var($_GET['date_from'], FILTER_SANITIZE_STRING);
        $date_to = filter_var($_GET['date_to'], FILTER_SANITIZE_STRING);

        $sql = "SELECT * FROM articles WHERE 1 ";
        $count_sql = "SELECT COUNT(*) AS total FROM articles WHERE 1 ";


        if (mb_strlen($keywords) > 0) {
            $filter = " AND (title LIKE '%"
                . $this->conn->real_escape_string($keywords) . "%' OR body LIKE '%"
                . $this->conn->real_escape_string($keywords) . "%') ";

            $sql .= $filter;
            $count_sql .= $filter;
        }

        if ($category_id > 0) {
            $filter = " AND category_id='"
                . $this->conn->real_escape_string($category_id) . "'";

            $sql .= $filter;
            $count_sql .= $filter;
        }

        if ($user_id > 0) {
            $filter = " AND user_id='"
                . $this->conn->real_escape_string($user_id) . "'";

            $sql .= $filter;
            $count_sql .= $filter;
        }

        if (mb_strlen($date_from) == 10) {
            $filter = " AND SUBSTRING(created_at, 1, 10) >= '" . $this->conn->real_escape_string($date_from) . "' ";
            $sql .= $filter;
            $count_sql .= $filter;
        }

        if (mb_strlen($date_to) == 10) {
            $filter = " AND SUBSTRING(created_at, 1, 10) <= '" . $this->conn->real_escape_string($date_to) . "' ";
            $sql .= $filter;
            $count_sql .= $filter;
        }


        $total_results = 0;
        $result = $this->conn->query($count_sql);
        if ($result->num_rows > 0) {
            $r = $result->fetch_assoc();
            $total_results = $r['total'];
        }

        $pages = ceil($total_results / $limit);
        $offset = ($page - 1) * $limit;
        $sql .= "ORDER BY created_at DESC LIMIT $offset, $limit";

        $previous = $page - 1;
        $next = $page + 1;

        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            $articles = [];
            while ($row = $result->fetch_assoc()) {
                $articles[] = $row;
            }

            $this->view('advanced_search_results', compact('articles', 'page', 'pages', 'keywords', 'category_id', 'user_id', 'date_from', 'date_to', 'next', 'previous'));
        } else {
            $this->view('report', ['report' => "Nuk gjeta asnje lajm me keto kritere!"]);
        }
    }
}