<?php

require_once './db/AccesoDatos.php';

class Operacion
{
    public $id;
    public $tipoOperacion;
    public $idCuenta;
    public $importe;
    public $fecha;

    

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM operaciones");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Operacion');
    }

    public static function TraerOperacionesPorUsuario($idUsuario)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT 'Deposito' AS tipoOperacion, d.id AS idOperacion, d.idCuenta, d.importe, d.fecha
        FROM depositos d
        JOIN cuentas c ON d.idCuenta = c.id
        JOIN usuarios u ON c.idUsuario = u.id
        WHERE u.id = :idUsuario
        
        UNION
        
        SELECT 'Retiro' AS tipoOperacion, r.id AS idOperacion, r.idCuenta, r.importe, r.fecha
        FROM retiros r
        JOIN cuentas c ON r.idCuenta = c.id
        JOIN usuarios u ON c.idUsuario = u.id
        WHERE u.id = :idUsuario
        ORDER BY fecha;
        ");
        $consulta->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Operacion');
    }
}
