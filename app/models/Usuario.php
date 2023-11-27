<?php

require_once './db/AccesoDatos.php';

class Usuario
{
    public $id;
    public $nombre;
    public $clave;
    public $perfil;
    public $tipoDocumento;
    public $nroDocumento;
    public $email;
    public $fechaAlta;
    public $fechaBaja;
    public $fotoUsuario;

    public function crearUsuario()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO usuarios (nombre, clave, perfil, 
        fechaAlta, fechaBaja, tipoDocumento, nroDocumento, email, fotoUsuario) 
        VALUES (:nombre, :clave, :perfil, :fechaAlta, :fechaBaja, :tipoDocumento, :nroDocumento, :email, :fotoUsuario)");
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':clave', $this->clave, PDO::PARAM_STR);
        $consulta->bindValue(':perfil', $this->perfil, PDO::PARAM_STR);
        $consulta->bindValue(':fechaAlta', $this->fechaAlta, PDO::PARAM_STR);
        $consulta->bindValue(':fechaBaja', $this->fechaBaja, PDO::PARAM_STR);
        $consulta->bindValue(':tipoDocumento', $this->tipoDocumento, PDO::PARAM_STR);
        $consulta->bindValue(':nroDocumento', $this->nroDocumento, PDO::PARAM_STR);
        $consulta->bindValue(':email', $this->email, PDO::PARAM_STR);
        $consulta->bindValue(':fotoUsuario', $this->fotoUsuario, PDO::PARAM_STR);

        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    
    public static function crearUsuarioDesdeCsv($archivo)
    {
        $array = GestorCSV::LeerCsv($archivo);
        
        for($i = 0; $i < sizeof($array); $i++)
        {
            $datos = explode(",", $array[$i]); 
            $usuarioAux = new Usuario();
            $usuarioAux->id = $datos[0];
            $usuarioAux->nombre = $datos[1];
            $usuarioAux->clave = $datos[2];
            $usuarioAux->perfil = $datos[3];
            $usuarioAux->fechaAlta = $datos[4];
            $usuarioAux->fechaBaja = $datos[5];
            $usuarioAux->tipoDocumento = $datos[6];
            $usuarioAux->roDocumento = $datos[7];
            $usuarioAux->email = $datos[8];
            $usuarioAux->crearUsuario();
        }
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM usuarios");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Usuario');
    }

    public static function obtenerTodosActivos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM usuarios WHERE fechaBaja is null");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Usuario');
    }

    public static function obtenerUsuario($dni)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM usuarios WHERE nroDocumento = :nroDocumento");
        $consulta->bindValue(':nroDocumento', $dni, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Usuario');
    }

    public static function obtenerUsuarioPorId($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM usuarios WHERE id = :id and fechaBaja is null");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Usuario');
    } 

    public static function obtenerUsuarioPorIdSinBaja($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM usuarios WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Usuario');
    } 

    public static function modificarUsuario($usuario)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE usuarios SET 
        nombre=:nombre, clave = :clave, perfil= :perfil, tipoDocumento=:tipoDocumento, 
        nroDocumento=:nroDocumento, email=:email  WHERE id = :id");
        $consulta->bindValue(':id', $usuario->id, PDO::PARAM_INT);
        $consulta->bindValue(':nombre', $usuario->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':clave', $usuario->clave, PDO::PARAM_STR);
        $consulta->bindValue(':perfil', $usuario->perfil, PDO::PARAM_STR);
        $consulta->bindValue(':tipoDocumento', $usuario->tipoDocumento, PDO::PARAM_STR);
        $consulta->bindValue(':nroDocumento', $usuario->nroDocumento, PDO::PARAM_STR);
        $consulta->bindValue(':email', $usuario->email, PDO::PARAM_STR);
        //$consulta->bindValue(':fechaBaja', null, PDO::PARAM_STR);

        return $consulta->execute();
    }

    public static function activarUsuario($usuario)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE usuarios SET fechaBaja=:fechaBaja  WHERE id = :id");
        $consulta->bindValue(':id', $usuario->id, PDO::PARAM_INT);
        $consulta->bindValue(':fechaBaja', null, PDO::PARAM_STR);

        return $consulta->execute();
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

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'tipoOperacion');
    }


    public static function borrarUsuario($id)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE usuarios SET fechaBaja = :fechaBaja WHERE id = :id");
        date_default_timezone_set("America/Argentina/Buenos_Aires");
        $fechaBaja = date("Y-m-d H:i:s");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->bindValue(':fechaBaja', $fechaBaja);

        return $consulta->execute();
    }

    public static function asignarFotoUsuario($usuario)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE pedidos SET 
        fotoUsuario = :fotoUsuario WHERE id = :id");
        $consulta->bindValue(':id', $usuario->id, PDO::PARAM_INT);
        $consulta->bindValue(':fotoMesa', $usuario->fotoUsuario, PDO::PARAM_STR);

        return $consulta->execute();
    }
    
}
?>