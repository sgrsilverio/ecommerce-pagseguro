<?php

namespace Hcode\Model;

use \Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Mailer;


class User extends Model {

    const SESSION = "User";
    const SECRET = "Tld717@ft9551th6ap3zq9";
    const IV = "1611871819141829";
    const ERROR_REGISTER = "UserErrorRegister";
    const ERROR = "UserError";
    const SUCCESS = "Success";

    public static function getFromSession(){
        $user = new User();
        if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {
            $user->setData($_SESSION[User::SESSION]);
        }
        return $user;
    }

    public static function checkLogin($inadmin = true)
    {
        if (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
        ) {
            //Não está logado
            return false;
        } else {
            if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {
                return true;
            } else if ($inadmin === false) {
                return true;
            } else {
                return false;
            }
        }
    }

    protected $fields = [
        "iduser", "idperson", "deslogin", "despassword", "inadmin", "dtergister"
    ];

    public static function login($login, $password)
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b ON a.idperson = b.idperson WHERE a.deslogin = :LOGIN", array(
            ":LOGIN"=>$login
        ));
        if (count($results) === 0)
        {
            //throw new \Exception("Usuário inexistente ou senha inválida.");
            header("Location: /admin/login");
            User::setError("Usuário inexistente ou senha inválida.");
            exit;
        }
        $data = $results[0];


        if ( password_verify($password,$data["despassword"]) === true)
        {
            $user = new User();
            $data['desperson'] = utf8_encode($data['desperson']);
            $user->setData($data);
            $_SESSION[User::SESSION] = $user->getValues();
            $iduser = $_SESSION[User::SESSION];
            if ((int)$iduser["iduser"] > 0) {
                Cart::getCartNotUsed((int)$iduser["iduser"]);
            }
            return $user;
        } else {

            //throw new \Exception("Usuário inexistente ou senha inválida.");
            header("Location: /admin/login");
            User::setError("Usuário inexistente ou senha inválida.");
        }
    }

    public static function logout()
    {
        $_SESSION[User::SESSION] = NULL;
    }

    public static function verifyLogin($inadmin = true)
    {
        if (!User::checkLogin($inadmin)) {
            if ($inadmin) {
                header("Location: /admin/login");
            } else {
                header("Location: /login");
            }
            exit;
        }
    }

    public static function listAll(){
        $sql = new sql();
        return $sql->select("select * from tb_users a INNER JOIN tb_persons b USING (idperson) ORDER BY b.desperson");
    }

    public function save(){

            $sql = new Sql();


            $results = $sql->select("CALL sp_users_save (:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
                ":desperson" => utf8_decode($this->getdesperson()),
                ":deslogin" => $this->getdeslogin(),
                ":despassword" => password_hash($this->getdespassword(),PASSWORD_DEFAULT),
                ":desemail" => $this->getdesemail(),
                ":nrphone" => $this->getnrphone(),
                ":inadmin" => $this->getinadmin()
            ));


            $this->setData($results[0]);


    }

    public function get($iduser){
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser ", array(
            ":iduser"=>$iduser
        ));
        $data = $results[0];
        $data['desperson'] = utf8_encode($data['desperson']);
        $this->setData($data);
    }

    public function update(){

        $sql = new Sql();
        $results = $sql->select("CALL sp_usersupdate_save (:iduser,:desperson,:deslogin,:despassword,:desemail,:nrphone,:inadmin)", array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>utf8_decode($this->getdesperson()),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>password_hash($this->getdespassword(), PASSWORD_DEFAULT),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));
        $this->setData($results[0]);
    }

    public function delete($iduser){
        $sql = new Sql();
        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser"=>$iduser
        ));
    }

    public static function getForgot($email){
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b on a.idperson = b.idperson WHERE a.desemail = :email", array(
            ":email"=>$email
        ));
        if(count($results)=== 0) {
            throw new \Exception("Não foi possivel recuperar a senha -- linha 116 erro em getforgot .");
            //header("Location: /login");
        } else {
            $data = $results[0];
            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser"=>$data["iduser"],
                ":desip"=>$_SERVER["REMOTE_ADDR"]
            ));
            if (count($results2) === 0) {
                throw new \Exception("Não foi possivel recuperar a senha -- linha 125 erro em getforgot.");
                //header("Location: /login");
            }else {
                $dataRecovery = $results2[0];
                $vetor = $dataRecovery["idrecovery"]; //vou usar isso como vetor de inicializaçao
                $code = $dataRecovery["iduser"];
                //$code =  base64_encode(openssl_encrypt($dataRecovery["iduser"], "AES-256-CBC", user::SECRET, OPENSSL_RAW_DATA,user::IV));
                $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";
                $mailer = new Mailer($data["desemail"],$data["desperson"],"Redefinir Senha","forgot", array(
                    "name"=>$data["desperson"],
                    "link"=>$link
                ));
                var_dump($mailer);

                exit;
                //$mailer->send();
                //return $data;
            }
        }
    }

    public static function validForgotDecrypt($code){
        //$Resultado = base64_decode($code);
        $idrecovery = $code;
        //$idrecovery = /*$code;*/ (openssl_decrypt($code,"AES-256-CBC",user::SECRET, OPENSSL_RAW_DATA,user::IV));

        $sql = new Sql();
        $results = $sql ->select("SELECT * FROM tb_userspasswordsrecoveries a
        INNER JOIN tb_users b
        on a.iduser = b.iduser
        INNER JOIN tb_persons c
        on b.idperson = c.idperson
        WHERE a.iduser = :idrecovery AND a.dtrecovery IS NULL
        AND DATE_ADD(a.dtregister, INTERVAL 10 HOUR) >= NOW();", array(
            ":idrecovery"=>$idrecovery
        ));

        if (count($results) === 0) {
            throw new \Exception("Nao foi possivel recuperar a senha -- erro linha 161 validforgotdecrypt--");
        } else {
            return $results[0];
        }
    }

    public static function setForgotUsed($idrecovery){
        $sql =new Sql();
        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() 
        WHERE idrecovery = :idrecovery", array(
            ":idrecovery"=>$idrecovery
        ));
    }

    public function setPassword($password,$iduser){

        $sql = new Sql();
        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
                        ":password"=>password_hash($password,PASSWORD_DEFAULT),
                        ":iduser"=>$iduser
        ));


    }

    public static function setError($msg){
        $_SESSION[User::ERROR] = $msg;
    }

    public static function getError(){
        $msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';
        User::clearError();
        return $msg;
    }

    public static function clearError(){
        $_SESSION[User::ERROR] = NULL;
    }

    public static function setErrorRegister($msg)
    {
        $_SESSION[User::ERROR_REGISTER] = $msg;

    }

    public static function getErrorRegister(){
        $msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';
        User::clearErrorRegister();
        return $msg;
    }

    public static function clearErrorRegister(){
        $_SESSION[User::ERROR_REGISTER] = NULL;
    }

    public static function checkLoginExist($login)
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_persons WHERE desemail = :desemail", [
            ':desemail'=>$login
        ]);
        return (count($results) > 0);
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
        $_SESSION[User::SUCCESS] = NULL;
    }

    public function getOrders(){
        $sql = new Sql();
        $results = $sql->select("
        SELECT * FROM tb_orders a 
        INNER JOIN tb_ordersstatus b USING (idstatus)
        INNER JOIN tb_carts c USING (idcart)
        INNER JOIN tb_users d ON d.iduser = a.iduser
        INNER JOIN tb_addresses e USING(idaddress)
        INNER JOIN tb_persons f ON f.idperson = d.idperson
        WHERE a.iduser = :iduser ORDER BY a.idorder DESC", [
            ':iduser'=>$this->getiduser()
        ]);
        if (count($results) > 0 ){
            return $results;
        }
    }

    public static function getPage($page = 1, $itemsPerPage = 3){
        $start = ($page - 1) * $itemsPerPage;
        $sql = new Sql();
        $results = $sql->select(
            "SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_users a
            INNER JOIN tb_persons b USING(idperson)
            ORDER BY b.desperson
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
            FROM tb_users a
            INNER JOIN tb_persons b USING(idperson)
            WHERE b.desperson LIKE :search OR b.desemail LIKE :search OR a.deslogin LIKE :search
            ORDER BY b.desperson
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

?>