<?php

require_once './db/AccesoDatos.php';

class Ajuste
{
    public $id;
    public $tipoTransaccion;
    public $idTransaccion;
    public $importe;
    public $motivo;
    public $fecha;

    public function crearAjuste()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO ajustes (tipoTransaccion, idTransaccion, importe, motivo, fecha) 
        VALUES (:tipoTransaccion, :idTransaccion, :importe, :motivo,  :fecha)");
        $consulta->bindValue(':tipoTransaccion', $this->tipoTransaccion, PDO::PARAM_STR);
        $consulta->bindValue(':idTransaccion', $this->idTransaccion, PDO::PARAM_STR);
        $consulta->bindValue(':importe', $this->importe, PDO::PARAM_STR);
        $consulta->bindValue(':motivo', $this->motivo, PDO::PARAM_STR);
        $consulta->bindValue(':fecha', $this->fecha->format('Y-m-d'), PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM ajustes");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Ajuste');
    }

    

}
?>