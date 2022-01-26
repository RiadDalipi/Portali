<?php
namespace App\Models;

class Category extends Model {

    public static function getCategories($conn) {
        $result = $conn->query("SELECT * FROM categories ORDER BY category_name");
        $categories = [];
        if ($result->num_rows > 0) {
            while($category = $result->fetch_assoc()) {
                $categories[] = $category;
            }
        }

        return $categories;
    }

}