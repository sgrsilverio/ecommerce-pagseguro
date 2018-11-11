<?php

use \Hcode\Model\User;
use \Hcode\Model\Cart;

function formatPrice($vlprice) {

    if (!$vlprice > 0) $vlprice = 0;
        return number_format($vlprice, 2, ",", ".");
}

function format_real($vlprice) {

    if (!$vlprice > 0) $vlprice = 0;
    return number_format($vlprice, 2, ",", ".");
}

function checkLogin($inadmin = true){
    return User::checkLogin($inadmin);
}

function getUserName(){
    $user = User::getFromSession();
    return $user->getdesperson();
}

function getCartNrQtd() {
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();
    return $totals['nrqtd'];

}

function getCartVlSubTotal() {
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();
    return $totals['vlprice'];

}

function format_date($date) {

    return date('d/m/y', strtotime($date));
}