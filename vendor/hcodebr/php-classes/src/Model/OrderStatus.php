<?php
/**
 * Created by PhpStorm.
 * User: shalon
 * Date: 07/10/2018
 * Time: 22:00
 */

namespace Hcode\Model;


use Hcode\DB\Sql;
use Hcode\Model;

class OrderStatus extends Model
{
    CONST EM_ABERTO = 1;
    CONST AGUARDANDO_PAGAMENTO = 2;
    CONST PAGO = 3;
    CONST ENTREGUE = 4;
    CONST EXCLUIDO = 5;

    public static function listAll(){
        $sql = new Sql();
        return $sql->select("SELECT * FROM db_ecommerce.tb_ordersstatus ORDER BY desstatus;");
    }

}