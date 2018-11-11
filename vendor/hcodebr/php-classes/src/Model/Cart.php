<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\User;


class Cart extends Model {
    const SESSION = "Cart";
    const SESSION_ERROR = "CartError";


    public static function getFromSession()
    {
        $cart = new Cart();
        if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {
            $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
        } else {
            $cart->getFromSessionID();
            if (!(int)$cart->getidcart() > 0) {
                $data = [
                    'dessessionid'=>session_id()
                ];
                if (User::checkLogin(false)) {
                    $user = User::getFromSession();
                    $data['iduser'] = $user->getiduser();
                }
                $cart->setData($data);
                $cart->save();
                $cart->setToSession();
            }
        }
        return $cart;
    }

    public function setToSession(){
        $_SESSION[Cart::SESSION] = $this->getValues();
    }

    public function getFromSessionID(){
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
            ':dessessionid'=>session_id()
        ]);

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    public function get(int $idcart)    {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", [
            ':idcart'=>$idcart
        ]);
        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    public function save()
    {
        $sql = new Sql();
        $results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", [
            ':idcart'=>$this->getidcart(),
            ':dessessionid'=>$this->getdessessionid(),
            ':iduser'=>$this->getiduser(),
            ':deszipcode'=>$this->getdeszipcode(),
            ':vlfreight'=>$this->getvlfreight(),
            ':nrdays'=>$this->getnrdays()
        ]);

        $this->setData($results[0]);
    }

    public function addProduct(Product $product){
        $sql = new Sql();
        $sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct)
            VALUES (:idcart, :idproduct)",[
            ':idcart'=>$this->getidcart(),
            ':idproduct'=>$product->getidproduct()
        ]);

        $this->getCalculateTotal();
    }

    public function removeProduct(Product $product, $all = false){
        $sql = new Sql();
        if ($all) {
            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW()
               WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL",[
                ':idcart'=>$this->getidcart(),
                ':idproduct'=>$product->getidproduct()
            ]);
        } else {
            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW()
               WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1",[
                ':idcart'=>$this->getidcart(),
                ':idproduct'=>$product->getidproduct()
            ]);
        }

        $this->getCalculateTotal();
    }

    public function getProducts(){
        $sql = new Sql();
        $rows = $sql->select("SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal
        FROM tb_cartsproducts a
        INNER JOIN tb_products b ON a.idproduct = b.idproduct
        WHERE a.idcart = :idcart AND a.dtremoved is NULL
        GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl
        ORDER BY b.desproduct", [
            ':idcart'=>$this->getidcart()
        ]);

        return Product::checkList($rows);
    }

    public function getProductsTotals(){
    $sql = new Sql();
    $results = $sql->select("SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth,
                     SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
                     FROM tb_products a 
                     INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
                     WHERE b.idcart = :idcart AND dtremoved IS NULL", [
                         ':idcart'=>$this->getidcart()
    ]);
    if (count($results) > 0) {
        return $results[0];
        } else {return [];}
    }

    public function setFreight($nrzipcode){
        $nrzipcode = str_replace('-', '',$nrzipcode);
        $totals = $this->getProductsTotals();
        if ($totals['vlwidth'] < 11 ) {$totals['vlwidth'] = 11;}
        if ($totals['vlheight'] < 2 ) {$totals['vlheight'] = 2;}
        if ($totals['vllength'] < 16 ){$totals['vllength'] = 16;}
        if ($totals['vlprice'] > 3000 ){
            $nVlValorDeclarado = 3000;
        } else {
            $nVlValorDeclarado = $totals['vlprice'];
        }

        if($totals['nrqtd'] > 0) {
            $qs = http_build_query([
                'nCdEmpresa'=>'',
                'sDsSenha'=>'',
                'nCdServico'=>'41106',
                'sCepOrigem'=>'23946185',
                'sCepDestino'=>$nrzipcode,
                'nVlPeso'=>$totals['vlweight'],
                'nCdFormato'=>1,
                'nVlComprimento'=>$totals['vllength'],
                'nVlAltura'=>$totals['vlheight'],
                'nVlLargura'=>$totals['vlwidth'],
                'nVlDiametro'=>0,
                'sCdMaoPropria'=>'N',
                'nVlValorDeclarado'=>$nVlValorDeclarado,
                'sCdAvisoRecebimento'=>'S'
            ]);
            $xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);
            $result = $xml->Servicos->cServico;


            if ($result->MsgErro != ''){
                Cart::setMsgError($result->MsgErro);
            } else {
                Cart::clearMsgError();
            }

            $this->setnrdays($result->PrazoEntrega);
            $this->setvlfreight(Cart::formatValueToDecimal($result->Valor));

            $this->setdeszipcode($nrzipcode);

            $this->save();

            return $result;
        } else {

        }

    }

    public static function formatValueToDecimal($value):float {
        $value = str_replace('.','',$value);
        return str_replace(',','.',$value);
    }

    public static function setMsgError($msg){
        $_SESSION[Cart::SESSION_ERROR] = $msg;

    }

    public static function getMsgError(){
        $msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : "";
        Cart::clearMsgError();
        return $msg;
    }

    public static function clearMsgError(){
        $_SESSION[Cart::SESSION_ERROR] = NULL;
    }

    public function updateFreight(){
       if ($this->getdeszipcode() != '') {
           $this->setFreight($this->getdeszipcode());
       }
    }

    public function getValues()
    {
        $this->getCalculateTotal();
        return parent::getValues(); // TODO: Change the autogenerated stub
    }

    public function getCalculateTotal(){
        $this->updateFreight();
        $totals = $this->getProductsTotals();
        $this->setvlsubtotal($totals['vlprice']);
        $this->setvltotal($totals['vlprice'] + $this->getvlfreight());
    }

    public static function removeFromSession(){
        $_SESSION[Cart::SESSION] = NULL;
    }

    public function getTotalItensCart(){
        $sql = new Sql();
        $results = $sql->select("SELECT count(idproduct) as qtd FROM db_ecommerce.tb_cartsproducts WHERE idcart = :idcart and dtremoved is null;",[
            ':idcart'=>$this->getidcart()
        ]);
        return ((int)$results[0]['qtd']);
    }

    public static function getCartNotUsed($iduser){
        $sql = new Sql();
        $newcart = Cart::getFromSession();
        $newcart = (int)$newcart->getidcart();
        $sql->query("UPDATE tb_carts SET iduser = :iduser
               WHERE idcart = :idcart",[
            ':iduser'=>$iduser,
            ':idcart'=>$newcart
        ]);
        //busca o ultimo carrinho com produtos não excluidos
        $results = $sql->select("SELECT MAX(tb_carts.idcart) AS idcart FROM tb_carts INNER JOIN tb_cartsproducts
        ON tb_carts.idcart = tb_cartsproducts.idcart and tb_cartsproducts.dtremoved is null 
        WHERE tb_carts.idcart NOT IN (
        SELECT tb_carts.idcart FROM tb_carts
        INNER JOIN tb_orders
        ON tb_carts.idcart = tb_orders.idcart)
        AND tb_carts.iduser = :iduser", [
            ':iduser'=>$iduser
        ]);

        //insere os produtos do antigo carrinho no novo carrinho
        if ((int)$results[0]['idcart'] > 0) {
            $oldcart = (int)$results[0]['idcart'];
            $sql = new Sql();
            $sql->query("INSERT INTO tb_cartsproducts (tb_cartsproducts.idcart, tb_cartsproducts.idproduct)
            SELECT :newcart, idproduct FROM db_ecommerce.tb_cartsproducts
            where idcart = :oldcart and dtremoved is null;",[
                ':newcart'=>$newcart,
                ':oldcart'=>$oldcart
            ]);

            //copia o deszipcode do antigo carrinho
            $olddeszipcode = $sql->select("SELECT deszipcode FROM tb_carts WHERE idcart = :idcart;", [
                ':idcart'=>$oldcart
                ]);

            //atualiza o cep do novo carrinho
            if ((int)$olddeszipcode[0]['deszipcode'] > 0) {

                $sql->query("UPDATE tb_carts SET deszipcode = :olddeszipcode
               WHERE idcart = :idcart ",[
                   ':olddeszipcode'=>(int)$olddeszipcode[0]['deszipcode'],
                   ':idcart'=>$newcart
                ]);

            }


            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW()
               WHERE idcart = :idcart AND dtremoved IS NULL",[
                ':idcart'=>$oldcart ]);

        }



    }






}