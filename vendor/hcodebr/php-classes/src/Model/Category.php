<?php


namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class Category extends Model {

    public static function listAll(){
        $sql = new sql();
        return $sql->select("select * from tb_categories ORDER BY descategory");
    }

    public function Save(){
        $sql = new Sql();
        $results = $sql->select("CALL sp_categories_save (:pidcategory, :pdescategory)", array(
            ":pidcategory"=>$this->getidcategory(),
            ":pdescategory"=>$this->getdescategory()

        ));
        $this->setData($results[0]);
        Category::updatefile();
    }

    public function get($idcategory){
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", [
            ':idcategory'=>$idcategory
        ]);
        $this->setData($results[0]);
    }

    public function delete(){
        $sql = new Sql();
        $sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", [
           ':idcategory'=>$this->getidcategory()
        ]);
        Category::updatefile();
    }

    public static function updatefile(){
        $categories = Category::listAll();
        $html = [];
        foreach ($categories as $row) {
            array_push($html,'<li><a href="/categories/'.$row['idcategory'].'">'.$row['descategory'].'</a></li>');
        }
        file_put_contents($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR."categories-menu.html",implode('',$html));
    }

    public function getProducts($related = true){
         $sql = new Sql();
        if ($related === true) {
            return $sql->select("select * FROM tb_products WHERE idproduct IN (
	                                    select a.idproduct
                                        FROM tb_products a
	                                    INNER JOIN 	tb_categoriesproducts b ON a.idproduct = b.idproduct
	                                    WHERE b.idcategory = :idcategory);",[
	                                        ':idcategory'=>$this->getidcategory()
            ]);
        } else {
            return $sql->select("select * FROM tb_products WHERE idproduct not IN (
	                                    select a.idproduct
                                        FROM tb_products a
	                                    INNER JOIN 	tb_categoriesproducts b ON a.idproduct = b.idproduct
	                                    WHERE b.idcategory = :idcategory);",[
	                                        'idcategory'=>$this->getidcategory()
            ]);


        }


    }

    public function addProduct(Product $product){
        $sql = new Sql();
        $sql->query("INSERT INTO tb_categoriesproducts (idcategory, idproduct) VALUES (:idcategory, :idproduct)",[
            ':idcategory'=>$this->getidcategory(),
            ':idproduct'=>$product->getidproduct()
        ]);
        //var_dump($this->getidcategory(),$product->getidproduct());
        //exit;
    }

    public function removeProduct(Product $product){
        $sql = new Sql();
        $sql->query("DELETE FROM tb_categoriesproducts WHERE idcategory = :idcategory and idproduct= :idproduct;",[
            ':idcategory'=>$this->getidcategory(),
            ':idproduct'=>$product->getidproduct()
        ]);
    }

    public function getProductsPage($page = 1, $itemsPerPage = 3){
        $start = ($page - 1) * $itemsPerPage;
        $sql = new Sql();
        $results = $sql->select(
            "SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_products a 
            INNER JOIN tb_categoriesproducts b ON a.idproduct = b.idproduct
            INNER JOIN tb_categories c ON c.idcategory = b.idcategory
            WHERE c.idcategory = :idcategory
            LIMIT $start, $itemsPerPage;", [
                ':idcategory'=>$this->getidcategory()
        ]);
        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");
        return [
            'data'=>Product::checkList($results),
            'total'=>(int)$resultTotal[0]["nrtotal"],
            'pages'=>ceil($resultTotal[0]["nrtotal"]/$itemsPerPage)
        ];

    }

    public static function getPage($page = 1, $itemsPerPage = 3){
        $start = ($page - 1) * $itemsPerPage;
        $sql = new Sql();
        $results = $sql->select(
            "SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_categories
            ORDER BY descategory
            LIMIT $start, $itemsPerPage;
            ");
        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");
        return [
            'data'=>$results,
            'total'=>(int)$resultTotal[0]["nrtotal"],
            'pages'=>ceil($resultTotal[0]["nrtotal"]/$itemsPerPage)
        ];

    }
    public static function getPageSearch($search, $page = 1, $itemsPerPage = 3){
        $start = ($page - 1) * $itemsPerPage;
        $sql = new Sql();
        $results = $sql->select(
            "SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_categories
            WHERE descategory LIKE :search
            ORDER BY descategory
            LIMIT $start, $itemsPerPage;
            ", [
            ':search'=>'%'.$search.'%'
        ]);
        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");
        return [
            'data'=>$results,
            'total'=>(int)$resultTotal[0]["nrtotal"],
            'pages'=>ceil($resultTotal[0]["nrtotal"]/$itemsPerPage)
        ];

    }

}