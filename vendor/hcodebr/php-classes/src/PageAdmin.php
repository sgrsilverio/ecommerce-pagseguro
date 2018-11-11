<?php
namespace  Hcode;

Class PageAdmin extends page {
public function __construct(array $opts = array(), $tpl_dir = "/views/admin/")
{
    parent::__construct($opts, $tpl_dir);
}
}

