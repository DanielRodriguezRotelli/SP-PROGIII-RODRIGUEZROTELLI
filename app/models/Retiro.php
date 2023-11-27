<?php

require_once './db/AccesoDatos.php';

class Retiro
{
    public $id;
    public $idCuenta;
    public $importe;
    public $fecha;

    public function crearRetiro()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO retiros (idCuenta, importe, fecha) 
        VALUES (:idCuenta, :importe, :fecha)");
        $consulta->bindValue(':idCuenta', $this->idCuenta, PDO::PARAM_STR);
        $consulta->bindValue(':importe', $this->importe, PDO::PARAM_STR);
        $consulta->bindValue(':fecha', $this->fecha->format('Y-m-d'), PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM retiros");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Retiro');
    }

    public static function obtenerRetiroPorId($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM retiros WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Retiro');
    }

    public static function ajustarRetiro($retiro, $importe)
    {
        $auxImporte = $retiro[0]->importe + $importe;

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE retiros SET importe = :importe WHERE id = :id");
        $consulta->bindValue(':id', $retiro[0]->id, PDO::PARAM_INT);
        $consulta->bindValue(':importe', $auxImporte, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Retiro');
    }


    public static function TraerRetirosPorUsuario($idUsuario)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT r.id, r.idCuenta, r.importe, r.fecha
        FROM retiros r JOIN cuentas c ON r.idCuenta = c.id JOIN usuarios u ON c.idUsuario = u.id
        WHERE u.id = :idUsuario;");
        $consulta->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Retiro');
    }


    public static function obtenerRetiroPorTipoCuenta($tipo)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT r.id, r.idCuenta, r.importe, r.fecha
        FROM retiros r JOIN cuentas c ON r.idCuenta = c.id WHERE c.tipoCuenta = :tipo");
        $consulta->bindValue(':tipo', $tipo, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Retiro');
    }


    public static function obtenerRetiroPorMoneda($moneda)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT r.id, r.idCuenta, r.importe, r.fecha
        FROM retiros r JOIN cuentas c ON r.idCuenta = c.id WHERE c.moneda = :moneda");
        $consulta->bindValue(':moneda', $moneda, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Retiro');
    }

    

}
?>