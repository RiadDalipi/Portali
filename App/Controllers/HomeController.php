<?php
namespace App\Controllers;


use App\Models\Article;

class HomeController extends BaseController {

    public function index() {

        $this->view('home',['name' => 'Innovation Centre Kosova','price' => 200]);
    }
}