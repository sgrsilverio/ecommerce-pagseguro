<?php


namespace Hcode\Model;


use Hcode\DB\Sql;
use Hcode\Model;

class Order extends Model
{
    const SUCESS = "Order-Success";
    const ERROR = "Order-Error";



    public function save(){
        $sql = new Sql();
            $results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", [
               ':idorder'=>$this->getidorder(),
                ':idcart'=>$this->getidcart(),
                ':iduser'=>$this->getiduser(),
                'idstatus'=>$this->getidstatus(),
                ':idaddress'=>$this->getidaddress(),
                ':vltotal'=>$this->getvltotal()
            ]);
            if (count($results) > 0) {
                $this->setData($results[0]);
            }
    }

    public function get($idorder){

        $sql = new Sql();
        $results = $sql->select("
        SELECT * FROM tb_orders a 
        INNER JOIN tb_ordersstatus b USING (idstatus)
        INNER JOIN tb_carts c USING (idcart)
        INNER JOIN tb_users d ON d.iduser = a.iduser
        INNER JOIN tb_addresses e USING(idaddress)
        INNER JOIN tb_persons f ON f.idperson = d.idperson
        WHERE a.idorder = :idorder", [
            ':idorder'=>$idorder
        ]);
        if (count($results) > 0 ){
            $this->setData($results[0]);
        }

    }

    public function getOrder($idcart){
        $sql = new Sql();
        $results = $sql->select("SELECT max(idorder) as idorder FROM tb_orders WHERE idcart = :idcart",[
            ':idcart'=>$idcart
        ]);

        if (count($results) > 0) {
            return ($results[0]['idorder']);
        }

    }

    public static function listAll(){
        $sql = new Sql;
        $results = $sql->select(" SELECT * FROM tb_orders a 
        INNER JOIN tb_ordersstatus b USING (idstatus)
        INNER JOIN tb_carts c USING (idcart)
        INNER JOIN tb_users d ON d.iduser = a.iduser
        INNER JOIN tb_addresses e USING(idaddress)
        INNER JOIN tb_persons f ON f.idperson = d.idperson
        ORDER BY a.dtregister DESC ");
        return $results;
    }

    public function delete($idorder){
        $sql = new Sql();
        $sql->query("UPDATE tb_orders SET idstatus = 5 WHERE idorder = :idorder;", [
            ':idorder'=>$idorder
        ]);
    }

    public function getCart():Cart{
        $cart = new Cart();
        $cart->get((int)$this->getidcart());
        return $cart;
    }

    public static function setSuccess($msg){
        $_SESSION[User::SUCCESS] = $msg;
    }

    public static function getSuccess(){
        $msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : '';
        User::clearSuccess();
        return $msg;
    }

    public static function clearSuccess(){
        $_SESSION[Order::SUCCESS] = NULL;
    }

    public static function setError($msg){
        $_SESSION[Order::ERROR] = $msg;
    }

    public static function getError(){
        $msg = (isset($_SESSION[Order::ERROR]) && $_SESSION[Order::ERROR]) ? $_SESSION[Order::ERROR] : '';
        Order::clearError();
        return $msg;
    }

    public static function clearError(){
        $_SESSION[Order::ERROR] = NULL;
    }

    public static function getPage($page = 1, $itemsPerPage = 3){
        $start = ($page - 1) * $itemsPerPage;
        $sql = new Sql();
        $results = $sql->select(
            "SELECT SQL_CALC_FOUND_ROWS *
        FROM tb_orders a 
        INNER JOIN tb_ordersstatus b USING (idstatus)
        INNER JOIN tb_carts c USING (idcart)
        INNER JOIN tb_users d ON d.iduser = a.iduser
        INNER JOIN tb_addresses e USING(idaddress)
        INNER JOIN tb_persons f ON f.idperson = d.idperson
        ORDER BY a.dtregister DESC 
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
            FROM tb_orders a 
        INNER JOIN tb_ordersstatus b USING (idstatus)
        INNER JOIN tb_carts c USING (idcart)
        INNER JOIN tb_users d ON d.iduser = a.iduser
        INNER JOIN tb_addresses e USING(idaddress)
        INNER JOIN tb_persons f ON f.idperson = d.idperson
        WHERE f.desperson LIKE :search OR b.desstatus LIKE :search
        ORDER BY a.dtregister DESC
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