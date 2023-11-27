<?php

require_once './db/AccesoDatos.php';

class Deposito
{
    public $id;
    public $idCuenta;
    public $importe;
    public $fecha;
    public $fotoDeposito;

    public function crearDeposito()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO depositos (idCuenta, importe, fecha, fotoDeposito) 
        VALUES (:idCuenta, :importe, :fecha, :fotoDeposito)");
        $consulta->bindValue(':idCuenta', $this->idCuenta, PDO::PARAM_STR);
        $consulta->bindValue(':importe', $this->importe, PDO::PARAM_STR);
        $consulta->bindValue(':fecha', $this->fecha->format('Y-m-d'), PDO::PARAM_STR);
        $consulta->bindValue(':fotoDeposito', $this->fotoDeposito, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM depositos");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Deposito');
    }

    public static function obtenerDepositoPorId($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM depositos WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Deposito');
    }


    public static function obtenerDepositoPorTipoCuenta($tipo)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT d.id, d.idCuenta, d.importe, d.fecha
        FROM depositos d JOIN cuentas c ON d.idCuenta = c.id WHERE c.tipoCuenta = :tipo");
        $consulta->bindValue(':tipo', $tipo, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Deposito');
    }

    public static function obtenerDepositoPorMoneda($moneda)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT d.id, d.idCuenta, d.importe, d.fecha
        FROM depositos d JOIN cuentas c ON d.idCuenta = c.id WHERE c.moneda = :moneda");
        $consulta->bindValue(':moneda', $moneda, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Deposito');
    }


    public static function ajustarDeposito($deposito, $importe)
    {
        $auxImporte = $deposito[0]->importe + $importe;

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE depositos SET importe = :importe WHERE id = :id");
        $consulta->bindValue(':id', $deposito[0]->id, PDO::PARAM_INT);
        $consulta->bindValue(':importe', $auxImporte, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Deposito');
    }


    public static function TraerDepositosPorIdCuenta($idCuenta)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM depositos WHERE idCuenta = :idCuenta");
        $consulta->bindValue(':idCuenta', $idCuenta, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Deposito');
    }


    public static function TraerDepositosPorUsuario($idUsuario)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT d.id, d.idCuenta, d.importe, d.fecha
        FROM depositos d JOIN cuentas c ON d.idCuenta = c.id JOIN usuarios u ON c.idUsuario = u.id
        WHERE u.id = :idUsuario;");
        $consulta->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Deposito');
    }

    

}
?>