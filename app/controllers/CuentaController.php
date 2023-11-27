<?php
require_once './models/Cuenta.php';
require_once './models/Usuario.php';
require_once './models/GestorCSV.php';
require_once './models/AutentificadorJWT.php';
require_once './interfaces/IApiUsable.php';

class CuentaController extends Cuenta implements IApiUsable
{

  public function CargarUno($request, $response, $args)
  {
    $parametros = $request->getParsedBody();

    $idUsuario = $parametros['idUsuario'];
    $tipoCuenta = $parametros['tipoCuenta'];
    $moneda = $parametros["moneda"];
    //$saldo = $parametros["saldo"];

    //HAY QUE VALIDAR SI EXISTE USUARIO, ANTES DE CREAR LA CUENTA!!!!!!!!

    $auxUsuario = Usuario::obtenerUsuarioPorIdSinBaja($idUsuario);
    if ($auxUsuario->fechaBaja != NULL) 
    {
      $mensaje = "Error. El usuario esta dado de baja. Activarlo para asignarle una cuenta.";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }
    
    if ($auxUsuario == NULL) 
    {
      $mensaje = "Error. El id de usuario no existe. Verifique los datos ingresados.";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }

    $auxCuenta = new Cuenta();
    $auxCuenta->idUsuario = $idUsuario;
    $auxCuenta->tipoCuenta = $tipoCuenta;
    $auxCuenta->moneda= $moneda;
    $auxCuenta->fechaAlta = new DateTime('now');

    if(isset($_POST["saldo"]))
    {
      $auxCuenta->saldo = $_POST["saldo"];
    } 
    else 
    {
      $auxCuenta->saldo = 0;
    }

    if(file_exists($_FILES["fotoCuenta"]["tmp_name"]))
    {
      $auxCuenta->fotoCuenta = $this->GuardarFoto($auxCuenta);
    } 
    else 
    {
      $auxCuenta->fotoCuenta = null;
    }

    $auxCuenta->crearCuenta();
    LogController::CargarUno($request, "Alta de cuenta");   

    $payload = json_encode(array("mensaje" => "Cuenta creada con exito"));
    $response->getBody()->write($payload);
    $response = $response->withStatus(200);
    return $response->withHeader('Content-Type', 'application/json');
  }
    


  public function TraerUno($request, $response, $args)
  {
    //$tipo = $args['tipo'];
    $id = $args['tipoCuenta'];
    $cuenta = Cuenta::obtenerCuentaPorTipo($id);

    if($cuenta)
    {
      $payload = json_encode($cuenta);
      $response->getBody()->write($payload);
      $response = $response->withStatus(200);
      LogController::CargarUno($request, "Ver Cuenta");   
      return $response->withHeader('Content-Type', 'application/json');           
    }
    else
    {
      $payload = json_encode(array("mensaje" => "Tipo Cuenta inválida. Verifique los datos ingresados."));
      $response->getBody()->write($payload);
      $response = $response->withStatus(400);
      return $response->withHeader('Content-Type', 'application/json');
    }
  }

  

  public function TraerTodos($request, $response, $args)
  {
    $lista = Cuenta::obtenerTodos();
    $payload = json_encode(array("listaCuentas" => $lista));
    $response->getBody()->write($payload);
    $response = $response->withStatus(200);
    LogController::CargarUno($request, "Ver listados de Cuentas");   
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function TraerTodosActivos($request, $response, $args)
  {
    $lista = Cuenta::obtenerTodosActivos();
    $payload = json_encode(array("listaCuentasActivas" => $lista));
    $response->getBody()->write($payload);
    $response = $response->withStatus(200);
    LogController::CargarUno($request, "Ver listados de Cuentas Activas");   
    return $response->withHeader('Content-Type', 'application/json');
  }



  public function ConsultarCuenta($request, $response, $args)
  {
    $tipo = $request->getQueryParams()["tipoCuenta"];
    $id = $request->getQueryParams()["idCuenta"];
    $cuenta = Cuenta::obtenerCuentaPorTipoId($tipo, $id);

    if($cuenta)
    { 
      LogController::CargarUno($request, "Consulta de Cuenta");     
      $mensaje = "MONEDA: " . $cuenta->moneda. " - SALDO: " . $cuenta->saldo;
      $payload = json_encode($mensaje);
      $response = $response->withStatus(200);
    }
    else
    {
      // -------ACA TENGO QUE VER LA COMBINACION DE TIPO Y NUMERO -----
      $mensaje = "Error, cuenta no encontrada.";
      $response = $response->withStatus(400);
      $payload = json_encode($mensaje);
    }

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
  }



  public function ModificarUno($request, $response, $args)
  {
    $datos = json_decode(file_get_contents("php://input"), true);
    $cuentaAModificar = new Cuenta();
    $cuentaAModificar->id=$datos["id"]; 
    $cuentaAModificar->idUsuario=$datos["idUsuario"]; 
    $cuentaAModificar->tipoCuenta=$datos["tipoCuenta"]; 
    $cuentaAModificar->moneda=$datos["moneda"];
    $cuentaAModificar->fechaAlta=$datos["fechaAlta"]; 
    if(array_key_exists("fechaAlta",$datos))
    {
      $cuentaAModificar->fechaAlta=$datos["fechaAlta"]; 
    }

    $existeCuenta = Cuenta::obtenerCuentaPorTipoId($cuentaAModificar->tipoCuenta, $cuentaAModificar->id);

    if ($existeCuenta == NULL) 
    {
      $mensaje = "Error. La cuenta no existe. Verifique los datos ingresados";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }

    if (Cuenta::modificarCuenta($cuentaAModificar)) 
    {   
      $mensaje = "Cuenta modificada con exito";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(200);    
      LogController::CargarUno($request, "Modificacion de Cuenta");   
    } 
    else
    {
      $mensaje = "No se pudo modificar la cuenta. Intente nuevamente";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
    }
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
  }



  public function BorrarUno($request, $response, $args)
  {
    $id = $args['id'];
    $existeCuenta = Cuenta::obtenerCuentaPorId($id);

    if ($existeCuenta == NULL) 
    {
      $mensaje = "Error. La cuenta no existe. Verifique los datos ingresados";
      $payload = json_encode($mensaje);
      $response = $response->withStatus(400);
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }

    CuentaController::MoverFoto($existeCuenta);

    Cuenta::borrarCuenta($id);

    $payload = json_encode(array("mensaje" => "Cuenta borrada con exito"));
    $response->getBody()->write($payload);
    $response = $response->withStatus(200);
    LogController::CargarUno($request, "Baja de Cuenta");  
    return $response->withHeader('Content-Type', 'application/json');
  }


  public static function GuardarFoto($cuenta)
  {
    $carpetaFotos = ".".DIRECTORY_SEPARATOR."ImagenesDeCuentas/2023".DIRECTORY_SEPARATOR;
    if(!file_exists($carpetaFotos))
    {
        mkdir($carpetaFotos, 0777, true);
    }

    $nombreDeArchivo = $cuenta->id."-".$cuenta->tipoCuenta;
    $destino = $carpetaFotos . $nombreDeArchivo . ".jpg";
    $tmpName = $_FILES["fotoCuenta"]["tmp_name"];

    if (move_uploaded_file($tmpName, $destino)) 
    {
        echo "<br>La foto se guardó correctamente.";
    } 
    else 
    {
        echo "<br>La foto no pudo guardarse.";
    }

    return $destino;
  }


  public static function MoverFoto($cuenta)
  {     
    $nombreDeArchivo = $cuenta->id."-".$cuenta->tipoCuenta;
    //echo $nombreDeArchivo;
    $antiguaCarpeta = ".".DIRECTORY_SEPARATOR."ImagenesDeCuentas/2023".DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR;
    $nuevaCarpeta = ".".DIRECTORY_SEPARATOR."ImagenesBackupCuentas/2023/".DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR;
    if(!file_exists($nuevaCarpeta)) 
    {
        mkdir($nuevaCarpeta, 0777, true);
    }
    if(rename($antiguaCarpeta.$nombreDeArchivo.".jpg", $nuevaCarpeta.$nombreDeArchivo.".jpg"))
    {
        echo "La foto ". $nombreDeArchivo. " se movió correctamente.";
        return true;
    }
    else 
    {
        echo "La foto ". $nombreDeArchivo." no pudo moverse.";
        return false;
    }
  }

}
?>