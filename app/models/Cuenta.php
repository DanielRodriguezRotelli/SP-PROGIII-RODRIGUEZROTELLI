<?php

require_once './db/AccesoDatos.php';

class Cuenta //implements JsonSerializable
{
    public $id;
    public $idUsuario;
    public $tipoCuenta;
    public $moneda;
    public $saldo;
    public $fechaAlta;
    public $fechaBaja;


    public function crearCuenta()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO cuentas (idUsuario, tipoCuenta, moneda, 
        saldo, fechaAlta, fechaBaja, fotoCuenta) 
        VALUES (:idUsuario, :tipoCuenta, :moneda, :saldo, :fechaAlta, :fechaBaja, :fotoCuenta)");
        $consulta->bindValue(':idUsuario', $this->idUsuario, PDO::PARAM_STR);
        $consulta->bindValue(':tipoCuenta', $this->tipoCuenta, PDO::PARAM_STR);
        $consulta->bindValue(':moneda', $this->moneda, PDO::PARAM_STR);
        $consulta->bindValue(':saldo', $this->saldo, PDO::PARAM_STR);
        $consulta->bindValue(':fechaAlta', $this->fechaAlta->format('Y-m-d'), PDO::PARAM_STR);
        $consulta->bindValue(':fechaBaja', $this->fechaBaja, PDO::PARAM_STR);
        $consulta->bindValue(':fotoCuenta', $this->fotoCuenta, PDO::PARAM_STR);

        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }
     

    public static function crearCuentaDesdeCsv($archivo)
    {
        $array = GestorCSV::LeerCsv($archivo);
        
        for($i = 0; $i < sizeof($array); $i++)
        {
            $datos = explode(",", $array[$i]); 
            $cuentaAux = new Cuenta();
            $cuentaAux->id = $datos[0];
            $cuentaAux->idUsuario = $datos[1];
            $cuentaAux->tipoCuenta = $datos[2];
            $cuentaAux->moneda = $datos[3];
            $cuentaAux->saldo = $datos[4];
            $cuentaAux->fechaAlta = $datos[5];
            $cuentaAux->fechaBaja = $datos[6];
            $cuentaAux->crearUsuario();
        }
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM cuentas");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Cuenta');
    }

    public static function obtenerTodosActivos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM cuentas WHERE fechaBaja is null");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Cuenta');
    }


    public static function obtenerCuentaPorTipo($tipoCuenta)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM cuentas WHERE tipoCuenta = :tipoCuenta AND fechaBaja is null");
        $consulta->bindValue(':tipoCuenta', $tipoCuenta, PDO::PARAM_INT);

        $consulta->execute();

        return $consulta->fetchObject('Cuenta');
    } 

    public static function obtenerCuentaPorId($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM cuentas WHERE id = :id and fechaBaja is null");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Cuenta');
    } 

    public static function obtenerCuentaPorTipoId($tipoCuenta, $id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM cuentas WHERE tipoCuenta = :tipoCuenta AND 
        id = :id AND fechaBaja is null");
        $consulta->bindValue(':tipoCuenta', $tipoCuenta, PDO::PARAM_INT);
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);

        $consulta->execute();

        return $consulta->fetchObject('Cuenta');
    } 

    public static function obtenerCuentaPorIdUsuario($idusuario)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM cuentas WHERE idUsuario = :idUsuario");
        $consulta->bindValue(':idUsuario', $idusuario, PDO::PARAM_INT);

        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Cuenta');
    } 

    public static function modificarCuenta($cuenta)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE cuentas SET 
        idUsuario=:idUsuario, tipoCuenta = :tipoCuenta, moneda= :moneda, fechaAlta=:fechaAlta WHERE id = :id");
        $consulta->bindValue(':id', $cuenta->id, PDO::PARAM_INT);
        $consulta->bindValue(':idUsuario', $cuenta->idUsuario, PDO::PARAM_INT);
        $consulta->bindValue(':tipoCuenta', $cuenta->tipoCuenta, PDO::PARAM_STR);
        $consulta->bindValue(':moneda', $cuenta->moneda, PDO::PARAM_STR);
        $consulta->bindValue(':fechaAlta', $cuenta->fechaAlta, PDO::PARAM_STR);
        //$consulta->bindValue(':fechaBaja', $cuenta->fechaBaja, PDO::PARAM_STR);

        return $consulta->execute();
    }

    public static function borrarCuenta($id)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE cuentas SET fechaBaja = :fechaBaja WHERE id = :id");
        date_default_timezone_set("America/Argentina/Buenos_Aires");
        $fechaBaja = date("Y-m-d H:i:s");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->bindValue(':fechaBaja', $fechaBaja);

        return $consulta->execute();
    }

    public static function actualizarSaldoDeCuenta($cuenta, $importe, $action)
    {
        $auxSaldo = 0;

        if ($action == "deposito") 
        {
            $auxSaldo = $cuenta->saldo + $importe;
        }
        if ($action == "retiro") 
        {
            $auxSaldo = $cuenta->saldo - $importe;
        }

        
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE cuentas SET saldo= :saldo WHERE id = :id");
        $consulta->bindValue(':saldo', $auxSaldo, PDO::PARAM_STR);
        $consulta->bindValue(':id', $cuenta->id, PDO::PARAM_INT);

        return $consulta->execute();
    }


   
}

?>