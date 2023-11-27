<?php

class Log
{
    public $id;
    public $idUsuario;
    public $fecha;
    public $operacion;

    public function crearLog()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO logs (idUsuario, fecha, operacion)
        VALUES (:idUsuario, :fecha, :operacion)");

        $consulta->bindValue(':idUsuario', $this->idUsuario, PDO::PARAM_INT);
        date_default_timezone_set("America/Argentina/Buenos_Aires");
        $fecha = date('Y-m-d H:i:s');
        $consulta->bindValue(':fecha', $fecha, PDO::PARAM_STR);
        $consulta->bindValue(':operacion', $this->operacion, PDO::PARAM_STR);   

        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    
}

?>