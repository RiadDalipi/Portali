<?php

namespace App\Controllers;

use App\Models\Article;
use App\Models\Category;
use Intervention\Image\ImageManager;


class AdminController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->checkAdmin();
    }

    public function dashboard()
    {
        $this->view('Admin/dashboard');
    }

    public function articles()
    {
        $articles = Article::getLastArticles($this->conn);
        $this->view('Admin/articles', compact('articles'));
    }

    public function editarticle($id)
    {
        $article = Article::getArticle($this->conn, $id);
        if ($article != NULL) {
            $title = $article['title'];
            $body = $article['body'];
            $category_id = $article['category_id'];

            $categories = Category::getCategories($this->conn);
            $mode = "update";
            $this->view('Admin/postarticle', compact('categories', 'title', 'body', 'category_id', 'mode', 'id'));
        } else {
            $this->view('report', ['report' => "Ky lajm nuk ekziston!"]);
        }
    }


    public function postarticle()
    {
        $title = isset($_COOKIE['title']) ? $_COOKIE['title'] : "";
        $body = isset($_COOKIE['body']) ? $_COOKIE['body'] : "";
        $category_id = isset($_COOKIE['category_id']) ? $_COOKIE['category_id'] : 0;

        $categories = Category::getCategories($this->conn);
        $mode = "insert";
        $id = 0;
        $this->view('Admin/postarticle', compact('categories', 'title', 'body', 'category_id', 'mode', 'id'));
    }


    public function savearticle()
    {
        $upload = $this->upload("fotografia");

        $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
        $body = strip_tags($_POST['body'], "<img><p><h1><h2><h3><strong><b><em><i><ul><ol><li><a><blockquote><table><tr><thead><tbody><tfoot><th><td><iframe><video><audio>");
        $category_id = filter_var($_POST['category_id'], FILTER_SANITIZE_NUMBER_INT);

        $mode = filter_var($_POST['mode'], FILTER_SANITIZE_STRING);
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

        setcookie("title", $title, time() + 600);
        setcookie("body", $body, time() + 600);
        setcookie("category_id", $category_id, time() + 600);

        $raporti = "";

        if (mb_strlen($title) < 5) {
            $raporti .= "<p>Titulli duhet t'i kete min. 5 shkronja</p>";
        }

        if (mb_strlen($body) < 50) {
            $raporti .= "<p>Lajmi duhet t'i kete min. 50 shkronja</p>";
        }

        if ($category_id <= 0) {
            $raporti .= "<p>Nuk e keni zgjedhur rubriken!</p>";
        }

        if (strlen($raporti) > 0) {
            $raporti .= "<p>Keni bere disa gabime.</p>";
            $raporti .= "<p><a href='/admin/postarticle'>Kthehu ne formular</a></p>";
            $this->view('report', ['report' => $raporti]);
        } else {
            if ($mode == "insert" && $id == 0) {
                $sql = "INSERT INTO articles 
                SET `title`='" . $this->conn->real_escape_string($title) . "', 
                `body`='" . $this->conn->real_escape_string($body) . "', 
                `featured_image` ='" . $this->conn->real_escape_string($upload['image_name']) . "',
                `category_id`='" . $this->conn->real_escape_string($category_id) . "', 
                `user_id`='" . $_SESSION['userID'] . "'";
            } else if ($mode == "update" && $id > 0) {
                $sql = "UPDATE articles 
                SET `title`='" . $this->conn->real_escape_string($title) . "', 
                `body`='" . $this->conn->real_escape_string($body) . "', ";

                if ($upload['uploaded']) {
                    $sql .= "`featured_image` ='" . $this->conn->real_escape_string($upload['image_name']) . "',";
                }

                $sql .= "`category_id`='" . $this->conn->real_escape_string($category_id) . "', 
                `user_id`='" . $_SESSION['userID'] . "' 
                 WHERE id='" . $this->conn->real_escape_string($id) . "' LIMIT 1";
            } else {
                $this->view('report', ['report' => "<p>Gabim ne kerkesen per perditesimin/insertimin e lajmit</p>"]);
            }

            $result = $this->conn->query($sql);
            if ($result) {
                setcookie("title", "", time() - 600);
                setcookie("body", "", time() - 600);
                setcookie("category_id", 0, time() - 600);

                $this->view('report', ['report' => "<p>Artikulli u postua me sukses!</p>" . $upload['upload_raporti']]);
            } else {
                $this->view('report', ['report' => "<p>Artikulli NUK u postua!</p>" . $upload['upload_raporti']]);
            }
        }
    }


    public function upload($field = "image")
    {
        $upload = false;
        $name = $_FILES[$field]['name'];
        $type = $_FILES[$field]['type'];
        $tmp_name = $_FILES[$field]['tmp_name'];
        $error = $_FILES[$field]['error'];
        $size = $_FILES[$field]['size'];
        $upload_raporti = "";
        if ($error == UPLOAD_ERR_OK) {
            if ($type == "image/png" || $type == "image/jpeg") {
                if ($size <= 7000000) {
                    $i = 1;
                    $n = pathinfo($name);
                    while (file_exists("images/" . $name)) {
                        $name = $n['filename'] . "-" . $i . "." . $n['extension'];
                        $i++;
                    }
                    if (move_uploaded_file($tmp_name, "images/" . $name)) {
                        $upload_raporti .= "<p>Fajlli u ngarkua me sukses</p>";
                        $upload = true;

                        // Krijo thumbnail

                        $manager = new ImageManager(['driver' => 'gd']);
                        $image = $manager->make("images/" . $name)
                            ->resize(1320, null, function ($constraint) {
                                $constraint->aspectRatio();
                            })->save("images/" . $name, 70);

                        $image = $manager->make("images/" . $name)
                            ->resize(330, null, function ($constraint) {
                                $constraint->aspectRatio();
                            })->save("images/thumbnails/" . $name, 50);
                    } else {
                        $upload_raporti .= "<p>Fajlli nuk u ngarkua</p>";
                    }
                } else {
                    $upload_raporti .= "<p>Fajlli duhet te kete max. 1.000.000 byte</p>";
                }
            } else {
                $upload_raporti .= "<p>Nuk lejohen fajllat qe nuk jane JPG ose PNG!</p>";
            }
        } else if ($error == UPLOAD_ERR_NO_FILE) {
            //
        } else {
            switch ($error) {
                case UPLOAD_ERR_INI_SIZE:
                    $upload_raporti .= "<p>Fajlli eshte me i madh se 'upload_max_filesize' ne php.ini</p>";
                    break;

                case UPLOAD_ERR_FORM_SIZE:
                    $upload_raporti .= "<p>Fajlli eshte me i madh se 'MAX_FILE_SIZE' ne formular</p>";
                    break;

                case UPLOAD_ERR_PARTIAL:
                    $upload_raporti .= "<p>Fajlli u ngarkua pjeserisht</p>";
                    break;

                case UPLOAD_ERR_NO_TMP_DIR:
                    $upload_raporti .= "<p>Mungon folderi 'tmp'</p>";
                    break;

                case UPLOAD_ERR_CANT_WRITE:
                    $upload_raporti .= "<p>Nuk ka leje per shkrim</p>";
                    break;

                case UPLOAD_ERR_EXTENSION:
                    $upload_raporti .= "<p>Nje ekstension shkaktoi problem!</p>";
                    break;
            }
        }


        return [
            'uploaded' => $upload,
            'upload_raporti' => $upload_raporti,
            'image_name' => $name,
        ];
    }

    public function deletearticles()
    {
        if (isset($_POST['deletearticle']) && is_array($_POST['deletearticle'])) {
            $raporti = "";
            foreach ($_POST['deletearticle'] as $key => $value) {
                $id = (int)$key;
                if ($value == "on" && $id > 0) {
                    switch ($this->delete($id)) {
                        case true:
                            $raporti .= "<p>Lajmi $id u fshi me sukses.</p>";
                            break;
                        case false:
                            $raporti .= "<p>Lajmi $id NUK u fshi.</p>";
                            break;
                        case null:
                            $raporti .= "<p>Lajmi $id NUK ekziston.</p>";
                            break;
                        default:
                            // Gabim i panjohur
                    }
                }
            }
            $this->view('report', ['report' => $raporti]);
        } else {
            header("Location: /admin/articles");
        }

    }

    private function delete($id)
    {
        $article = Article::getArticle($this->conn, $id);
        if ($article !== NULL) {
            if (!empty($article['featured_image'])) {
                if (file_exists("images/" . $article['featured_image'])) {
                    @unlink("images/" . $article['featured_image']);
                }

                if (file_exists("images/thumbnails/" . $article['featured_image'])) {
                    @unlink("images/thumbnails/" . $article['featured_image']);
                }
            }

            if (Article::deleteArticle($this->conn, $id)) {
                return true;
            } else {
                return false;
            }
        } else {
            return null;
        }
    }


    public function deletearticle($id)
    {
        $raporti = "";
        switch ($this->delete($id)) {
            case true:
                $raporti .= "<p>Lajmi $id u fshi me sukses.</p>";
                break;
            case false:
                $raporti .= "<p>Lajmi $id NUK u fshi.</p>";
                break;
            case null:
                $raporti .= "<p>Lajmi $id NUK ekziston.</p>";
                break;
            default:
                // Gabim i panjohur
        }

        $this->view('report', ['report' => $raporti]);
    }
}
